<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        /** @var GoogleClient $client */
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        /** @var GoogleUser $googleUser */
        $googleUser = $client->fetchUserFromToken($accessToken);

        $userBadge = new UserBadge(
            $googleUser->getEmail() ?? $googleUser->getId(),
            function () use ($googleUser): UserInterface {
                $googleId = $googleUser->getId();
                $email = $googleUser->getEmail();

                $user = null;

                if ($googleId !== null) {
                    $user = $this->userRepository->findOneByGoogleId($googleId);
                }

                if ($user === null && $email !== null) {
                    $user = $this->userRepository->findOneByEmail($email);

                    if ($user !== null) {
                        $user->setGoogleId($googleId);
                        $this->entityManager->flush();
                    }
                }

                if ($user === null) {
                    $user = new User(
                        $email ?? sprintf('google-user-%s@example.com', $googleId ?? bin2hex(random_bytes(4))),
                        $this->deriveFirstName($googleUser),
                        $this->deriveLastName($googleUser),
                    );
                    $user->setGoogleId($googleId);
                    $user->setRoles(['ROLE_USER']);

                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                }

                return $user;
            },
        );

        return new SelfValidatingPassport($userBadge);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetUrl = $request->getSession()->get('_security.' . $firewallName . '.target_path') ?? $this->urlGenerator->generate('app_home');

        return new RedirectResponse($targetUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new RedirectResponse($this->urlGenerator->generate('spa_login', ['error' => 'google']));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('spa_login'));
    }

    private function deriveFirstName(GoogleUser $googleUser): string
    {
        return $googleUser->getFirstName() ?: $this->fallbackNamePart($googleUser, 0);
    }

    private function deriveLastName(GoogleUser $googleUser): string
    {
        return $googleUser->getLastName() ?: $this->fallbackNamePart($googleUser, 1);
    }

    private function fallbackNamePart(GoogleUser $googleUser, int $partIndex): string
    {
        $name = $googleUser->getName() ?? ($googleUser->getEmail() ?? 'Google User');
        $parts = preg_split('/\s+/', trim($name));

        if (!$parts || !isset($parts[$partIndex])) {
            return $partIndex === 0 ? 'Google' : 'User';
        }

        return $parts[$partIndex];
    }
}
