<?php

namespace App\Tests\Gutendex;

use App\Catalog\Exception\BookNotFoundException;
use App\Catalog\Exception\CatalogUnavailableException;
use App\Gutendex\GutendexClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

final class GutendexClientTest extends TestCase
{
    private const BOOK = [
        'id' => 345,
        'title' => 'Dracula',
        'authors' => [['name' => 'Stoker, Bram', 'birth_year' => 1847, 'death_year' => 1912]],
        'summaries' => ['A vampire novel.'],
        'subjects' => ['Horror tales'],
        'languages' => ['en'],
        'formats' => ['image/jpeg' => 'https://www.gutenberg.org/cache/epub/345/pg345.cover.medium.jpg'],
        'download_count' => 12345,
    ];

    public function testSearchMapsResultsAndSendsQuery(): void
    {
        $requests = [];
        $http = new MockHttpClient(function (string $method, string $url) use (&$requests) {
            $requests[] = $url;

            return new JsonMockResponse(['count' => 1, 'next' => null, 'previous' => null, 'results' => [self::BOOK]]);
        }, 'https://gutendex.test');

        $result = $this->client($http)->search('dracula', 2);

        self::assertSame('https://gutendex.test/books/?page=2&search=dracula', $requests[0]);
        self::assertSame(1, $result->count);
        self::assertSame(2, $result->page);
        self::assertFalse($result->hasNext);
        self::assertSame('Dracula', $result->books[0]->title);
        self::assertSame(['Stoker, Bram'], $result->books[0]->authors);
        self::assertSame('https://www.gutenberg.org/cache/epub/345/pg345.cover.medium.jpg', $result->books[0]->coverUrl);
    }

    public function testSecondIdenticalCallIsServedFromCache(): void
    {
        $http = new MockHttpClient([new JsonMockResponse(self::BOOK)], 'https://gutendex.test');
        $client = $this->client($http);

        $client->getBook(345);
        $book = $client->getBook(345);

        self::assertSame(1, $http->getRequestsCount());
        self::assertSame(345, $book->id);
    }

    public function testUnknownBookThrowsNotFound(): void
    {
        $http = new MockHttpClient([new JsonMockResponse(['detail' => 'Not found.'], ['http_code' => 404])], 'https://gutendex.test');

        $this->expectException(BookNotFoundException::class);
        $this->client($http)->getBook(99999999);
    }

    public function testServerErrorThrowsUnavailable(): void
    {
        $http = new MockHttpClient([new MockResponse('', ['http_code' => 503])], 'https://gutendex.test');

        $this->expectException(CatalogUnavailableException::class);
        $this->client($http)->search('dracula');
    }

    public function testTimeoutThrowsUnavailable(): void
    {
        $http = new MockHttpClient(static fn () => throw new TransportException('Idle timeout reached'), 'https://gutendex.test');

        $this->expectException(CatalogUnavailableException::class);
        $this->client($http)->search('dracula');
    }

    public function testFailuresAreNotCached(): void
    {
        $http = new MockHttpClient([
            new MockResponse('', ['http_code' => 503]),
            new JsonMockResponse(self::BOOK),
        ], 'https://gutendex.test');
        $client = $this->client($http);

        try {
            $client->getBook(345);
            self::fail('Une exception était attendue.');
        } catch (CatalogUnavailableException) {
        }

        self::assertSame('Dracula', $client->getBook(345)->title);
    }

    private function client(MockHttpClient $http): GutendexClient
    {
        return new GutendexClient($http, new ArrayAdapter());
    }
}
