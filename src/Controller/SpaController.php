<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SpaController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    #[Route('/login', name: 'spa_login', methods: ['GET'])]
    #[Route('/register', name: 'spa_register', methods: ['GET'])]
    #[Route('/forgot-password', name: 'spa_forgot_password', methods: ['GET'])]
    #[Route('/reset-password/{selector}', name: 'spa_reset_password', requirements: ['selector' => '[A-Za-z0-9\-]+'], methods: ['GET'])]
    #[Route('/workspaces', name: 'spa_workspaces', methods: ['GET'])]
    #[Route('/workspaces/new', name: 'spa_workspace_new', methods: ['GET'])]
    #[Route('/workspaces/{workspaceId}', name: 'spa_workspace_detail', requirements: ['workspaceId' => '(?!new$)[A-Za-z0-9\-]+'], methods: ['GET'])]
    #[Route('/invites/{token}', name: 'spa_workspace_invite', requirements: ['token' => '[A-Za-z0-9\-_]+'], methods: ['GET'])]
    #[Route('/admin', name: 'spa_admin_dashboard', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('app/index.html.twig');
    }
}
