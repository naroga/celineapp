<?php

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final class ForgotPasswordRequestInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        private readonly string $email,
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
