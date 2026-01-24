<?php

namespace App\DTO;

use Carbon\Carbon;

class ChatEventData
{
    public function __construct(
        public int $candidateId,
        public ?int $vacancyId,
        public ?string $previewText,
        public string $content,
        public string $provider,
        public string $direction, // incoming | outgoing
        public ?string $authorName = null,
        public ?string $companyName = null,
        public ?string $status = null, // pending/sent/failed/delivered/read
        public ?string $externalMessageId = null,
        public ?string $externalChatId = null,
        public ?string $syncError = null,
        public ?Carbon $occurredAt = null,
    ) {
    }
}
