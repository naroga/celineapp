<?php

namespace App\Dto\Assistant;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[Assert\GroupSequence(['CreateAssistantInput', 'Strict'])]
final class CreateAssistantInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 120)]
        private readonly string $name,
        #[Assert\NotBlank]
        #[Assert\Choice(['male', 'female'])]
        private readonly string $gender,
        #[Assert\NotBlank]
        #[Assert\Choice(['url', 'base64'])]
        private readonly string $profilePictureType,
        #[Assert\NotBlank]
        private readonly string $profilePictureValue,
        #[Assert\Length(max: 191)]
        private readonly ?string $profilePictureMimeType = null,
        #[Assert\Length(max: 2000, groups: ['Strict'])]
        private readonly ?string $instructions = null,
    ) {
    }

    public function getName(): string
    {
        return trim($this->name);
    }

    public function getGender(): string
    {
        return mb_strtolower(trim($this->gender));
    }

    public function getProfilePictureType(): string
    {
        return mb_strtolower(trim($this->profilePictureType));
    }

    public function getProfilePictureValue(): string
    {
        return trim($this->profilePictureValue);
    }

    public function getProfilePictureMimeType(): ?string
    {
        if ($this->profilePictureMimeType === null) {
            return null;
        }

        $trimmed = trim($this->profilePictureMimeType);

        return $trimmed === '' ? null : $trimmed;
    }

    public function getInstructions(): ?string
    {
        if ($this->instructions === null) {
            return null;
        }

        $trimmed = trim($this->instructions);

        return $trimmed === '' ? null : $trimmed;
    }

    public function getProfilePictureForStorage(): string
    {
        if ($this->getProfilePictureType() === 'url') {
            return $this->getProfilePictureValue();
        }

        $mimeType = $this->getProfilePictureMimeType() ?? 'image/png';

        return sprintf('data:%s;base64,%s', $mimeType, $this->stripBase64Whitespace($this->getProfilePictureValue()));
    }

    /**
     * @param ExecutionContextInterface $context
     */
    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        $type = $this->getProfilePictureType();
        $value = $this->getProfilePictureValue();

        if ($type === 'url') {
            if (filter_var($value, FILTER_VALIDATE_URL) === false) {
                $context
                    ->buildViolation('Provide a valid image URL.')
                    ->atPath('profilePictureValue')
                    ->addViolation();
            }

            return;
        }

        if ($this->getProfilePictureMimeType() === null) {
            $context
                ->buildViolation('Mime type is required when uploading a base64 image.')
                ->atPath('profilePictureMimeType')
                ->addViolation();
        }

        if (!$this->isValidBase64($value)) {
            $context
                ->buildViolation('Provide a valid base64-encoded image payload.')
                ->atPath('profilePictureValue')
                ->addViolation();
        }
    }

    private function isValidBase64(string $value): bool
    {
        $stripped = $this->stripBase64Whitespace($value);

        return base64_decode($stripped, true) !== false;
    }

    private function stripBase64Whitespace(string $value): string
    {
        return preg_replace('/\s+/', '', $value) ?? $value;
    }
}
