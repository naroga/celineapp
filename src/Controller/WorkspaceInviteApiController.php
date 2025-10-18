<?php

namespace App\Controller;

use App\Controller\Concerns\HandlesJsonRequest;
use App\Entity\User;
use App\Entity\WorkspaceInvite;
use App\Entity\WorkspaceMembership;
use App\Repository\WorkspaceInviteRepository;
use App\Service\WorkspaceManager;
use DateTimeImmutable;
use DateTimeInterface;
use DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/api/workspace-invites', name: 'api_workspace_invites_')]
final class WorkspaceInviteApiController extends AbstractController
{
    use HandlesJsonRequest;

    public function __construct(
        private readonly WorkspaceInviteRepository $inviteRepository,
        private readonly WorkspaceManager $workspaceManager,
    ) {
    }

    #[Route('/pending', name: 'pending', methods: ['GET'])]
    public function pending(Security $security): JsonResponse
    {
        $user = $this->requireUser($security);

        if ($user instanceof JsonResponse) {
            return $user;
        }

        $invites = array_map(
            static fn (WorkspaceInvite $invite): array => [
                'id' => $invite->getId(),
                'email' => $invite->getEmail(),
                'role' => $invite->getRole(),
                'status' => $invite->getStatus(),
                'expiresAt' => $invite->getExpiresAt()->format(DateTimeInterface::ATOM),
                'workspace' => [
                    'id' => $invite->getWorkspace()->getId(),
                    'name' => $invite->getWorkspace()->getName(),
                ],
                'invitedBy' => [
                    'id' => $invite->getInvitedBy()->getId(),
                    'firstName' => $invite->getInvitedBy()->getFirstName(),
                    'lastName' => $invite->getInvitedBy()->getLastName(),
                    'email' => $invite->getInvitedBy()->getEmail(),
                ],
            ],
            $this->inviteRepository->findPendingForUser($user, new DateTimeImmutable()),
        );

        return $this->json([
            'invites' => $invites,
        ]);
    }

    #[Route('/{inviteId}/accept', name: 'accept', methods: ['POST'])]
    public function accept(string $inviteId, Security $security): JsonResponse
    {
        $user = $this->requireUser($security);

        if ($user instanceof JsonResponse) {
            return $user;
        }

        $invite = $this->inviteRepository->findOneById($inviteId);

        if (!$invite instanceof WorkspaceInvite) {
            return $this->inviteNotFoundResponse();
        }

        try {
            $membership = $this->workspaceManager->acceptInvite($invite, $user);
        } catch (AccessDeniedException $exception) {
            return $this->json([
                'error' => 'forbidden',
                'message' => $exception->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        } catch (DomainException $exception) {
            return $this->json([
                'error' => 'invalid_invite',
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'invite' => [
                'id' => $invite->getId(),
                'status' => $invite->getStatus(),
            ],
            'membership' => $this->formatMembership($membership),
        ]);
    }

    #[Route('/{inviteId}/decline', name: 'decline', methods: ['POST'])]
    public function decline(string $inviteId, Security $security): JsonResponse
    {
        $user = $this->requireUser($security);

        if ($user instanceof JsonResponse) {
            return $user;
        }

        $invite = $this->inviteRepository->findOneById($inviteId);

        if (!$invite instanceof WorkspaceInvite) {
            return $this->inviteNotFoundResponse();
        }

        try {
            $this->workspaceManager->declineInvite($invite, $user);
        } catch (AccessDeniedException $exception) {
            return $this->json([
                'error' => 'forbidden',
                'message' => $exception->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        } catch (DomainException $exception) {
            return $this->json([
                'error' => 'invalid_invite',
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'invite' => [
                'id' => $invite->getId(),
                'status' => $invite->getStatus(),
            ],
        ]);
    }

    #[Route('/token/{token}', name: 'token_show', methods: ['GET'])]
    public function showByToken(string $token): JsonResponse
    {
        $invite = $this->inviteRepository->findOnePendingByToken($token, new DateTimeImmutable());

        if (!$invite instanceof WorkspaceInvite) {
            return $this->inviteNotFoundResponse();
        }

        return $this->json([
            'invite' => [
                'id' => $invite->getId(),
                'email' => $invite->getEmail(),
                'role' => $invite->getRole(),
                'expiresAt' => $invite->getExpiresAt()->format(DateTimeInterface::ATOM),
                'workspace' => [
                    'id' => $invite->getWorkspace()->getId(),
                    'name' => $invite->getWorkspace()->getName(),
                ],
                'invitedBy' => [
                    'firstName' => $invite->getInvitedBy()->getFirstName(),
                    'lastName' => $invite->getInvitedBy()->getLastName(),
                    'email' => $invite->getInvitedBy()->getEmail(),
                ],
            ],
        ]);
    }

    #[Route('/token/{token}/accept', name: 'token_accept', methods: ['POST'])]
    public function acceptByToken(string $token, Security $security): JsonResponse
    {
        $user = $this->requireUser($security);

        if ($user instanceof JsonResponse) {
            return $user;
        }

        $invite = $this->inviteRepository->findOnePendingByToken($token, new DateTimeImmutable());

        if (!$invite instanceof WorkspaceInvite) {
            return $this->inviteNotFoundResponse();
        }

        try {
            $membership = $this->workspaceManager->acceptInvite($invite, $user);
        } catch (AccessDeniedException $exception) {
            return $this->json([
                'error' => 'forbidden',
                'message' => $exception->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        } catch (DomainException $exception) {
            return $this->json([
                'error' => 'invalid_invite',
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'invite' => [
                'id' => $invite->getId(),
                'status' => $invite->getStatus(),
            ],
            'membership' => $this->formatMembership($membership),
        ]);
    }

    private function requireUser(Security $security): User|JsonResponse
    {
        $user = $security->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'error' => 'unauthenticated',
                'message' => 'You must be signed in to manage workspace invites.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $user;
    }

    private function inviteNotFoundResponse(): JsonResponse
    {
        return $this->json([
            'error' => 'invite_not_found',
            'message' => 'Invite not found or expired.',
        ], Response::HTTP_NOT_FOUND);
    }

    private function formatMembership(WorkspaceMembership $membership): array
    {
        return [
            'id' => $membership->getId(),
            'role' => $membership->getRole(),
            'joinedAt' => $membership->getJoinedAt()->format(DateTimeInterface::ATOM),
            'workspace' => [
                'id' => $membership->getWorkspace()->getId(),
                'name' => $membership->getWorkspace()->getName(),
            ],
        ];
    }
}
