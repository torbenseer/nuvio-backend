<?php

namespace App\Services\Content;

class ContentValidationResult
{
    /**
     * @param  list<array{field: string, code: string, message: string}>  $errors
     */
    public function __construct(private readonly array $errors = []) {}

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @return list<array{field: string, code: string, message: string}>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function hasError(string $field, ?string $code = null): bool
    {
        foreach ($this->errors as $error) {
            if ($error['field'] !== $field) {
                continue;
            }

            if ($code === null || $error['code'] === $code) {
                return true;
            }
        }

        return false;
    }
}
