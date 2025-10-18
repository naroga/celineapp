<?php

namespace App\Service;

use App\Dto\Auth\ResetPasswordTokenData;
use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PasswordResetManager
{
    public function __construct(
        private readonly PasswordResetTokenRepository $tokenRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire('%app.reset_password_ttl%')]
        private readonly int $ttlInSeconds,
    ) {
    }

    public function generateToken(User $user): ResetPasswordTokenData
    {
        $this->tokenRepository->removeAllActiveForUser($user);

        $selector = bin2hex(random_bytes(12));
        $verifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $hashedToken = hash('sha256', $verifier);

        $expiresAt = (new DateTimeImmutable())->add(new DateInterval(sprintf('PT%dS', $this->ttlInSeconds)));
        $token = new PasswordResetToken($user, $selector, $hashedToken, $expiresAt);

        $this->tokenRepository->save($token);
        $this->entityManager->flush();

        return new ResetPasswordTokenData($selector, $verifier, $expiresAt);
    }

    public function sendResetEmail(User $user, ResetPasswordTokenData $tokenData, ?string $fromEmail = null, ?string $fromName = null): void
    {
        $resetLink = $this->urlGenerator->generate(
            'app_reset_password',
            [
                'selector' => $tokenData->getSelector(),
                'token' => $tokenData->getVerifier(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $email = (new Email())
            ->to(new Address($user->getEmail(), sprintf('%s %s', $user->getFirstName(), $user->getLastName())))
            ->subject('Reset your password')
            ->html(<<<HTML
<p>Hello {$user->getFirstName()},</p>
<p>You recently requested to reset the password for your Naroga Assistant account. Use the link below to choose a new password:</p>
<p><a href="{$resetLink}">Reset password</a></p>
<p>If you did not request a password reset, you can safely ignore this email.</p>
HTML);

        if ($fromEmail !== null) {
            $email->from(new Address($fromEmail, $fromName ?? 'Naroga Assistant'));
        }

        $this->mailer->send($email);
    }

    public function validateToken(string $selector, string $verifier): ?PasswordResetToken
    {
        $now = new DateTimeImmutable();
        $resetToken = $this->tokenRepository->findActiveBySelector($selector, $now);

        if ($resetToken === null) {
            return null;
        }

        $hashedVerifier = hash('sha256', $verifier);

        if (!hash_equals($resetToken->getHashedToken(), $hashedVerifier)) {
            return null;
        }

        return $resetToken;
    }

    public function consumeToken(PasswordResetToken $token): void
    {
        $token->consume(new DateTimeImmutable());
        $this->entityManager->flush();
    }
}
