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
use Illuminate\Http\Request;
use App\Models\CustomField;
use App\Traits\ModelTrait;
use \Illuminate\Http\JsonResponse;
class CandidateController extends Controller
{
    use ModelTrait;
    private int $defaultStage = 1;
    private int $defaultFunnel = 1;

    private array $validFields = [
        'firstname' => 'required|string|min:3|max:50',
        'surname' => 'required|string|min:3|max:50',
        'patronymic' => 'required|string|min:3|max:50',
        'email' => 'required|string|max:50',
        'age' => 'nullable|numeric',
        'phone' => 'regex:/^\+7\d{10}$/',
        'stage_id' => 'nullable|numeric',
        'location' => 'string|max:100',
        'quickInfo' => 'string|min:3|max:255',
        'education' => 'string|max:100',
        'link' => 'nullable|string|max:255',
        'vacancy' => 'required|nullable|string|max:100',
        'experience' => 'string|max:50',
        'telegram' => 'nullable|string|max:50',
        'skype' => 'nullable|string|max:50',
        'icon' => 'nullable|string|max:50',
        'imagePath' => 'nullable|string|max:50',
        'isPng' => 'nullable|boolean',
        'resume' => 'nullable|string|max:50',
        'resumePath' => 'nullable|string|max:50',
        'coverPath' => 'nullable|string|max:50',
        'customFields' => 'nullable|numeric',
    ];

    private array $validUpdateFields = [
        'firstname' => 'string|min:3|max:50',
        'surname' => 'string|min:3|max:50',
        'patronymic' => 'string|min:3|max:50',
        'email' => 'string|max:50',
        'age' => 'nullable|numeric',
        'phone' => 'regex:/^\+7\d{10}$/',
        'stage_id' => 'nullable|numeric',
        'location' => 'string|max:100',
        'quickInfo' => 'string|min:3|max:255',
        'education' => 'string|max:100',
        'link' => 'nullable|string|max:255',
        'vacancy' => 'nullable|string|max:100',
        'experience' => 'string|max:50',
        'telegram' => 'nullable|string|max:50',
        'skype' => 'nullable|string|max:50',
        'icon' => 'nullable|string|max:50',
        'imagePath' => 'nullable|string|max:50',
        'isPng' => 'nullable|boolean',
        'resume' => 'nullable|string|max:50',
        'resumePath' => 'nullable|string|max:50',
        'coverPath' => 'nullable|string|max:50',
        'customFields' => 'nullable|numeric',
    ];

    private array $editFields = [
        'customer_id' => 'customer',
        'vacancy_id' => 'vacancy',
        'stage_id' => 'stage'
    ];

    private array $validSort = [
        'dateCreate'
    ];

    public function index(Request $request): JsonResponse
    {
        $customerId = $request->attributes->get('customer_id');
        $sort = $request->get('sort');
        $candidates = Candidate::where('customer_id', $customerId);
        if (!empty($sort) && in_array($sort, $this->validSort)) {
            $sort = match ($sort) {
                'dateCreate' => 'created_at'
            };
            $asc = $request->get('asc') === '0' ? 'desc' : 'asc';
            $candidates = $candidates->orderBy($sort, $asc);
        }
        $candidates = $candidates->paginate();
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

        $candidate->update($data);
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
            $related = array_map(fn($el) => intval($el), $request->tags);
            $candidate->tags()->detach();
            $candidate->tags()->attach($request->tags);
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

        $isExists = Candidate::where('phone', $request->phone)->exists();
        if ($isExists) {
            return response()->json([
                'message' => 'Кандидат с номером телефона ' . $request->phone . ' уже существует'
            ], 409);
        }

        $isExists = Candidate::where('email', $request->email)->exists();
        if ($isExists) {
            return response()->json([
                'message' => 'Кандидат с email ' . $request->email . ' уже существует'
            ], 409);
        }

        $customerId = $request->attributes->get('customer_id');

        $data['customer_id'] = $customerId;
        $data['vacancy_id'] = intval($data['vacancy']);
        unset($data['vacancy']);

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

        $customerId = $request->attributes->get('customer_id');
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

        return response()->json([
            'message' => 'Кандидат '
                . $data['surname'] . ' '
                . $data['firstname'] . ' '
                . $data['patronymic'] . ' успешно создан',
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
}
