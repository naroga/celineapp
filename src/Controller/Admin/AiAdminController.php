<?php

namespace App\Controller\Admin;

use App\Repository\AiInteractionRepository;
use App\Service\Admin\AiAdminDashboardBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/ai', name: 'api_admin_ai_')]
final class AiAdminController extends AbstractController
{
    public function __construct(private readonly AiAdminDashboardBuilder $dashboardBuilder)
    {
    }

    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(Security $security): JsonResponse
    {
        if (!$security->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'error' => 'forbidden',
                'message' => 'You do not have permission to access admin analytics.',
            ], Response::HTTP_FORBIDDEN);
        }

        $payload = $this->dashboardBuilder->buildDashboard();

        return $this->json($payload);
    }

    #[Route('/interactions/{interactionId}', name: 'interaction_show', methods: ['GET'])]
    public function showInteraction(
        string $interactionId,
        Security $security,
        AiInteractionRepository $interactionRepository,
    ): JsonResponse {
        if (!$security->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'error' => 'forbidden',
                'message' => 'You do not have permission to access admin analytics.',
            ], Response::HTTP_FORBIDDEN);
        }

        $interaction = $interactionRepository->find($interactionId);

        if ($interaction === null) {
            return $this->json([
                'error' => 'not_found',
                'message' => 'Interaction not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $payload = $this->dashboardBuilder->buildInteractionDetail($interaction);

        return $this->json($payload);
    }
}
