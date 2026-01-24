<?php

namespace App\Services;

class SystemEventMessageBuilder
{
    public function createdCandidate(string $fullName, ?string $author = null): string
    {
        $fullName = $this->safe($fullName, 'кандидат');
        return "создан кандидат: {$fullName}";
    }

    public function movedStage(string $stageName, string $oldStageName, ?string $author = null): string
    {
        $stageName = $this->safe($stageName, 'неизвестный этап');
        $oldStageName = $this->safe($oldStageName, 'неизвестный этап');
        return $this->withAuthor("Новый этап: {$stageName} из {$oldStageName}", $author);
    }

    public function attachedVacancy(string $fullName, string $vacancyName, ?string $author = null): string
    {
        $vacancyName = $this->safe($vacancyName, 'неизвестная вакансия');
        $fullName = $this->safe($fullName, 'неизвестный кандидат');
        return $this->withAuthor("кандидат {$fullName} привязка к вакансии {$vacancyName}", $author);
    }

    public function detachedVacancy(string $vacancyName, ?string $author = null): string
    {
        $vacancyName = $this->safe($vacancyName, 'неизвестная вакансия');
        return $this->withAuthor("открепление от {$vacancyName}", $author);
    }

    public function movedBetweenVacancies(string $fullName, string $fromVacancy, string $toVacancy, ?string $author = null): string
    {
        $fromVacancy = $this->safe($fromVacancy, 'неизвестная вакансия');
        $toVacancy = $this->safe($toVacancy, 'неизвестная вакансия');
        $fullName = $this->safe($fullName, 'неизвестный кандидат');
        return $this->withAuthor("перемещение кандидата {$fullName} из {$fromVacancy} в {$toVacancy}", $author);
    }

    private function withAuthor(string $text, ?string $author): string
    {
        $author = $this->clean($author);
        return $author ? "{$author}: " . $text : $text;
    }

    public function fieldUpdated(string $label, string $newValue, ?string $author = null): string
    {
        $label = $this->safe($label, 'Поле');
        $newValue = $this->safe($newValue, '—');
        return $this->withAuthor("Для поля \"{$label}\" установлено значение \"{$newValue}\"", $author);
    }

    public function fullNameChanged(string $old, string $new, ?string $author = null): string
    {
        return $this->withAuthor("Изменено ФИО: {$old} → {$new}", $author);
    }

    public function tagAdded(string $tag, ?string $author = null): string
    {
        return $this->withAuthor("Добавлен тег: #{$tag}", $author);
    }

    public function tagRemoved(string $tag, ?string $author = null): string
    {
        return $this->withAuthor("Удалён тег: #{$tag}", $author);
    }

    private function safe(?string $value, string $fallback): string
    {
        $value = $this->clean($value);
        return $value !== '' ? $value : $fallback;
    }

    private function clean(?string $value): string
    {
        return trim((string) $value);
    }
}
