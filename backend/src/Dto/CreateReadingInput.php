<?php

namespace App\Dto;

use App\Enum\ReadingStatus;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateReadingInput
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        public readonly ?int $gutendexId = null,
        public readonly ReadingStatus $status = ReadingStatus::ToRead,
    ) {
    }
}
