<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Http\Request;

class CandidateController extends Controller
{
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
                'name' => 'required|string|min:3|max:255',
                'email' => 'required|string|max:50',
                'phone' => 'regex:/^\+7\d{10}$/',
                'job' => 'string|max:255',
                'location' => 'string|max:100',
                'description' => 'required|string|min:3|max:255',
                'education' => 'required|string|max:100',
                'link' => 'nullable|string|max:255',
                'vacancy' => 'nullable|string|max:100',
                'experience' => 'required|string|max:50',
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

        try {
            $candidate = Candidate::create($data);
        } catch (\Throwable $th) {
            return response()->json([
                'massage' => 'Ошибка создания кандидата ' . $request->name
            ], 500);
        }

        $customerId = $request->attributes->get('customer_id');
        $candidate->customer = $customerId;

        if(isset($request->skills)) {
            $candidate->skills()->attach($request->skills);
        }

        if(isset($request->tags)) {
            $candidate->skills()->attach($request->tags);
        }

        if(isset($request->customFields)) {
            $candidate->customFields()->attach($request->customFields);
        }

        if(isset($request->attachments)) {
            $candidate->attachments()->attach($request->attachments);
        }

        return response()->json([
            'message' => 'Кандидат ' . $data['name'] . ' успешно создан',
            'data' => $candidate
        ]);
    }
}
