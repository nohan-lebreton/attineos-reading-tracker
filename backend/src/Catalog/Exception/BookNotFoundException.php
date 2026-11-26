<?php

namespace App\Catalog\Exception;

final class BookNotFoundException extends \RuntimeException
{
    public function __construct(public readonly int $bookId)
    {
        parent::__construct(sprintf('Livre %d introuvable.', $bookId));
    }
}
