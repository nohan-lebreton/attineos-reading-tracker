<?php

namespace App\Controller;

use App\Catalog\BookCatalog;
use App\Repository\BookRepository;
use App\Repository\ReadingRepository;
use App\Service\CurrentUserProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/books')]
final class BookController extends AbstractController
{
    public function __construct(private readonly BookCatalog $catalog)
    {
    }

    #[Route('', name: 'api_books_search', methods: ['GET'])]
    public function search(
        #[MapQueryParameter] ?string $search = null,
        #[MapQueryParameter(options: ['min_range' => 1], validationFailedStatusCode: Response::HTTP_BAD_REQUEST)] int $page = 1,
    ): JsonResponse {
        return $this->json($this->catalog->search($search, $page));
    }

    #[Route('/{gutendexId<\d+>}', name: 'api_books_show', methods: ['GET'])]
    public function show(
        int $gutendexId,
        BookRepository $books,
        ReadingRepository $readings,
        CurrentUserProvider $currentUser,
    ): JsonResponse {
        $book = $this->catalog->getBook($gutendexId);

        $localBook = $books->findOneByGutendexId($gutendexId);
        $reading = null === $localBook ? null : $readings->findOneBy(['user' => $currentUser->get(), 'book' => $localBook]);

        return $this->json([
            'book' => $book,
            'reading' => null === $reading ? null : ['id' => $reading->getId(), 'status' => $reading->getStatus()],
        ]);
    }
}
