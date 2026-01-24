<?php

namespace App\DTO;

use Carbon\Carbon;

class SystemEventData
{
    public function __construct(
        public int $candidateId,
        public ?int $vacancyId,
        public ?string $previewText,
        public ?string $authorName = null,
        public ?Carbon $occurredAt = null,
        public ?string $content = null,
    ) {
    }
}
