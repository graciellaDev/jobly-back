<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\AttachmentCandidate;
use App\Models\Candidate;
use App\Models\CandidateSkill;
use App\Models\Skill;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Mail\Attachment;

class CandidateController extends Controller
{
    private int $defaultStage = 1;
    private int $defaultFunnel = 1;
    public function index(Request $request)
    {
        $customerId = $request->attributes->get('customer_id');
        $candidates = Candidate::with('customFields')->get();
//        where('customer_id', $customerId)
//
//            ->paginate();
//        ->get();

        return response()->json([
            'message' => 'Success',
            'data' => $candidates
        ]);
    }

    public function show(Request $request, int $id)
    {
        $customerId = $request->attributes->get('customer_id');
        $candidates = Candidate::where('customer_id', $customerId)->find($id);
        if (!empty($candidates)) {
            $candidates['tags'] = $candidates->tags;
            $candidates['customFields'] = $candidates->customFields;
            $candidates['skills'] = $candidates->attachments;
        } else {
            return response()->json([
                'message' => 'Кандидата с id = ' . $id . ' не существует',
                'data' => $candidates
            ], 404);
        }

        return response()->json([
            'message' => 'Success',
            'data' => $candidates
        ]);
    }

    public function update(Request $request, int $id)
    {
        $customerId = $request->attributes->get('customer_id');
        $candidate = Candidate::where('customer_id', $customerId)->with(['fields'])->find($id);
        if (empty($candidate)) {
            return response()->json([
                'message' => 'Кандидата с id = ' . $id . ' не существует',
                'data' => $candidate
            ], 404);
        }
        try {
            $data = $request->validate([
                'name' => 'nullable|string|min:3|max:255',
                'email' => 'nullable|string|max:50',
                'phone' => 'regex:/^\+7\d{10}$/',
                'job' => 'string|max:255',
                'location' => 'string|max:100',
                'description' => 'nullable|string|min:3|max:255',
                'education' => 'nullable|string|max:100',
                'link' => 'nullable|string|max:255',
                'vacancy' => 'nullable|string|max:100',
                'experience' => 'nullable|string|max:50',
                'telegram' => 'nullable|string|max:50',
                'skype' => 'nullable|string|max:50',
                'imagePath' => 'nullable|string|max:50',
                'resumePath' => 'nullable|string|max:50',
                'coverPath' => 'nullable|string|max:50',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        $candidate->update($data);

//        if (isset($request->fields)) {
//            var_dump($request->fields);
//            $fields= array_filter($request->fields);
//            $candidate->fields()->sync($fields);
//        }

        return response()->json([
                'message' => 'Success',
                'data' => $candidate
            ]);
    }

    public function create(Request $request)
    {
        try {
            $data = $request->validate([
                'firstname' => 'required|string|min:3|max:50',
                'surname' => 'required|string|min:3|max:50',
                'patronymic' => 'required|string|min:3|max:50',
                'email' => 'required|string|max:50',
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
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        $isExists = Candidate::where('phone', $request->phone)->exists();
        if ($isExists) {
            return response()->json([
                'massage' => 'Кандидат с номером телефона ' . $request->phone . ' уже существует'
            ], 409);
        }

        $isExists = Candidate::where('email', $request->email)->exists();
        if ($isExists) {
            return response()->json([
                'massage' => 'Кандидат с email ' . $request->email . ' уже существует'
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
                'massage' => 'Ошибка создания кандидата '
                    . $data['surname'] . ' '
                    . $data['firstname'] . ' '
                    . $data['patronymic']
            ], 500);
        }

        $customerId = $request->attributes->get('customer_id');
        $candidate->customer = $customerId;
        $candidate->vacancy = $candidate->vacancy_id;
        $candidate->stage = $candidate->stage_id;
        $candidate->makeHidden(['customer_id', 'stage_id', 'vacancy_id']);

        if(isset($request->skills)) {
            $candidate->skills()->attach($request->skills);
            $skills = Skill::whereIn('id', $request->skills)->get();
            $candidate->skills = $skills->toArray();
        }

        if(isset($request->tags)) {
            $tags = Tag::whereIn('id', $request->tags)->get();
            $candidate->tags = $tags->toArray();
        }
//
//        if(isset($request->customFields)) {
//            $candidate->customFields()->attach($request->customFields);
//        }
//
        if(isset($request->attachments)) {
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
}
