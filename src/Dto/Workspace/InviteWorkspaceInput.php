<?php

namespace App\Dto\Workspace;

use App\Entity\WorkspaceMembership;
use Symfony\Component\Validator\Constraints as Assert;

final class InviteWorkspaceInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required.')]
        #[Assert\Email(message: 'Provide a valid email address.')]
        private readonly string $email,
        #[Assert\NotBlank(message: 'Role is required.')]
        #[Assert\Choice(callback: [WorkspaceMembership::class, 'availableRoles'], message: 'Invalid workspace role.')]
        private readonly string $role = WorkspaceMembership::ROLE_MEMBER,
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRole(): string
    {
        return $this->role;
    }
}
