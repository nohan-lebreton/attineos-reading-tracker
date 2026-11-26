<?php

namespace App\Catalog;

final readonly class CatalogBook
{
    /**
     * @param list<string> $authors
     * @param list<string> $languages
     * @param list<string> $subjects
     * @param list<string> $summaries
     */
    public function __construct(
        public int $id,
        public string $title,
        public array $authors,
        public array $languages,
        public ?string $coverUrl,
        public array $subjects,
        public array $summaries,
        public int $downloadCount,
    ) {
    }
}
