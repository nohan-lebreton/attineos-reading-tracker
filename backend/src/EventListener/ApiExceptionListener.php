<?php

namespace App\EventListener;

use App\Catalog\Exception\BookNotFoundException;
use App\Catalog\Exception\CatalogUnavailableException;
use App\Exception\InvalidReadingException;
use App\Exception\ReadingAlreadyExistsException;
use App\Exception\ReadingNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ApiExceptionListener
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();
        [$status, $message, $details] = $this->describe($exception);

        if ($status >= 500) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
        }

        $body = ['error' => ['status' => $status, 'message' => $message]];
        if ([] !== $details) {
            $body['error']['details'] = $details;
        }

        $event->setResponse(new JsonResponse($body, $status));
    }

    /**
     * @return array{int, string, array<string, string>}
     */
    private function describe(\Throwable $exception): array
    {
        $validation = $exception->getPrevious();
        if ($exception instanceof HttpExceptionInterface && $validation instanceof ValidationFailedException) {
            return [Response::HTTP_UNPROCESSABLE_ENTITY, 'Les données envoyées sont invalides.', $this->violations($validation->getViolations())];
        }

        return match (true) {
            $exception instanceof InvalidReadingException => [Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage(), $exception->errors],
            $exception instanceof ReadingNotFoundException,
            $exception instanceof BookNotFoundException => [Response::HTTP_NOT_FOUND, $exception->getMessage(), []],
            $exception instanceof ReadingAlreadyExistsException => [Response::HTTP_CONFLICT, $exception->getMessage(), []],
            $exception instanceof CatalogUnavailableException => [Response::HTTP_SERVICE_UNAVAILABLE, 'Le catalogue de livres est momentanément indisponible. Réessayez dans quelques instants.', []],
            $exception instanceof HttpExceptionInterface => [$exception->getStatusCode(), $exception->getMessage(), []],
            default => [Response::HTTP_INTERNAL_SERVER_ERROR, 'Une erreur interne est survenue.', []],
        };
    }

    /**
     * @return array<string, string>
     */
    private function violations(ConstraintViolationListInterface $violations): array
    {
        $details = [];
        foreach ($violations as $violation) {
            $details[$violation->getPropertyPath()] = (string) $violation->getMessage();
        }

        return $details;
    }
}
