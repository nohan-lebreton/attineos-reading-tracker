<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    private int $gutendexId;

    #[ORM\Column(type: Types::TEXT)]
    private string $title;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON, options: ['jsonb' => true])]
    private array $authors = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $coverUrl = null;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON, options: ['jsonb' => true])]
    private array $languages = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * @param list<string> $authors
     * @param list<string> $languages
     */
    public function __construct(int $gutendexId, string $title, array $authors = [], ?string $coverUrl = null, array $languages = [])
    {
        $this->gutendexId = $gutendexId;
        $this->title = $title;
        $this->authors = $authors;
        $this->coverUrl = $coverUrl;
        $this->languages = $languages;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGutendexId(): int
    {
        return $this->gutendexId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /** @return list<string> */
    public function getAuthors(): array
    {
        return $this->authors;
    }

    public function getCoverUrl(): ?string
    {
        return $this->coverUrl;
    }

    /** @return list<string> */
    public function getLanguages(): array
    {
        return $this->languages;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
