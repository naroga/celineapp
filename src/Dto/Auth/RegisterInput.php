<?php

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

#[Assert\Expression('this.getPassword() === this.getPasswordConfirmation()', message: 'Passwords do not match.')]
final class RegisterInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 80)]
        private readonly string $firstName,
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 80)]
        private readonly string $lastName,
        #[Assert\NotBlank]
        #[Assert\Email]
        private readonly string $email,
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 72)]
        private readonly string $password,
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 72)]
        private readonly string $passwordConfirmation,
        #[Assert\Length(max: 191)]
        private readonly ?string $inviteToken = null,
    ) {
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getPasswordConfirmation(): string
    {
        return $this->passwordConfirmation;
    }

    public function getInviteToken(): ?string
    {
        $token = $this->inviteToken;

        if ($token === null || $token === '') {
            return null;
        }

        return $token;
    }
}
