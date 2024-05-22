<?php

namespace App\Controller;

use App\Entity\Post;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class BlogController extends AbstractController
{
    private \App\Repository\PostRepository $postRepository;

    public function __construct(
        ManagerRegistry $doctrine,
        private readonly SerializerInterface $serializer
    ) {
        $this->postRepository = $doctrine->getManager()->getRepository(Post::class);
    }

    #[Route('/article/{id?}', name: 'app_blog', methods: ['GET'])]
    public function findArticles(Request $request, ?int $id): Response
    {
        if ($id !== null) {

            $article = $this->postRepository->find($id);
            if (!$article) {
                return new Response('Article not found', Response::HTTP_NOT_FOUND);
            }
            $jsonContent = $this->serializer->serialize($article, 'json', ['groups' => 'post']);
        } else {

            $limit = $request->query->getInt('limit', 3);
            $offset = $request->query->getInt('offset', 0);
            $createdSince = $request->query->get('created_since', (new \DateTime())->format('Y-m-d H:i:s'));


            $queryBuilder = $this->postRepository->createQueryBuilder('p');

            $date = \DateTime::createFromFormat('Y-m-d H:i:s', $createdSince);

            if (!$date) {
                return new Response("Date not valid. Use format: Y-m-d H:i:s", 400);
            }
            $queryBuilder->andWhere('p.posted >= :createdSince')->setParameter('createdSince', $date);


            $queryBuilder->setFirstResult($offset)->setMaxResults($limit);
            $articles = $queryBuilder->getQuery()->getResult();


            if (empty($articles)) {
                return new Response('No articles found', Response::HTTP_NOT_FOUND);
            }

            $jsonContent = $this->serializer->serialize($articles, 'json', ['groups' => 'post']);
        }

        return new Response($jsonContent, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }
}
