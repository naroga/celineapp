<?php

namespace App\Controller;

use App\ArtificialIntelligence\Persona\AssistantPersonaGenerator;
use App\Controller\Concerns\HandlesJsonRequest;
use App\Dto\Assistant\CreateAssistantInput;
use App\Entity\Assistant;
use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\WorkspaceMembership;
use App\Repository\AssistantRepository;
use App\Repository\WorkspaceRepository;
use App\Service\AssistantManager;
use App\Service\WorkspaceManager;
use DateTimeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/workspaces/{workspaceId}/assistants', name: 'api_workspaces_assistants_')]
final class AssistantApiController extends AbstractController
{
    use HandlesJsonRequest;

    public function __construct(
        private readonly AssistantPersonaGenerator $personaGenerator,
        private readonly AssistantManager $assistantManager,
        private readonly WorkspaceManager $workspaceManager,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        string $workspaceId,
        Security $security,
        WorkspaceRepository $workspaceRepository,
        AssistantRepository $assistantRepository,
    ): JsonResponse {
        $resolution = $this->resolveWorkspaceAccess($workspaceId, $security, $workspaceRepository);

        if ($resolution instanceof JsonResponse) {
            return $resolution;
        }

        [$workspace] = $resolution;

        $assistants = array_map(
            static fn (Assistant $assistant): array => [
                'id' => $assistant->getId(),
                'name' => $assistant->getName(),
                'gender' => $assistant->getGender(),
                'profilePicture' => $assistant->getProfilePicture(),
                'email' => $assistant->getEmail(),
                'phoneNumber' => $assistant->getPhoneNumber(),
                'instructions' => $assistant->getPlaybook(),
                'defaultProvider' => $assistant->getDefaultProvider(),
                'defaultModel' => $assistant->getDefaultModel(),
                'createdAt' => $assistant->getCreatedAt()->format(DateTimeInterface::ATOM),
                'updatedAt' => $assistant->getUpdatedAt()->format(DateTimeInterface::ATOM),
            ],
            $assistantRepository->findForWorkspace($workspace),
        );

        return $this->json([
            'assistants' => $assistants,
        ]);
    }

    #[Route('/generate-name', name: 'generate_name', methods: ['POST'])]
    public function generateName(
        string $workspaceId,
        Request $request,
        Security $security,
        WorkspaceRepository $workspaceRepository,
    ): JsonResponse {
        $resolution = $this->resolveWorkspaceAccess($workspaceId, $security, $workspaceRepository, true);

        if ($resolution instanceof JsonResponse) {
            return $resolution;
        }

        $payload = $this->decodePayload($request);

        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $gender = isset($payload['gender']) ? trim((string) $payload['gender']) : '';

        if ($gender === '') {
            return $this->json([
                'error' => 'invalid_payload',
                'message' => 'Gender selection is required to generate a name.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $name = $this->personaGenerator->generateName($gender);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'error' => 'invalid_payload',
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $exception) {
            return $this->json([
                'error' => 'generation_failed',
                'message' => 'Unable to generate a name right now. Try again in a moment.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $this->json([
            'name' => $name,
        ]);
    }

    #[Route('/generate-profile-picture', name: 'generate_profile_picture', methods: ['POST'])]
    public function generateProfilePicture(
        string $workspaceId,
        Request $request,
        Security $security,
        WorkspaceRepository $workspaceRepository,
    ): JsonResponse {
        $resolution = $this->resolveWorkspaceAccess($workspaceId, $security, $workspaceRepository, true);

        if ($resolution instanceof JsonResponse) {
            return $resolution;
        }

        $payload = $this->decodePayload($request);

        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $gender = isset($payload['gender']) ? trim((string) $payload['gender']) : '';
        $name = isset($payload['name']) ? trim((string) $payload['name']) : '';

        if ($gender === '' || $name === '') {
            return $this->json([
                'error' => 'invalid_payload',
                'message' => 'Name and gender are required to generate a profile picture.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $image = $this->personaGenerator->generateProfilePicture($name, $gender);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'error' => 'invalid_payload',
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $exception) {
            return $this->json([
                'error' => 'generation_failed',
                'message' => 'Unable to generate a profile picture right now. Try again in a moment.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $this->json([
            'image' => [
                'type' => $image->getType()->value,
                'value' => $image->getValue(),
                'mimeType' => $image->getMimeType(),
            ],
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        string $workspaceId,
        Request $request,
        Security $security,
        WorkspaceRepository $workspaceRepository,
        ValidatorInterface $validator,
    ): JsonResponse {
        $resolution = $this->resolveWorkspaceAccess($workspaceId, $security, $workspaceRepository, true);

        if ($resolution instanceof JsonResponse) {
            return $resolution;
        }

        [$workspace, $membership] = $resolution;

        if (!$this->canManageAssistants($membership)) {
            return $this->json([
                'error' => 'forbidden',
                'message' => 'You do not have permission to create assistants for this workspace.',
            ], Response::HTTP_FORBIDDEN);
        }

        $payload = $this->decodePayload($request);

        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $input = new CreateAssistantInput(
            $payload['name'] ?? '',
            $payload['gender'] ?? '',
            $payload['profilePicture']['type'] ?? '',
            $payload['profilePicture']['value'] ?? '',
            $payload['profilePicture']['mimeType'] ?? null,
            $payload['instructions'] ?? null,
        );

        $violations = $validator->validate($input);

        if (count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        try {
            $assistant = $this->assistantManager->createAssistant(
                $workspace,
                $input->getName(),
                $input->getGender(),
                $input->getProfilePictureForStorage(),
                $input->getInstructions(),
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'error' => 'invalid_payload',
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'assistant' => $this->transformAssistant($assistant),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{assistantId}', name: 'show', methods: ['GET'])]
    public function show(
        string $workspaceId,
        string $assistantId,
        Security $security,
        WorkspaceRepository $workspaceRepository,
        AssistantRepository $assistantRepository,
    ): JsonResponse {
        $resolution = $this->resolveWorkspaceAccess($workspaceId, $security, $workspaceRepository);

        if ($resolution instanceof JsonResponse) {
            return $resolution;
        }

        [$workspace] = $resolution;

        $assistant = $this->resolveAssistant($assistantId, $workspace, $assistantRepository);

        if ($assistant instanceof JsonResponse) {
            return $assistant;
        }

        return $this->json([
            'assistant' => $this->transformAssistant($assistant),
        ]);
    }

    #[Route('/{assistantId}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(
        string $workspaceId,
        string $assistantId,
        Request $request,
        Security $security,
        WorkspaceRepository $workspaceRepository,
        AssistantRepository $assistantRepository,
        ValidatorInterface $validator,
    ): JsonResponse {
        $resolution = $this->resolveWorkspaceAccess($workspaceId, $security, $workspaceRepository, true);

        if ($resolution instanceof JsonResponse) {
            return $resolution;
        }

        [$workspace] = $resolution;

        $assistant = $this->resolveAssistant($assistantId, $workspace, $assistantRepository);

        if ($assistant instanceof JsonResponse) {
            return $assistant;
        }

        $payload = $this->decodePayload($request);

        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $input = new CreateAssistantInput(
            $payload['name'] ?? '',
            $payload['gender'] ?? '',
            $payload['profilePicture']['type'] ?? '',
            $payload['profilePicture']['value'] ?? '',
            $payload['profilePicture']['mimeType'] ?? null,
            $payload['instructions'] ?? null,
        );

        $violations = $validator->validate($input);

        if (count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        try {
            $assistant = $this->assistantManager->updateAssistant(
                $assistant,
                $input->getName(),
                $input->getGender(),
                $input->getProfilePictureForStorage(),
                $input->getInstructions(),
                array_key_exists('email', $payload) ? $payload['email'] : $assistant->getEmail(),
                array_key_exists('phoneNumber', $payload) ? $payload['phoneNumber'] : $assistant->getPhoneNumber(),
                array_key_exists('defaultProvider', $payload) ? $payload['defaultProvider'] : $assistant->getDefaultProvider(),
                array_key_exists('defaultModel', $payload) ? $payload['defaultModel'] : $assistant->getDefaultModel(),
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'error' => 'invalid_payload',
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'assistant' => $this->transformAssistant($assistant),
        ]);
    }

    private function transformAssistant(Assistant $assistant): array
    {
        return [
            'id' => $assistant->getId(),
            'name' => $assistant->getName(),
            'gender' => $assistant->getGender(),
            'profilePicture' => $assistant->getProfilePicture(),
            'email' => $assistant->getEmail(),
            'phoneNumber' => $assistant->getPhoneNumber(),
            'instructions' => $assistant->getPlaybook(),
            'defaultProvider' => $assistant->getDefaultProvider(),
            'defaultModel' => $assistant->getDefaultModel(),
            'createdAt' => $assistant->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $assistant->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }

    private function resolveWorkspaceAccess(
        string $workspaceId,
        Security $security,
        WorkspaceRepository $workspaceRepository,
        bool $requireManagementPermissions = false,
    ): JsonResponse|array {
        $user = $this->requireUser($security);

        if ($user instanceof JsonResponse) {
            return $user;
        }

        $workspace = $workspaceRepository->find($workspaceId);

        if (!$workspace instanceof Workspace) {
            return $this->json([
                'error' => 'not_found',
                'message' => 'Workspace not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $membership = $this->workspaceManager->ensureMembership($user, $workspace);

        if ($membership === null) {
            return $this->json([
                'error' => 'not_found',
                'message' => 'Workspace not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($requireManagementPermissions && !$this->canManageAssistants($membership)) {
            return $this->json([
                'error' => 'forbidden',
                'message' => 'You do not have permission to manage assistants for this workspace.',
            ], Response::HTTP_FORBIDDEN);
        }

        return [$workspace, $membership];
    }

    private function canManageAssistants(WorkspaceMembership $membership): bool
    {
        return in_array($membership->getRole(), [WorkspaceMembership::ROLE_OWNER, WorkspaceMembership::ROLE_ADMIN], true);
    }

    private function resolveAssistant(
        string $assistantId,
        Workspace $workspace,
        AssistantRepository $assistantRepository,
    ): JsonResponse|Assistant {
        $assistant = $assistantRepository->findOneForWorkspace($workspace, $assistantId);

        if (!$assistant instanceof Assistant) {
            return $this->json([
                'error' => 'not_found',
                'message' => 'Assistant not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return $assistant;
    }

    private function requireUser(Security $security): User|JsonResponse
    {
        $user = $security->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'error' => 'unauthenticated',
                'message' => 'You must be signed in to manage assistants.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $user;
    }
}
