<?php

namespace App\Catalog;

final readonly class CatalogSearchResult
{
    /**
     * @param list<CatalogBook> $books
     */
    public function __construct(
        public int $count,
        public int $page,
        public bool $hasNext,
        public bool $hasPrevious,
        public array $books,
    ) {
    }
}
