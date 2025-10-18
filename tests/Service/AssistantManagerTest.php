<?php

namespace App\Tests\Service;

use App\Entity\Assistant;
use App\Entity\User;
use App\Entity\Workspace;
use App\Service\AssistantManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class AssistantManagerTest extends TestCase
{
    public function testUpdateAssistantPersistsNormalizedChanges(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $owner = new User('owner@example.com', 'Owner', 'User');
        $workspace = new Workspace($owner, "Owner's Workspace");
        $assistant = new Assistant(
            $workspace,
            'Original',
            'female',
            'data:image/png;base64,AAAA',
            'current@example.com',
            '123',
            'Initial playbook',
        );

        $manager = new AssistantManager($entityManager);

        $updatedAssistant = $manager->updateAssistant(
            $assistant,
            '  Updated Name  ',
            'male',
            ' data:image/png;base64,BBBB ',
            '  Trim instructions  ',
            'NEW@example.com',
            ' 555-0000 ',
            'OpenAI',
            ' gpt-5-1 ',
        );

        self::assertSame('Updated Name', $updatedAssistant->getName());
        self::assertSame('male', $updatedAssistant->getGender());
        self::assertSame('data:image/png;base64,BBBB', $updatedAssistant->getProfilePicture());
        self::assertSame('Trim instructions', $updatedAssistant->getPlaybook());
        self::assertSame('new@example.com', $updatedAssistant->getEmail());
        self::assertSame('555-0000', $updatedAssistant->getPhoneNumber());
        self::assertSame('openai', $updatedAssistant->getDefaultProvider());
        self::assertSame('gpt-5-1', $updatedAssistant->getDefaultModel());
    }
}
