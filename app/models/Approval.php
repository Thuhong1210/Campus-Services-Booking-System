<?php
declare(strict_types=1);

class Approval
{
    public function __construct(public array $data = []) {}

    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
