<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Customer;
use App\Models\Event;
use App\Models\EventChat;
use App\Services\EventFeedService;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(private EventFeedService $eventFeedService)
    {
    }

    public function indexByCandidate($candidateId, Request $request)
    {
        $customerId = $request->attributes->get('customer_id');
        $this->checkAccess($customerId, $candidateId);

        $data = $this->eventFeedService->getFeed($candidateId);
        return response()->json(['data' => $data]);

    }

    public function indexByCandidateVacancy($candidateId, $vacancyId, Request $request)
    {
        $customerId = $request->attributes->get('customer_id');
        $this->checkAccess($customerId, $candidateId);

        $data = $this->eventFeedService->getFeed($candidateId, $vacancyId);
        return response()->json(['data' => $data]);
    }

    public function createChatMessage($candidateId, $vacancyId, Request $request)
    {
        $customerId = $request->attributes->get('customer_id');
        $this->checkAccess($customerId, $candidateId);

        $data = $request->validate([
            'provider' => 'required|string|in:jobly,hh', // добавлять внешние сервисы по мере добавления
            'content' => 'required|string|min:1',
        ]);

        $provider = $data['provider'] ?? 'jobly';

        $customerName = Customer::find($customerId)?->name;

        $event = Event::create([
            'candidate_id' => $candidateId,
            'vacancy_id' => $vacancyId,
            'type' => Event::TYPE_CHAT,
            'occurred_at' => now(),
            'author_name' => $customerName,
            'channel' => $provider,
            'direction' => EventChat::DIRECTION_OUT,
        ]);

        $chat = EventChat::create([
            'event_id' => $event->id,
            'provider' => $provider,
            'direction' => EventChat::DIRECTION_OUT,
            'author_name' => $customerName ?? 'Работодатель',
            'content' => $data['content'],
            'status' => in_array($provider, ['hh']) // добавлять внешние сервисы по мере добавления
                ? EventChat::STATUS_PENDING
                : EventChat::STATUS_SENT,
            'external_message_id' => null,
            'external_chat_id' => null,
            'sync_error' => null,
        ]);

        if (in_array($provider, ['hh'])) { // добавлять внешние сервисы по мере добавления
            // dispatch Job для отправки во внешний сервис
        }

        return response()->json([
            'message' => 'queued',
            'event_id' => $event->id,
            'status' => $chat->status,
        ]);
    }

    private function checkAccess($customerId, $candidateId)
    {
        $exists = Candidate::where('customer_id', $customerId)
            ->where('id', $candidateId)
            ->exists();

        if (!$exists) {
            return response()->json(['message' => 'Кандидат не найден'], 404);
        }
        exit;
    }
}
