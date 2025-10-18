<?php

namespace App\Service;

use App\Entity\Assistant;
use App\Entity\Workspace;
use Doctrine\ORM\EntityManagerInterface;

final class AssistantManager
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function createAssistant(
        Workspace $workspace,
        string $name,
        string $gender,
        string $profilePicture,
        ?string $instructions,
        ?string $email = null,
        ?string $phoneNumber = null,
    ): Assistant {
        $assistant = new Assistant(
            $workspace,
            $this->normalizeName($name),
            $gender,
            trim($profilePicture),
            $email,
            $phoneNumber,
            $this->normalizeInstructions($instructions),
        );

        $this->entityManager->persist($assistant);
        $this->entityManager->flush();

        return $assistant;
    }

    public function updateAssistant(
        Assistant $assistant,
        string $name,
        string $gender,
        string $profilePicture,
        ?string $instructions,
        ?string $email = null,
        ?string $phoneNumber = null,
        ?string $defaultProvider = null,
        ?string $defaultModel = null,
    ): Assistant {
        $assistant->setName($this->normalizeName($name));
        $assistant->setGender($gender);
        $assistant->setProfilePicture(trim($profilePicture));
        $assistant->setPlaybook($this->normalizeInstructions($instructions));
        $assistant->setEmail($email);
        $assistant->setPhoneNumber($phoneNumber);
        $assistant->setDefaultProvider($defaultProvider);
        $assistant->setDefaultModel($defaultModel);

        $this->entityManager->flush();

        return $assistant;
    }

    private function normalizeName(string $name): string
    {
        $normalized = trim($name);

        if ($normalized === '') {
            throw new \InvalidArgumentException('Assistant name is required.');
        }

        return $normalized;
    }

    private function normalizeInstructions(?string $instructions): ?string
    {
        if ($instructions === null) {
            return null;
        }

        $trimmed = trim($instructions);

        return $trimmed === '' ? null : $trimmed;
    }
}
