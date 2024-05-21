<?php

namespace App\Controller;

use App\Entity\Comments;
use App\Entity\Post;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class CommentController extends AbstractController
{

    #[Route('/comments/{id}', name: 'app_blog_comments')]
    public function findComments(ManagerRegistry $doctrine, int $id, SerializerInterface $serializer): Response
    {
        $entityManager = $doctrine->getManager();
        $article = $entityManager->getRepository(Post::class)->find($id);

        if (!$article) {
            return new Response('Article not found', Response::HTTP_NOT_FOUND);
        }

        $comments = $article->getComments();
        $jsonContent = $serializer->serialize($comments, 'json', ['groups' => 'comment']);

        return new Response($jsonContent, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/comments/{id}/add', name: 'app_blog_comments_post')]
    public function addComment(ManagerRegistry $doctrine, int $id, Request $request): Response
    {
        $requestBody = json_decode($request->getContent(), true);
        $entityManager = $doctrine->getManager();
        $article = $entityManager->getRepository(Post::class)->find($id);

        if (!$article) {
            return new Response('Article not found', Response::HTTP_NOT_FOUND);
        }

        if (empty($requestBody)) {
            return new Response('No request body', Response::HTTP_BAD_REQUEST);
        }

        $comment = new Comments();
        $comment->setArticle($article);
        $comment->setName($requestBody['name']);
        $comment->setMail($requestBody['mail']);
        $comment->setUrl($requestBody['url']);
        $comment->setText($requestBody['text']);

        $entityManager->persist($comment);
        $entityManager->flush();

        return new Response('Comment added', Response::HTTP_CREATED);

    }
}
