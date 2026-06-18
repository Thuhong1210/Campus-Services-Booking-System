<?php

declare(strict_types=1);

class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        if (!isset($this->data[$field]) || trim((string) $this->data[$field]) === '') {
            $this->errors[$field] = "$label is required.";
        }
        return $this;
    }

    public function email(string $field): self
    {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Invalid email format.';
        }
        return $this;
    }

    public function min(string $field, int $min, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        if (!empty($this->data[$field]) && strlen((string) $this->data[$field]) < $min) {
            $this->errors[$field] = "$label must be at least $min characters.";
        }
        return $this;
    }

    public function numeric(string $field): self
    {
        if (!empty($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be a number.';
        }
        return $this;
    }

    public function datetimeOrder(string $start, string $end): self
    {
        if (!empty($this->data[$start]) && !empty($this->data[$end])) {
            if (strtotime($this->data[$start]) >= strtotime($this->data[$end])) {
                $this->errors[$end] = 'Start time must be earlier than end time.';
            }
        }
        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors[array_key_first($this->errors)] ?? null;
    }
}
