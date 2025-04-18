<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Jobs\ActionStage;
use App\Jobs\MoveStage;
use App\Models\Candidate;
use App\Models\Stage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

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

    private array $response = [
        'message' => '',
        'status' => 200
    ];
    private bool $isAction;
    private int $time;
    private Candidate $candidate;
    private int $customerId;
    private $times = [
        0, 10, 30, 60, 180, 720, 1440
    ];

    public function init(Request $request)
    {
        $this->customerId = $request->attributes->get('customer_id');

        try {
            $data = $request->validate($this->validData);
        } catch (\Throwable $th) {
            $this->response['message'] = 'Ошибка валидации';
            $this->response['status'] = 422;
            return;
        }

        $this->candidate = Candidate::with('attachments')->where('customer_id', $this->customerId)->first();
        if (!$this->candidate) {
            $this->response['message'] = 'Кандидат с id ' . $data['candidate_id'] . ' не найден';
            $this->response['status'] = 409;
            return;
        }
        $arCandidate = $this->candidate->toArray();
        $condition = '=';
        $attachments = $this->candidate->attachments->pluck('link')->toArray();
        $this->isAction = false;

        switch ($data['conditions']) {
            case 'true':
                if ($data['field'] == 'attachments' && !isEmpty($attachments)) {
                    $this->isAction = true;
                } else {
                    if (!is_null($arCandidate[$data['field']])) {
                        $this->isAction = true;
                    }
                }
                break;
            case 'false':
                if ($data['field'] == 'attachments' && isEmpty($attachments)) {
                    $this->isAction = true;
                } else {
                    if (is_null($arCandidate[$data['field']])) {
                        $this->isAction = true;
                    }
                }
                break;
            case '=':
                if (!isset($data['value'])) {
                    $this->response['message'] = 'Поле значения не заполнено';
                    $this->response['status'] = 409;
                    return;
                }
                if ($data['field'] == 'attachments' && in_array($data['value'], $attachments)) {
                    $this->isAction = true;
                } else {
                    if ($arCandidate[$data['field']] == $data['value']) {
                        $this->isAction = true;
                    }
                }
                break;
            case '!=':
                if (!isset($data['value'])) {
                    $this->response['message'] = 'Поле значения не заполнено';
                    $this->response['status'] = 409;
                    return;
                }
                if ($data['field'] == 'attachments' && !in_array($data['value'], $attachments)) {
                    $this->isAction = true;
                } else {
                    if ($arCandidate[$data['field']] != $data['value']) {
                        $this->isAction = true;
                    }
                }
                break;
            case '>':
                if ($data['fieldType'] == 'number') {
                    if ($arCandidate[$data['field']] > intval($data['value'])) {
                        $this->isAction = true;
                    }
                }
                break;
            case '<':
                if ($data['fieldType'] == 'number') {
                    if ($arCandidate[$data['field']] < intval($data['value'])) {
                        $this->isAction = true;
                    }
                }
                break;
            case 'interval':
                if (!isset($data['from']) || !isset($data['to'])) {
                    $this->response['message'] = 'Нет одной из границ интервала';
                    $this->response['status'] = 409;
                    return;
                }
                if ($arCandidate[$data['field']] >= $data['from'] && $arCandidate[$data['field']] <= $data['to']) {
                    $this->isAction = true;
                }
                break;
        }

        if (!$this->candidate) {
            $this->response['message'] = 'Кандидат с id ' . $data['candidate_id'] . ' не найден';
            $this->response['status'] = 409;
            return;
        }

        if (isset($data['time']) && !in_array($data['time'], $this->times)) {
            $this->response['message'] = 'Неверное время';
            $this->response['status'] = 409;
            return;
        }
        $this->time = intval($data['time']);
    }
    public function invite(Request $request)
    {
        $this->init($request);

        if ($this->isAction) {
            ActionStage::dispatch($this->candidate)->delay(now()->addMinutes($this->time));
        }

        return response()->json([
            'message' => 'Триггер по перемещению кандидата '
                . $this->candidate->firstname . ' '
                . $this->candidate->surname . ' '
                . $this->candidate->patronymic
                . ' перемещен на этап Подходящие'
        ]);
    }

    public function moveStage(Request $request): JsonResponse
    {
        $this->init($request);
        try {
            $data = $request->validate(['stage_id' => 'required|numeric']);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации этапа воронки',
            ], 422);
        }
        $stage = Stage::find($data['stage_id']);

        if (is_null($stage)) {
            return response()->json([
                'message' => 'Этапа не существует',
            ], 409);
        }

        if (!$stage->fixed) {
            $funnel = Stage::find($data['stage_id'])->funnels()->where('customer_id', $this->customerId)->pluck('funnel_id');
            if (is_null($funnel)) {
                return response()->json([
                    'message' => 'Этапа  не существует',
                ], 409);
            }
        }

        if ($this->isAction) {
            MoveStage::dispatch($this->candidate, $stage)->delay(now()->addMinutes($this->time));
        }

        return response()->json([
            'message' => 'Триггер по перемещению кандидата '
                . $this->candidate->firstname . ' '
                . $this->candidate->surname . ' '
                . $this->candidate->patronymic . ' '
                . ' на этап ' .
                $stage->name . ' запущен',
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
