<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Jobs\ActionStage;
use App\Mail\register\Success;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ActionController extends Controller
{
    private $validData = [
        'candidate_id' => 'required|numeric',
        'field' => 'required|in:phone,age,location,link,attachments',
        'fieldType' => 'required|string',
        'value' => '',
        'from' => 'numeric',
        'to' => 'numeric',
        'conditions' => 'required|in:true,false,=,!=,>,<,interval',
        'time' => 'numeric|min:1|max:1440'
    ];

    private $fieldConditions = [
        'string' => [
            'true',
            'false',
            'more',
            'less'
        ],
        'number' => [
            'true',
            'false',
            'more',
            'less',
            'interval'
        ]
    ];
    private $times = [
        1, 10, 30, 60, 180, 720, 1440
    ];
    public function invite(Request $request)
    {
        $customer = $request->attributes->get('customer_id');
        try {
            $data = $request->validate($this->validData);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        $candidate = Candidate::with('attachments')->where('customer_id', $customer)->first();
        if (!$candidate) {
            return response()->json([
                'message' => 'Кандидат с id ' . $data['candidate_id'] . ' не найден',
            ], 409);
        }
        $arCandidate = $candidate->toArray();
        $condition = '=';
        $attachments = $candidate->attachments->pluck('link')->toArray();
        $isAction = false;

        switch ($data['conditions']) {
            case 'true':
                if ($data['field'] == 'attachments' && !isEmpty($attachments)) {
                    $isAction = true;
                } else {
                    if (!is_null($arCandidate[$data['field']])) {
                        $isAction = true;
                    }
                }
                break;
            case 'false':
                if ($data['field'] == 'attachments' && isEmpty($attachments)) {
                    $isAction = true;
                } else {
                    if (is_null($arCandidate[$data['field']])) {
                        $isAction = true;
                    }
                }
                break;
            case '=':
                if (!isset($data['value'])) {
                    return response()->json([
                        'message' => 'Поле значения не заполнено'
                    ], 409);
                }
                if ($data['field'] == 'attachments' && in_array($data['value'], $attachments)) {
                    $isAction = true;
                } else {
                    if ($arCandidate[$data['field']] == $data['value']) {
                        $isAction = true;
                    }
                }
                break;
            case '!=':
                if (!isset($data['value'])) {
                    return response()->json([
                        'message' => 'Поле значения не заполнено'
                    ], 409);
                }
                if ($data['field'] == 'attachments' && !in_array($data['value'], $attachments)) {
                    $isAction = true;
                } else {
                    if ($arCandidate[$data['field']] != $data['value']) {
                        $isAction = true;
                    }
                }
                break;
            case '>':
                if ($data['fieldType'] == 'number') {
                    if ($arCandidate[$data['field']] > intval($data['value'])) {
                        $isAction = true;
                    }
                }
                break;
            case '<':
                if ($data['fieldType'] == 'number') {
                    if ($arCandidate[$data['field']] < intval($data['value'])) {
                        $isAction = true;
                    }
                }
                break;
            case 'interval':
                if (!isset($data['from']) || !isset($data['to'])) {
                    return response()->json([
                        'message' => 'Нет одной из границ интервала'
                    ], 409);
                }
                if ($arCandidate[$data['field']] >= $data['from'] && $arCandidate[$data['field']] <= $data['to']) {
                    $isAction = true;
                }
                break;
        }

        if (!$candidate) {
            return response()->json([
                'message' => 'Кандидат с id ' . $data['candidate_id'] . ' не найден',
            ], 409);
        }

        if (isset($data['time']) && !in_array($data['time'], $this->times)) {
            return response()->json([
                'message' => 'Неверное время',
            ], 409);
        }

        if ($isAction) {
//            ActionStage::dispatch($candidate)->delay(Carbon::now()->addMinutes(intval($data['time'])));
            ActionStage::dispatch($candidate)->delay(now()->addMinutes(intval($data['time'])));
        }

        return response()->json([
            'message' => 'Success',
        ]);
    }

    public function show()
    {
        ActionStage::dispatch()->delay(Carbon::now()->addMinutes(1));

        return response()->json([
            'message' => 'Еще одно задание отправлено в очередь',
        ]);
    }
}
