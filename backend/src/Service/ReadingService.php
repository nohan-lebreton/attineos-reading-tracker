<?php

namespace App\Service;

use App\Catalog\BookCatalog;
use App\Dto\CreateReadingInput;
use App\Dto\UpdateReadingInput;
use App\Entity\Book;
use App\Entity\Reading;
use App\Entity\User;
use App\Enum\ReadingStatus;
use App\Exception\InvalidReadingException;
use App\Exception\ReadingAlreadyExistsException;
use App\Exception\ReadingNotFoundException;
use App\Repository\BookRepository;
use App\Repository\ReadingRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final class ReadingService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ReadingRepository $readings,
        private readonly BookRepository $books,
        private readonly BookCatalog $catalog,
    ) {
    }

    public function get(int $id, User $user): Reading
    {
        return $this->readings->findOneForUser($id, $user) ?? throw new ReadingNotFoundException($id);
    }

    public function add(CreateReadingInput $input, User $user): Reading
    {
        $gutendexId = (int) $input->gutendexId;
        $book = $this->books->findOneByGutendexId($gutendexId);

        if (null !== $book && null !== $this->readings->findOneBy(['user' => $user, 'book' => $book])) {
            throw new ReadingAlreadyExistsException($gutendexId);
        }

        if (null === $book) {
            $data = $this->catalog->getBook($gutendexId);
            $book = new Book($data->id, $data->title, $data->authors, $data->coverUrl, $data->languages);
            $this->em->persist($book);
        }

        $reading = new Reading($user, $book, $input->status);
        $this->applyStatusDates($reading);
        $this->em->persist($reading);

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            throw new ReadingAlreadyExistsException($gutendexId);
        }

        return $reading;
    }

    /**
     * @param list<string> $providedFields
     */
    public function update(Reading $reading, UpdateReadingInput $input, array $providedFields): Reading
    {
        $has = static fn (string $field): bool => \in_array($field, $providedFields, true);

        if ($has('status')) {
            if (null === $input->status) {
                throw new InvalidReadingException(['status' => 'Le statut ne peut pas être vide.']);
            }
            $reading->setStatus($input->status);
        }
        if ($has('startedAt')) {
            $reading->setStartedAt($input->startedAt);
        }
        if ($has('finishedAt')) {
            $reading->setFinishedAt($input->finishedAt);
        }
        if ($has('rating')) {
            $reading->setRating($input->rating);
        }
        if ($has('comment')) {
            $reading->setComment('' === trim((string) $input->comment) ? null : $input->comment);
        }

        if ($has('status')) {
            $this->applyStatusDates($reading);
        }
        $this->assertDatesAreConsistent($reading);

        $this->em->flush();

        return $reading;
    }

    public function delete(Reading $reading): void
    {
        $this->em->remove($reading);
        $this->em->flush();
    }

    private function applyStatusDates(Reading $reading): void
    {
        $today = new \DateTimeImmutable('today');

        if (\in_array($reading->getStatus(), [ReadingStatus::Reading, ReadingStatus::Read], true) && null === $reading->getStartedAt()) {
            $reading->setStartedAt($today);
        }
        if (ReadingStatus::Read === $reading->getStatus() && null === $reading->getFinishedAt()) {
            $reading->setFinishedAt(max($today, $reading->getStartedAt()));
        }
    }

    private function assertDatesAreConsistent(Reading $reading): void
    {
        $startedAt = $reading->getStartedAt();
        $finishedAt = $reading->getFinishedAt();

        if (null !== $startedAt && null !== $finishedAt && $finishedAt < $startedAt) {
            throw new InvalidReadingException(['finishedAt' => 'La date de fin doit être postérieure ou égale à la date de début.']);
        }
    }
}
