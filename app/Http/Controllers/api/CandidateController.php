<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\AttachmentCandidate;
use App\Models\Candidate;
use App\Models\CandidateCustomField;
use App\Models\CandidateSkill;
use App\Models\CandidateTag;
use App\Models\Skill;
use App\Models\Tag;
use App\Models\Stage;
use App\Models\Vacancy;
use App\Models\Customer;
use App\Models\CustomField;
use App\Services\EventService;
use App\Services\SystemEventMessageBuilder;
use App\Services\CandidateChangeEventService;
use Illuminate\Http\Request;
use \Illuminate\Http\JsonResponse;
use App\DTO\SystemEventData;
use App\Traits\ModelTrait;
class CandidateController extends Controller
{
    use ModelTrait;
    private int $defaultStage = 1;
    private int $defaultFunnel = 1;

    private array $validFields = [
        'firstname' => 'required|string|min:3|max:50',
        'surname' => 'nullable|string|max:50',
        'patronymic' => 'nullable|string|max:50',
        'email' => 'required|string|max:50',
        'age' => 'nullable|numeric',
        'phone' => 'regex:/^\+7\d{10}$/',
        'stage_id' => 'nullable|numeric',
        'location' => 'nullable|string|max:100',
        'quickInfo' => 'nullable|string|min:3|max:255',
        'education' => 'nullable|string|max:100',
        'link' => 'nullable|string|max:255',
        'vacancy_id' => 'nullable|integer',
        'experience' => 'nullable|string|max:50',
        'telegram' => 'nullable|string|max:50',
        'messengerMax' => 'nullable|string|max:80',
        'skype' => 'nullable|string|max:50',
        'icon' => 'nullable|string|max:50',
        'imagePath' => 'nullable|string|max:50',
        'isPng' => 'nullable|boolean',
        'resume' => 'nullable|string|max:50',
        'resumePath' => 'nullable|string|max:50',
        'coverPath' => 'nullable|string|max:50',
        'source' => 'nullable|string|max:50',
        'isReserve' => 'nullable|boolean',
        'customFields' => 'nullable|numeric',
    ];

    private array $validUpdateFields = [
        'firstname' => 'string|min:3|max:50',
        'surname' => 'nullable|string|max:50',
        'patronymic' => 'nullable|string|max:50',
        'email' => 'string|max:50|regex:/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
        'age' => 'nullable|numeric',
        'phone' => 'regex:/^\+7\d{10}$/',
        'stage_id' => 'nullable|numeric',
        'stage' => 'nullable|numeric',
        'location' => 'nullable|string|max:100',
        'quickInfo' => 'nullable|string|max:255',
        'education' => 'nullable|string|max:100',
        'link' => 'nullable|string|max:255',
        'vacancy_id' => 'nullable|integer',
        'experience' => 'nullable|string|max:50',
        'telegram' => 'nullable|string|max:80',
        'messengerMax' => 'nullable|string|max:80',
        'skype' => 'nullable|string|max:50',
        'icon' => 'nullable|string|max:50',
        'imagePath' => 'nullable|string|max:50',
        'isPng' => 'nullable|boolean',
        'resume' => 'nullable|string|max:50',
        'resumePath' => 'nullable|string|max:50',
        'coverPath' => 'nullable|string|max:50',
        'source' => 'nullable|string|max:50',
        'isReserve' => 'nullable|boolean',
        'customFields' => 'nullable|numeric',
        'tags' => 'nullable|array'
    ];

    private array $editFields = [
        'customer_id' => 'customer',
        // 'vacancy_id' => 'vacancy_id',
        'stage_id' => 'stage'
    ];

    private array $validSort = [
        'dateCreate'
    ];

    private array $validFilters = [
        'stage_id'
    ];

    public function __construct(
        private EventService $eventService,
        private SystemEventMessageBuilder $messageBuilder,
        private CandidateChangeEventService $candidateChangeEventService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $customerId = $request->attributes->get('customer_id');
        $sort = $request->get('sort');
        $filters = $request->get('filters');
        $candidates = Candidate::where('customer_id', $customerId);
        $perPage = $request->integer('per_page', 15);
        $perPage = max(1, min($perPage, 100));
        $perPage = $request->get('per_page') == 'all' ? Candidate::count() : $perPage;
        $filterVacancy = $request->get('vacancy_id');
        if (!empty($filterVacancy))
            $candidates = $candidates->where('vacancy_id', $filterVacancy);
        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                if (in_array($key, $this->validFilters)) {
                    switch ($key) {
                        case $this->validFilters[0]:
                            $candidates->where($key, $value);
                            break;
                    }
                }
            }
        }
        if (!empty($sort) && in_array($sort, $this->validSort)) {
            $sort = match ($sort) {
                'dateCreate' => 'created_at'
            };
            $asc = $request->get('asc') === '0' ? 'desc' : 'asc';
            $candidates = $candidates->orderBy($sort, $asc);
        }
        $candidates = $candidates->paginate($perPage);
        $candidates->getCollection()->transform(function ($candidate) {
            $this->replaceFields($this->editFields, $candidate);

            $customFields = CandidateCustomField::all()->where('candidate_id', $candidate->id)->pluck(['custom_field_id']);
            $candidate->customFields = CustomField::whereIn('id', $customFields)->get();

            $skills = CandidateSkill::all()->where('candidate_id', $candidate->id)->pluck('skill_id');
            $candidate->skills = Skill::whereIn('id', $skills)->get();

            $tags = CandidateTag::all()->where('candidate_id', $candidate->id)->pluck('tag_id');
            $candidate->tags = Tag::whereIn('id', $tags)->get();

            return $candidate;
        });

        return response()->json([
            'message' => 'Success',
            'data' => $candidates
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $customerId = $request->attributes->get('customer_id');
        $candidate = Candidate::with(['attachments'])->where('customer_id', $customerId)->find($id);
        if (!empty($candidate)) {
            $this->replaceFields($this->editFields, $candidate);

            $related = CandidateSkill::all()->where('candidate_id', $id)->pluck('skill_id');
            $candidate['skills'] = Skill::whereIn('id', $related)->get();

            $related = CandidateTag::all()->where('candidate_id', $id)->pluck('tag_id');
            $candidate['tags'] = Tag::whereIn('id', $related)->get();

            $related = CandidateCustomField::all()->where('candidate_id', $id)->pluck('custom_field_id');
            $candidate['customFields'] = CustomField::whereIn('id', $related)->get();
        } else {
            return response()->json([
                'message' => 'Кандидата с id = ' . $id . ' не существует',
                'data' => $candidate
            ], 404);
        }

        return response()->json([
            'message' => 'Success',
            'data' => $candidate
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $customerId = $request->attributes->get('customer_id');
        $customerName = Customer::find($customerId)?->name;

        $candidate = Candidate::with('attachments')->where('customer_id', $customerId)->find($id);
        if (empty($candidate)) {
            return response()->json([
                'message' => 'Кандидата с id = ' . $id . ' не существует',
                'data' => $candidate
            ], 404);
        }

        try {
            $data = $request->validate($this->validUpdateFields);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        if (isset($data['stage'])) {
            $data['stage_id'] = $data['stage'];
            unset($data['stage']);
        }

        // if (isset($data['vacancy'])) {
        //     $data['vacancy_id'] = intval($data['vacancy']);
        //     unset($data['vacancy']);
        // }
        $oldStageId = $candidate->stage_id;
        $oldVacancyId = $candidate->vacancy_id;
        $oldStageName = $stageName = Stage::find($candidate->stage_id)?->name ?? '';
        $old = $candidate->only(['firstname', 'surname', 'patronymic', 'phone', 'email']);
        $oldTags = $candidate->tags->pluck('name')->toArray();

        $candidate->update($data);

        $fullName = $candidate->surname ?? '';
        $fullName .= $candidate->firstname ? " {$candidate->firstname}" : '';
        $fullName .= $candidate->patronymic ? " {$candidate->patronymic}" : '';
        $fullName = trim($fullName);

        if ($oldStageId != $candidate->stage_id) {
            $stageName = Stage::find($candidate->stage_id)?->name ?? '';
            $text = $this->messageBuilder->movedStage($stageName, $oldStageName, $customerName);

            $this->eventService->createSystemEvent(new SystemEventData(
                candidateId: $candidate->id,
                vacancyId: $candidate->vacancy_id,
                previewText: $text,
                authorName: $customerName
            ));
        }

        if ($oldVacancyId != $candidate->vacancy_id) {
            $fromId = $oldVacancyId;
            $toId = $candidate->vacancy_id;

            $fromName = $fromId ? (Vacancy::find($fromId)?->name ?? '') : '';
            $toName = $toId ? (Vacancy::find($toId)?->name ?? '') : '';

            if (is_null($fromId) && !is_null($toId)) {
                $text = $this->messageBuilder->attachedVacancy($fullName, $toName, $customerName);
            } elseif (!is_null($fromId) && is_null($toId)) {
                $text = $this->messageBuilder->detachedVacancy($fromName, $customerName);
            } else {
                $text = $this->messageBuilder->movedBetweenVacancies($fullName, $fromName, $toName, $customerName);
            }

            $this->eventService->createSystemEvent(new SystemEventData(
                candidateId: $candidate->id,
                vacancyId: $toId,
                previewText: $text,
                authorName: $customerName
            ));
        }

        $this->replaceFields($this->editFields, $candidate);

        if (isset($request->skills)) {
            $related = array_map(fn($el) => intval($el), $request->skills);
            $candidate->skills()->detach();
            $candidate->skills()->attach($request->skills);
        } else {
            $related = CandidateSkill::all()->where('candidate_id', $id)->pluck('skill_id');
        }
        $candidate['skills'] = Skill::whereIn('id', $related)->get();
        if (isset($request->tags)) {
            if (empty($request->tags)) {
                $candidate->tags()->detach();
                $related = [];
            } else {
                $candidate->tags()->attach($request->tags);
                $related = CandidateTag::all()->where('candidate_id', $id)->pluck('tag_id');
            }
        } else {
            $related = CandidateTag::all()->where('candidate_id', $id)->pluck('tag_id');
        }

        $candidate['tags'] = Tag::whereIn('id', $related)->get();

        if (isset($request->customFields)) {
            $related = array_map(fn($el) => intval($el), $request->customFields);
            $fields = CandidateCustomField::all()->where('candidate_id', $id)->pluck('custom_field_id')->toArray();
            foreach ($request->customFields as $key => $field) {
                if (in_array($key, $field)) {
                    $candidate->customFields()->updateExistingPivot($key, $field);
                } else {
                    $candidate->customFields()->sync([$key => $fields]);
                }
            }
        } else {
            $related = CandidateCustomField::all()->where('candidate_id', $id)->pluck('custom_field_id');
        }
        $candidate['customFields'] = CustomField::whereIn('id', $related)->get();

        if (isset($request->attachments)) {
            $attachmentsData = [];
            foreach ($request->attachments as $item) {
                $attachmentsData[] = ['link' => $item];
            }
            $candidate->attachments()->delete();
            $attachments = [];
            foreach ($attachmentsData as $data) {
                $attachments[] = $candidate->attachments()->create($data)->toArray();
            }
            $candidate['attachments'] = $attachments;
            //            $candidate->attachments = $candidate->attachments()->saveMany($attachments);

        }

        $new = $candidate->only(['firstname', 'surname', 'patronymic', 'phone', 'email']);
        $newTags = $candidate->tags->pluck('name')->toArray();

        $this->candidateChangeEventService->handleChanges(
            $candidate,
            $old,
            $new,
            $oldTags,
            $newTags,
            $customerName
        );

        return response()->json([
            'message' => 'Success',
            'data' => $candidate
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $data = $request->validate($this->validFields);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        // $isExists = Candidate::where('phone', $request->phone)->exists();
        // if ($isExists) {
        //     return response()->json([
        //         'message' => 'Кандидат с номером телефона ' . $request->phone . ' уже существует'
        //     ], 409);
        // }

        // $isExists = Candidate::where('email', $request->email)->exists();
        // if ($isExists) {
        //     return response()->json([
        //         'message' => 'Кандидат с email ' . $request->email . ' уже существует'
        //     ], 409);
        // }

        $customerId = $request->attributes->get('customer_id');
        $customerName = Customer::find($customerId)?->name;

        $data['customer_id'] = $customerId;
        if (isset($data['vacancy'])) {
            $data['vacancy_id'] = intval($data['vacancy']);
            unset($data['vacancy']);
        }

        $data['stage_id'] = 1;

        try {
            $candidate = Candidate::create($data);

        } catch (\Throwable $th) {
            echo $th->getMessage();
            return response()->json([
                'message' => 'Ошибка создания кандидата '
                    . $data['surname'] . ' '
                    . $data['firstname'] . ' '
                    . $data['patronymic']
            ], 500);
        }

        $candidate->customer = $customerId;
        $this->replaceFields($this->editFields, $candidate);
        $candidate->makeHidden(['customer_id', 'stage_id', 'vacancy_id']);

        if (isset($request->skills)) {
            $candidate->skills()->attach($request->skills);
            $skills = Skill::whereIn('id', $request->skills)->get();
            $skills = $skills->toArray();
            $candidate->skills = $skills;
        }

        if (isset($request->tags)) {
            $tags = Tag::whereIn('id', $request->tags)->get();
            $candidate->tags = $tags->toArray();
        }

        if (isset($request->customFields)) {
            $customFields = CustomField::whereIn('id', $request->customFields)->get();
            $candidate->customFields = $customFields->toArray();
        }

        if (isset($request->attachments)) {
            if (!empty($request->attachments)) {
                $attachments = [];
                foreach ($request->attachments as $item) {
                    $attachments[] = new AttachmentCandidate(['link' => $item, 'candidate_id', $candidate->id]);
                }
                $attachments = $candidate->attachments()->saveMany($attachments);
                $candidate->attachments = $attachments;
            }
        }

        $fullName = $candidate->surname ?? '';
        $fullName .= $candidate->firstname ? " {$candidate->firstname}" : '';
        $fullName .= $candidate->patronymic ? " {$candidate->patronymic}" : '';
        $fullName = trim($fullName);
        $text = $this->messageBuilder->createdCandidate($fullName, $customerName);

        $this->eventService->createSystemEvent(new SystemEventData(
            candidateId: $candidate->id,
            vacancyId: null,
            previewText: $text,
            authorName: $customerName
        ));

        if (isset($data['vacancy_id'])) {
            $newVacancyId = intval($data['vacancy_id']);
            $newVacancyName = Vacancy::find($newVacancyId)?->name ?? '';
            $text = $this->messageBuilder->attachedVacancy($fullName, $newVacancyName, $customerName);

            $this->eventService->createSystemEvent(new SystemEventData(
                candidateId: $candidate->id,
                vacancyId: $data['vacancy_id'],
                previewText: $text,
                authorName: $customerName
            ));
        }

        return response()->json([
            'message' => "Кандидат {$fullName} успешно создан",
            'data' => $candidate
        ]);
    }

    public function delete(Request $request, int $id): JsonResponse
    {
        $customerId = $request->attributes->get('customer_id');

        $candidate = Candidate::where('customer_id', $customerId)->find($id);
        if ($candidate) {
            $name = $candidate->surname . ' ' . $candidate->firstname . ' ' . $candidate->patronymic;
            $candidate->attachments()->delete();
            $candidate->delete();

            return response()->json([
                'message' => 'Вакансия ' . $name . ' успешно удалена'
            ]);
        } else {
            return response()->json([
                'message' => 'Вакансия не найдена'
            ], 404);
        }
    }

    public function reply(Request $request): JsonResponse
    {

        try {
            $data = $request->validate($this->validFields);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }
        return response()->json([
            'message' => 'Отклик по вакансии'
        ]);
    }

    public function detachTag(Request $request, int $id, int $tag): JsonResponse
    {
        $candidate = Candidate::find($id);
        if ($candidate) {
            $candidate->tags()->detach($tag);
            return response()->json([
                'message' => 'Тег у кандидата успешно удален'
            ]);
        } else {
            return response()->json([
                'message' => 'Кандидат не найден'
            ], 404);
        }
    }
}
