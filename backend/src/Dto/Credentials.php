<?php

declare(strict_types=1);

namespace App\Dto;

use App\Exception\ValidationException;

final readonly class Credentials
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    public static function fromRequestBody(mixed $body): self
    {
        $email = is_array($body) && is_string($body['email'] ?? null) ? trim($body['email']) : '';
        $password = is_array($body) && is_string($body['password'] ?? null) ? $body['password'] : '';

        $errors = [];

        if ($email === '') {
            $errors['email'] = 'Укажите email';
        }

        if ($password === '') {
            $errors['password'] = 'Укажите пароль';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return new self($email, $password);
    }
}
