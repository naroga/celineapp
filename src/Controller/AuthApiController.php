<?php

namespace App\Controller;

use App\Controller\Concerns\HandlesJsonRequest;
use App\Dto\Auth\ForgotPasswordRequestInput;
use App\Dto\Auth\RegisterInput;
use App\Dto\Auth\ResetPasswordInput;
use App\Entity\User;
use App\Entity\WorkspaceInvite;
use App\Repository\UserRepository;
use App\Repository\WorkspaceInviteRepository;
use App\Service\PasswordResetManager;
use App\Service\WorkspaceManager;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth', name: 'api_auth_')]
final class AuthApiController extends AbstractController
{
    use HandlesJsonRequest;

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        ValidatorInterface $validator,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        WorkspaceInviteRepository $workspaceInviteRepository,
        WorkspaceManager $workspaceManager,
    ): JsonResponse {
        $payload = $this->decodePayload($request);

        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $input = new RegisterInput(
            $payload['firstName'] ?? '',
            $payload['lastName'] ?? '',
            $payload['email'] ?? '',
            $payload['password'] ?? '',
            $payload['passwordConfirmation'] ?? '',
            $payload['inviteToken'] ?? null,
        );

        $violations = $validator->validate($input);

        if (count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        $inviteToken = $input->getInviteToken();
        $tokenInvite = null;

        if ($inviteToken !== null) {
            $tokenInvite = $workspaceInviteRepository->findOnePendingByToken($inviteToken, new DateTimeImmutable());

            if (!$tokenInvite instanceof WorkspaceInvite) {
                return $this->json([
                    'error' => 'invalid_invite',
                    'message' => 'That invite link is no longer valid.',
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($tokenInvite->getEmail() !== mb_strtolower($input->getEmail())) {
                return $this->json([
                    'error' => 'invite_email_mismatch',
                    'message' => 'Use the email address that received the invite.',
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        if ($userRepository->findOneByEmail($input->getEmail()) !== null) {
            return $this->json([
                'error' => 'email_taken',
                'message' => 'We already have an account for that email address.',
            ], Response::HTTP_CONFLICT);
        }

        $user = new User(
            $input->getEmail(),
            $input->getFirstName(),
            $input->getLastName(),
        );
        $hashedPassword = $passwordHasher->hashPassword($user, $input->getPassword());
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        $workspaceManager->createWorkspace(
            $user,
            $this->buildDefaultWorkspaceName($user),
        );

        try {
            $workspaceManager->claimInvitesForUser($user, $tokenInvite);
        } catch (\DomainException|\Symfony\Component\Security\Core\Exception\AccessDeniedException) {
            // Ignore invite errors at this stage; the account is created and the user can accept invites later.
        }

        return $this->json([
            'message' => 'Account created. You can sign in now.',
        ], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(): void
    {
        throw new LogicException('This route is handled by Symfony security json_login.');
    }

    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        ValidatorInterface $validator,
        UserRepository $userRepository,
        PasswordResetManager $resetManager,
    ): JsonResponse {
        $payload = $this->decodePayload($request);

        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $input = new ForgotPasswordRequestInput(
            $payload['email'] ?? '',
        );

        $violations = $validator->validate($input);

        if (count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        $user = $userRepository->findOneByEmail($input->getEmail());

        if ($user !== null) {
            $tokenData = $resetManager->generateToken($user);
            $resetManager->sendResetEmail($user, $tokenData);
        }

        return $this->json([
            'message' => 'If that email is on file, we sent a reset link.',
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/reset-password/{selector}', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(
        string $selector,
        Request $request,
        PasswordResetManager $resetManager,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        $payload = $this->decodePayload($request);

        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $input = new ResetPasswordInput(
            $payload['password'] ?? '',
            $payload['passwordConfirmation'] ?? '',
        );

        $violations = $validator->validate($input);

        if (count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        $tokenValue = $payload['token'] ?? '';

        $resetToken = $tokenValue === '' ? null : $resetManager->validateToken($selector, $tokenValue);

        if ($resetToken === null) {
            return $this->json([
                'error' => 'invalid_token',
                'message' => 'That reset link is invalid or expired. Try requesting a new one.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $resetToken->getUser();
        $hashedPassword = $passwordHasher->hashPassword($user, $input->getPassword());
        $user->setPassword($hashedPassword);

        $resetManager->consumeToken($resetToken);

        return $this->json([
            'message' => 'Password updated. Sign in with your new password.',
        ]);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(Security $security): JsonResponse
    {
        $user = $security->getUser();

        if (!$user instanceof UserInterface) {
            return $this->json([
                'error' => 'unauthenticated',
                'message' => 'You are not signed in.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getUserIdentifier(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(): void
    {
        throw new LogicException('Logout is handled by the security system.');
    }

    private function buildDefaultWorkspaceName(User $user): string
    {
        $firstName = trim($user->getFirstName());

        if ($firstName === '') {
            return 'My Workspace';
        }

        if (str_ends_with(mb_strtolower($firstName), 's')) {
            return sprintf("%s' Workspace", $firstName);
        }

        return sprintf("%s's Workspace", $firstName);
    }
}
