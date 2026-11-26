<?php

namespace App\Exception;

final class ReadingNotFoundException extends \RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct(sprintf('Lecture %d introuvable.', $id));
    }
}
