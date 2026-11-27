<?php

namespace App\User\Exception;

/**
 * Invalid user data. Carries one message per invalid field.
 */
class UserValidationException extends \InvalidArgumentException
{
    /**
     * @param array<string, string> $errors field => message
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('The user data is invalid.');
    }

    /** @return array<string, string> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
