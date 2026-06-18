<?php
declare(strict_types=1);

class Booking
{
    public function __construct(public array $data = []) {}

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function isCancellable(): bool
    {
        return in_array($this->data['status'] ?? '', ['pending', 'approved'], true);
    }
}
