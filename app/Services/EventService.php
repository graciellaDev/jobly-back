<?php

namespace App\Services;

use App\DTO\SystemEventData;
use App\Models\Event;
use App\Models\EventSystem;
use Illuminate\Support\Facades\DB;

class EventService
{
    public function createSystemEvent(SystemEventData $data): Event
    {
        return DB::transaction(function () use ($data) {
            $event = Event::create([
                'candidate_id' => $data->candidateId,
                'vacancy_id' => $data->vacancyId,
                'type' => Event::TYPE_SYSTEM,
                'occurred_at' => $data->occurredAt ?? now(),
                'author_name' => $data->authorName,
                'channel' => null,
                'direction' => null,
            ]);

            EventSystem::create([
                'event_id' => $event->id,
                'content' => $data->content ?? $data->previewText,
            ]);

            return $event;
        });
    }
}
