<?php

namespace App\Dto;

use App\Enum\ReadingStatus;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateReadingInput
{
    public function __construct(
        public readonly ?ReadingStatus $status = null,
        public readonly ?\DateTimeImmutable $startedAt = null,
        public readonly ?\DateTimeImmutable $finishedAt = null,
        #[Assert\Range(min: 1, max: 5)]
        public readonly ?int $rating = null,
        #[Assert\Length(max: 2000)]
        public readonly ?string $comment = null,
    ) {
    }
}
