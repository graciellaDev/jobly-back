<?php

namespace App\Services;
use App\Services\EventService;
use App\Services\SystemEventMessageBuilder;
use App\Models\Candidate;
use App\DTO\SystemEventData;

class CandidateChangeEventService
{
    public function __construct(
        private EventService $eventService,
        private SystemEventMessageBuilder $messageBuilder
    ) {
    }

    private array $fieldConfig = [
        'phone' => ['label' => 'Номер телефона'],
        'email' => ['label' => 'Email'],
    ];

    public function handleChanges(
        Candidate $candidate,
        array $old,
        array $new,
        array $tagsOld,
        array $tagsNew,
        ?string $authorName
    ): void {
        // 1) ФИО — одно событие
        $oldFio = trim(($old['surname'] ?? '') . ' ' . ($old['firstname'] ?? '') . ' ' . ($old['patronymic'] ?? ''));
        $newFio = trim(($new['surname'] ?? '') . ' ' . ($new['firstname'] ?? '') . ' ' . ($new['patronymic'] ?? ''));
        if ($oldFio !== $newFio) {
            $text = $this->messageBuilder->fullNameChanged($oldFio, $newFio, $authorName);
            $this->createEvent($candidate, $text, $authorName);
        }

        // 2) Универсальные поля (телефон/email и др.)
        foreach ($this->fieldConfig as $field => $cfg) {
            $oldVal = $old[$field] ?? null;
            $newVal = $new[$field] ?? null;
            if ($oldVal !== $newVal && $newVal !== null) {
                $text = $this->messageBuilder->fieldUpdated($cfg['label'], $newVal, $authorName);
                $this->createEvent($candidate, $text, $authorName);
            }
        }

        // 3) Теги — добавлено/удалено
        $added = array_diff($tagsNew, $tagsOld);
        $removed = array_diff($tagsOld, $tagsNew);

        foreach ($added as $tag) {
            $text = $this->messageBuilder->tagAdded($tag, $authorName);
            $this->createEvent($candidate, $text, $authorName);
        }

        foreach ($removed as $tag) {
            $text = $this->messageBuilder->tagRemoved($tag, $authorName);
            $this->createEvent($candidate, $text, $authorName);
        }
    }

    private function createEvent(Candidate $candidate, string $text, ?string $authorName): void
    {
        $this->eventService->createSystemEvent(new SystemEventData(
            candidateId: $candidate->id,
            vacancyId: $candidate->vacancy_id,
            previewText: $text,
            authorName: $authorName
        ));
    }
}
