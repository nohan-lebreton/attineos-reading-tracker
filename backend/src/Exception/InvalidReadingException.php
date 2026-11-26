<?php

namespace App\Exception;

final class InvalidReadingException extends \DomainException
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('La lecture est invalide.');
    }
}
