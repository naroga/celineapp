<?php

namespace App\Controller;

use App\Controller\Concerns\HandlesJsonRequest;
use App\Dto\Workspace\CreateWorkspaceInput;
use App\Dto\Workspace\InviteWorkspaceInput;
use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\WorkspaceInvite;
use App\Entity\WorkspaceMembership;
use App\Repository\WorkspaceInviteRepository;
use App\Repository\WorkspaceMembershipRepository;
use App\Repository\WorkspaceRepository;
use App\Service\WorkspaceManager;
use DateTimeInterface;
use DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/workspaces', name: 'api_workspaces_')]
final class WorkspaceApiController extends AbstractController
{
    use HandlesJsonRequest;

    public function __construct(private readonly WorkspaceManager $workspaceManager)
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Security $security, WorkspaceRepository $workspaceRepository): JsonResponse
    {
        $user = $this->requireUser($security);

        if ($user instanceof JsonResponse) {
            return $user;
        }

        $workspaces = array_map(
            static fn (array $workspace): array => [
                'id' => $workspace['id'],
                'name' => $workspace['name'],
                'role' => $workspace['role'],
            ],
            $workspaceRepository->findSummariesForUser($user),
        );

        return $this->json([
            'workspaces' => $workspaces,
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        ValidatorInterface $validator,
        Security $security,
    ): JsonResponse {
        $user = $this->requireUser($security);

        if ($user instanceof JsonResponse) {
            return $user;
        }

        $payload = $this->decodePayload($request);

        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $input = new CreateWorkspaceInput($payload['name'] ?? '');
        $violations = $validator->validate($input);

        if (count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        $workspace = $this->workspaceManager->createWorkspace($user, $input->getName());

        return $this->json([
            'workspace' => [
                'id' => $workspace->getId(),
                'name' => $workspace->getName(),
                'role' => WorkspaceMembership::ROLE_OWNER,
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{workspaceId}', name: 'show', methods: ['GET'])]
    public function show(
        string $workspaceId,
        Security $security,
        WorkspaceRepository $workspaceRepository,
        WorkspaceMembershipRepository $membershipRepository,
        WorkspaceInviteRepository $inviteRepository,
    ): JsonResponse {
        $user = $this->requireUser($security);

        if ($user instanceof JsonResponse) {
            return $user;
        }

        $workspace = $workspaceRepository->find($workspaceId);

        if (!$workspace instanceof Workspace) {
            return $this->notFoundResponse();
        }

        $membership = $this->workspaceManager->ensureMembership($user, $workspace);

        if ($membership === null) {
            return $this->notFoundResponse();
        }

        $members = array_map(
            static fn (WorkspaceMembership $member): array => [
                'id' => $member->getId(),
                'role' => $member->getRole(),
                'joinedAt' => $member->getJoinedAt()->format(DateTimeInterface::ATOM),
                'user' => [
                    'id' => $member->getUser()->getId(),
                    'email' => $member->getUser()->getEmail(),
                    'firstName' => $member->getUser()->getFirstName(),
                    'lastName' => $member->getUser()->getLastName(),
                ],
            ],
            $membershipRepository->findMembersForWorkspace($workspace),
        );

        $canManageInvites = $this->workspaceManager->userCanManageInvites($membership);

        $invites = [];

        if ($canManageInvites) {
            $invites = array_map(
                static fn (WorkspaceInvite $invite): array => [
                    'id' => $invite->getId(),
                    'email' => $invite->getEmail(),
                    'role' => $invite->getRole(),
                    'status' => $invite->getStatus(),
                    'createdAt' => $invite->getCreatedAt()->format(DateTimeInterface::ATOM),
                    'expiresAt' => $invite->getExpiresAt()->format(DateTimeInterface::ATOM),
                    'respondedAt' => $invite->getRespondedAt()?->format(DateTimeInterface::ATOM),
                    'invitedBy' => [
                        'id' => $invite->getInvitedBy()->getId(),
                        'firstName' => $invite->getInvitedBy()->getFirstName(),
                        'lastName' => $invite->getInvitedBy()->getLastName(),
                        'email' => $invite->getInvitedBy()->getEmail(),
                    ],
                    'invitedUser' => $invite->getInvitedUser() === null ? null : [
                        'id' => $invite->getInvitedUser()->getId(),
                        'firstName' => $invite->getInvitedUser()->getFirstName(),
                        'lastName' => $invite->getInvitedUser()->getLastName(),
                        'email' => $invite->getInvitedUser()->getEmail(),
                    ],
                ],
                $inviteRepository->findForWorkspace($workspace),
            );
        }

        return $this->json([
            'workspace' => [
                'id' => $workspace->getId(),
                'name' => $workspace->getName(),
                'role' => $membership->getRole(),
                'members' => $members,
                'canManageInvites' => $canManageInvites,
                'invites' => $invites,
            ],
        ]);
    }

    #[Route('/{workspaceId}/invites', name: 'invite', methods: ['POST'])]
    public function invite(
        string $workspaceId,
        Request $request,
        ValidatorInterface $validator,
        Security $security,
        WorkspaceRepository $workspaceRepository,
    ): JsonResponse {
        $user = $this->requireUser($security);

        if ($user instanceof JsonResponse) {
            return $user;
        }

        $workspace = $workspaceRepository->find($workspaceId);

        if (!$workspace instanceof Workspace) {
            return $this->notFoundResponse();
        }

        $payload = $this->decodePayload($request);

        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $input = new InviteWorkspaceInput(
            $payload['email'] ?? '',
            $payload['role'] ?? WorkspaceMembership::ROLE_MEMBER,
        );

        $violations = $validator->validate($input);

        if (count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        try {
            $invite = $this->workspaceManager->inviteUser($workspace, $user, $input->getEmail(), $input->getRole());
        } catch (AccessDeniedException $exception) {
            return $this->json([
                'error' => 'forbidden',
                'message' => $exception->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        } catch (DomainException $exception) {
            return $this->json([
                'error' => 'conflict',
                'message' => $exception->getMessage(),
            ], Response::HTTP_CONFLICT);
        }

        return $this->json([
            'invite' => [
                'id' => $invite->getId(),
                'email' => $invite->getEmail(),
                'role' => $invite->getRole(),
                'status' => $invite->getStatus(),
                'createdAt' => $invite->getCreatedAt()->format(DateTimeInterface::ATOM),
                'expiresAt' => $invite->getExpiresAt()->format(DateTimeInterface::ATOM),
            ],
        ], Response::HTTP_CREATED);
    }

    private function requireUser(Security $security): User|JsonResponse
    {
        $user = $security->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'error' => 'unauthenticated',
                'message' => 'You must be signed in to manage workspaces.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $user;
    }

    private function notFoundResponse(): JsonResponse
    {
        return $this->json([
            'error' => 'not_found',
            'message' => 'Workspace not found.',
        ], Response::HTTP_NOT_FOUND);
    }
}
