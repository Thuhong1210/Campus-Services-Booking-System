<?php
declare(strict_types=1);

class User
{
    public function __construct(public array $data = []) {}

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
}
