<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Dto\AbstractDto;
use App\Enum\FormStatus;

class FormResponse extends AbstractDto
{
    protected ?FormStatus $status = null;
    protected ?string $message = null;
    /** @var array<string, string[]> */
    protected array $validation = [];
    protected ?string $redirectTo = null;

    /** @var array<string, string|int|float|null>|null */
    protected ?array $body = [];

    public function getStatus(): ?FormStatus
    {
        return $this->status;
    }

    public function setStatus(?FormStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string[][]
     */
    public function getValidation(): array
    {
        return $this->validation;
    }

    /**
     * @param string[][] $validation
     */
    public function setValidation(array $validation): self
    {
        $this->validation = $validation;

        return $this;
    }

    public function getRedirectTo(): ?string
    {
        return $this->redirectTo;
    }

    public function setRedirectTo(?string $redirectTo): self
    {
        $this->redirectTo = $redirectTo;

        return $this;
    }

    /**
     * @return array<string, string|int|float|null>|null
     */
    public function getBody(): ?array
    {
        return $this->body;
    }

    /**
     * @param array<string, string|int|float|null>|null $body
     */
    public function setBody(?array $body): void
    {
        $this->body = $body;
    }
}
