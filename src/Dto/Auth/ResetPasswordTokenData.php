<?php

namespace App\Dto\Auth;

use DateTimeImmutable;

final class ResetPasswordTokenData
{
    public function __construct(
        private readonly string $selector,
        private readonly string $verifier,
        private readonly DateTimeImmutable $expiresAt,
    ) {
    }

    public function getSelector(): string
    {
        return $this->selector;
    }

    public function getVerifier(): string
    {
        return $this->verifier;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
