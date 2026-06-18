<?php
declare(strict_types=1);

class Resource
{
    public function __construct(public array $data = []) {}

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function isBookable(): bool
    {
        return ($this->data['status'] ?? '') === 'available';
    }
}
