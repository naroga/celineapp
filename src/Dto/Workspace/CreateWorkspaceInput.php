<?php

namespace App\Dto\Workspace;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateWorkspaceInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'Workspace name is required.')]
        #[Assert\Length(min: 3, max: 120, minMessage: 'Workspace name must be at least 3 characters.')]
        private readonly string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
