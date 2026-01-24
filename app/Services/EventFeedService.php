<?php

namespace App\Services;
use App\Models\Event;
use App\Models\EventChat;
use App\Models\EventSystem;

class EventFeedService
{

    public function getFeed(int $candidateId, ?int $vacancyId = null): array
    {
        $events = Event::forCandidateVacancy($candidateId, $vacancyId)
            ->ordered()
            ->get(['id', 'type', 'occurred_at', 'author_name', 'direction', 'channel']);

        $idsByType = $this->groupByType($events);

        $systems = EventSystem::whereIn('event_id', $idsByType['system'] ?? [])
            ->get()->keyBy('event_id');

        $chats = EventChat::whereIn('event_id', $idsByType['chat'] ?? [])
            ->get()->keyBy('event_id');
        // здесь будут остальные типы по мере добавления. Также добавить в map ниже

        return $events->map(function ($event) use ($systems, $chats) {
            $payload = match ($event->type) {
                'system' => $systems[$event->id] ?? (object) [],
                'chat' => $chats[$event->id] ?? (object) [],
                // здесь обработка других типов
                default => (object) []
            };

            return [
                'id' => $event->id,
                'type' => $event->type,
                'occurred_at' => $event->occurred_at,
                'author_name' => $event->author_name,
                'direction' => $event->direction,
                'channel' => $event->channel,
                'payload' => $payload
            ];
        })->all();
    }

    private function groupByType($events): array
    {
        $ids = [];
        foreach ($events as $event) {
            $ids[$event->type][] = $event->id;
        }
        return $ids;
    }
}
