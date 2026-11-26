<?php

namespace App\Entity;

use App\Enum\ReadingStatus;
use App\Repository\ReadingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

#[ORM\Entity(repositoryClass: ReadingRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_reading_user_book', fields: ['user', 'book'])]
#[ORM\Index(name: 'idx_reading_user_status', fields: ['user', 'status'])]
#[ORM\HasLifecycleCallbacks]
class Reading
{
    #[Groups(['reading'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[Groups(['reading'])]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Book $book;

    #[Groups(['reading'])]
    #[ORM\Column(length: 20, enumType: ReadingStatus::class)]
    private ReadingStatus $status;

    #[Groups(['reading'])]
    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[Groups(['reading'])]
    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    #[Groups(['reading'])]
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $rating = null;

    #[Groups(['reading'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[Groups(['reading'])]
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[Groups(['reading'])]
    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $user, Book $book, ReadingStatus $status = ReadingStatus::ToRead)
    {
        $this->user = $user;
        $this->book = $book;
        $this->status = $status;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getBook(): Book
    {
        return $this->book;
    }

    public function getStatus(): ReadingStatus
    {
        return $this->status;
    }

    public function setStatus(ReadingStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeImmutable $finishedAt): static
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
