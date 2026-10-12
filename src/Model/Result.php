<?php declare(strict_types=1);

namespace Kniebes\VerifyUrl\Model;

class Result
{
    private array $errors = [];
    public function __construct(
        private bool $isValid,
        private ?string $replacement = null,
    )
    {
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getReplacement(): ?string
    {
        return $this->replacement;
    }

    public function setIsValid(bool $isValid): Result
    {
        $this->isValid = $isValid;

        return $this;
    }

    public function setReplacement(?string $replacement): Result
    {
        $this->replacement = $replacement;

        return $this;
    }

    public function addError(string $error): self
    {
        $this->errors[] = $error;

        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
