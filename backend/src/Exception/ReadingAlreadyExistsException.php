<?php

namespace App\Exception;

final class ReadingAlreadyExistsException extends \RuntimeException
{
    public function __construct(int $gutendexId)
    {
        parent::__construct(sprintf('Le livre %d est déjà dans vos lectures.', $gutendexId));
    }
}
