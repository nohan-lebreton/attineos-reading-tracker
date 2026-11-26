<?php

namespace App\Gutendex;

use App\Catalog\BookCatalog;
use App\Catalog\CatalogBook;
use App\Catalog\CatalogSearchResult;
use App\Catalog\Exception\BookNotFoundException;
use App\Catalog\Exception\CatalogUnavailableException;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GutendexClient implements BookCatalog
{
    public function __construct(
        #[Target('gutendex.client')]
        private readonly HttpClientInterface $httpClient,
        #[Target('gutendex.cache')]
        private readonly CacheInterface $cache,
    ) {
    }

    public function search(?string $search, int $page = 1): CatalogSearchResult
    {
        $search = trim((string) $search);
        $page = max(1, $page);

        $query = ['page' => $page];
        if ('' !== $search) {
            $query['search'] = $search;
        }

        $data = $this->cache->get(
            'search_'.md5(mb_strtolower($search)).'_'.$page,
            fn (ItemInterface $item): array => $this->request('/books/', $query),
        );

        return new CatalogSearchResult(
            count: (int) $data['count'],
            page: $page,
            hasNext: null !== $data['next'],
            hasPrevious: null !== $data['previous'],
            books: array_map($this->toCatalogBook(...), $data['results']),
        );
    }

    public function getBook(int $id): CatalogBook
    {
        $data = $this->cache->get(
            'book_'.$id,
            fn (ItemInterface $item): array => $this->request(sprintf('/books/%d/', $id), [], $id),
        );

        return $this->toCatalogBook($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function toCatalogBook(array $data): CatalogBook
    {
        return new CatalogBook(
            id: (int) $data['id'],
            title: (string) $data['title'],
            authors: array_values(array_map(
                static fn (array $person): string => (string) $person['name'],
                $data['authors'] ?? [],
            )),
            languages: array_values($data['languages'] ?? []),
            coverUrl: $data['formats']['image/jpeg'] ?? null,
            subjects: array_values($data['subjects'] ?? []),
            summaries: array_values($data['summaries'] ?? []),
            downloadCount: (int) ($data['download_count'] ?? 0),
        );
    }

    /**
     * @param array<string, scalar> $query
     *
     * @return array<string, mixed>
     */
    private function request(string $path, array $query, ?int $bookId = null): array
    {
        try {
            $response = $this->httpClient->request('GET', $path, ['query' => $query]);
            $status = $response->getStatusCode();

            if (404 === $status && null !== $bookId) {
                throw new BookNotFoundException($bookId);
            }

            if (200 !== $status) {
                throw new CatalogUnavailableException(sprintf('Gutendex a répondu %d.', $status));
            }

            return $response->toArray();
        } catch (ExceptionInterface $e) {
            throw new CatalogUnavailableException('Gutendex est injoignable ou a répondu de façon invalide.', 0, $e);
        }
    }
}
