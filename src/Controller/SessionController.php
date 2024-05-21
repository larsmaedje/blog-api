<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SessionController extends AbstractController
{
    public function __construct(
        private readonly UserRepository              $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TokenStorageInterface       $tokenStorage
    ) {
    }


    #[Route('/session', name: 'session_status', methods: ['GET'])]
    public function getSessionStatus(): Response
    {
        $user = $this->getUser();
        if ($user) {
            return new Response('User is authenticated', Response::HTTP_OK);
        } else {
            return new Response('User is not authenticated', Response::HTTP_UNAUTHORIZED);
        }
    }

    #[Route('/session', name: 'session_login', methods: ['POST'])]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['user']) || !isset($data['pass'])) {
            return new Response('Invalid login data', Response::HTTP_BAD_REQUEST);
        }

        $username = $data['user'];
        $password = $data['pass'];

        $user = $this->userRepository->findOneBy(['username' => $username]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return new Response('Invalid credentials', Response::HTTP_UNAUTHORIZED);
        }

        // Log out the current user if any
        $this->tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        // Perform login
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);
        $request->getSession()->set('_security_main', serialize($token));

        return new Response('User logged in successfully', Response::HTTP_OK);
    }

    #[Route('/session', name: 'session_logout', methods: ['DELETE'])]
    public function logout(Request $request): Response
    {
        $this->tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        return new Response('User logged out successfully', Response::HTTP_OK);
    }
}
