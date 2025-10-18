<?php

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

#[Assert\Expression('this.getPassword() === this.getPasswordConfirmation()', message: 'Passwords do not match.')]
final class ResetPasswordInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 72)]
        private readonly string $password,
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 72)]
        private readonly string $passwordConfirmation,
    ) {
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getPasswordConfirmation(): string
    {
        return $this->passwordConfirmation;
    }
}
