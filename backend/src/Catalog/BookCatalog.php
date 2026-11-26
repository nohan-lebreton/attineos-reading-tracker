<?php

namespace App\Catalog;

use App\Catalog\Exception\BookNotFoundException;
use App\Catalog\Exception\CatalogUnavailableException;

interface BookCatalog
{
    /**
     * @throws CatalogUnavailableException
     */
    public function search(?string $search, int $page = 1): CatalogSearchResult;

    /**
     * @throws BookNotFoundException
     * @throws CatalogUnavailableException
     */
    public function getBook(int $id): CatalogBook;
}
