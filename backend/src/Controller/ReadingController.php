<?php

namespace App\Controller;

use App\Dto\CreateReadingInput;
use App\Dto\UpdateReadingInput;
use App\Enum\ReadingStatus;
use App\Repository\ReadingRepository;
use App\Service\CurrentUserProvider;
use App\Service\ReadingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/readings')]
final class ReadingController extends AbstractController
{
    public function __construct(
        private readonly ReadingService $readingService,
        private readonly CurrentUserProvider $currentUser,
    ) {
    }

    #[Route('', name: 'api_readings_list', methods: ['GET'])]
    public function list(ReadingRepository $readings, #[MapQueryParameter(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)] ?ReadingStatus $status = null): JsonResponse
    {
        return $this->json($readings->findForUser($this->currentUser->get(), $status), context: ['groups' => 'reading']);
    }

    #[Route('', name: 'api_readings_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateReadingInput $input): JsonResponse
    {
        $reading = $this->readingService->add($input, $this->currentUser->get());

        return $this->json($reading, Response::HTTP_CREATED, context: ['groups' => 'reading']);
    }

    #[Route('/{id<\d+>}', name: 'api_readings_update', methods: ['PATCH'])]
    public function update(int $id, Request $request, #[MapRequestPayload] UpdateReadingInput $input): JsonResponse
    {
        $reading = $this->readingService->get($id, $this->currentUser->get());
        $reading = $this->readingService->update($reading, $input, array_keys($request->toArray()));

        return $this->json($reading, context: ['groups' => 'reading']);
    }

    #[Route('/{id<\d+>}', name: 'api_readings_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $this->readingService->delete($this->readingService->get($id, $this->currentUser->get()));

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
