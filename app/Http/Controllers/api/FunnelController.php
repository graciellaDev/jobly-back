<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\CustomerFunnel;
use App\Models\Funnel;
use App\Models\FunnelStage;
use App\Models\Stage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FunnelController extends Controller
{
    public function index(Request $request) {
        $customerId = $request->attributes->get('customer_id');
        $defaultFunnel = Funnel::where('fixed', 1)->get()->toArray();
        $customers = CustomerFunnel::where('customer_id', $customerId)->pluck('funnel_id')->toArray();
        $funnel = Funnel::find($customers)->toArray();
        $funnel = array_merge($defaultFunnel, $funnel);

        return response()->json([
            'message' => 'Success',
            'data' => $funnel
        ]);
    }

    public function create(Request $request)
    {
        $customerId = $request->attributes->get('customer_id');
        try {
            $data = $request->validate([
                'name' => 'required|string|min:3|max:255'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }
        $customers = CustomerFunnel::where('customer_id', $customerId)->pluck('funnel_id');
        try {
            $funnel = Funnel::where('name', $data['name'])->first();
            $defaultFunnel = Funnel::where('fixed', 1)->get()->toArray();
            if (empty($funnel)) {
                $funnel = Funnel::create($data);
                $funnel->customers()->attach([$customerId]);
                $funnel = [$funnel->toArray()];
            } else {
                $isFunnels = CustomerFunnel::where('customer_id', $customerId)->pluck('funnel_id')->toArray();
                if (empty($isFunnels)) {
                    $funnel->customers()->attach([$customerId]);
                    $isFunnels[0] = $funnel->id;
                } else {
                    return response()->json([
                        'message' => 'Воронка ' . $funnel->name. ' уже создана'
                    ], 409);
                }
                $funnel = Funnel::find($isFunnels)->toArray();
            }
            $funnel = array_merge($defaultFunnel, $funnel);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Воронка ' . $request->name . ' уже создана',
            ], 409);
        }

        return response()->json([
            'message' => 'Воронка ' . $request->name . ' успешно создана',
            'data' => $funnel
        ]);
    }

    public function delete (Request $request, int $id) {
        $customerId = $request->attributes->get('customer_id');
        $funnel = Funnel::find($id);

        if (!empty($funnel)) {
            $name = $funnel->name;

            if ($funnel->fixed) {
                return response()->json([
                    'message' => 'Ворноку ' . $name . ' удалять нельзя'
                ], 409);
            }

            $customers = CustomerFunnel::where('funnel_id', $id)->pluck('customer_id')->toArray();
            if (!empty($customers) && in_array($customerId, $customers)) {
                if (count($customers) == 1) {
                    $funnel->delete();
                } else {
                    $funnel->customers()->detach($customerId);
                }
            } else {
                return response()->json([
                    'message' => 'Воронка не найдена'
                ], 404);
            }

//            $funnel->delete();

            return response()->json([
                'massage' => 'Воронка ' . $name . ' успешно удалена'
            ]);
        } else {
            return response()->json([
                'message' => 'Воронка не найдена'
            ], 404);
        }
    }

    public function createStage(Request $request, int $id) {
        $customer_id = $request->attributes->get('customer_id');
        try {
            $data = $request->validate([
                'name' => 'required|string|min:3|max:255'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        $stages = Stage::where('name', $request->name)->where('fixed', true)->get();
        if (!$stages->isEmpty()) {
            return response()->json([
                'message' => 'Этап с названием' . $request->name . ' нельзя создавать, он существует и фиксирован'
            ], 409);
        }

        $funnel = Funnel::find($id);
        if (empty($funnel)) {
            return response()->json([
                'message' => 'Воронка не найдена'
            ], 404);
        }
        $stages = $funnel->stages()->where('customer_id', $customer_id)->pluck('name')->toArray();
        if (!is_null($stages) && in_array($request->name, $stages)) {
            return response()->json([
                'message' => 'Этап ' . $request->name . ' уже существует в воронке ' . $funnel->name
            ], 409);
        }

        $stages = Stage::create($data);
        $funnel->stages()->sync([$stages->id => ['customer_id' => $customer_id]]);

        return response()->json([
            'message' => 'Этап ' . $request->name . ' в воронке ' . $funnel->name . ' успешно создан',
            'data' => $stages
        ]);
    }

    public function deleteStage(Request $request, int $id) {
        $customer_id = $request->attributes->get('customer_id');
        try {
            $data = $request->validate([
                'stage_id' => 'required|numeric'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        $funnel = Funnel::find($id);
        if (empty($funnel)) {
            return response()->json([
                'message' => 'Воронка не найдена'
            ], 404);
        }

        $stage = Stage::find($request->stage_id);
        if (empty($stage)) {
            return response()->json([
                'message' => 'Этап с идентификатором ' . $request->stage_id . ' в воронке ' . $funnel->name . ' не найден'
            ], 404);
        }
        $stages = $funnel->stages()->where('customer_id', $customer_id)->where('stage_id', $data['stage_id'])->pluck('stage_id')
            ->toArray();
        if (empty($stages)) {
            return response()->json([
                'message' => 'Этап с идентификатором ' . $request->stage_id . ' в воронке ' . $funnel->name . ' не найден'
            ], 404);
        }
        $stageName = $stage->name;
        $funnel->stages()->detach();
        $stage->delete();

        return response()->json([
            'message' => 'Этап ' . $stageName . ' в воронке ' . $funnel->name . ' успешно удален',
        ]);
    }

    public function indexStage(Request $request, int $id) {
        $isFunnel = Funnel::find($id);
        if (empty($isFunnel)) {
            return response()->json([
                'message' => 'Воронка не найдена'
            ], 404);
        }

        $customer_id = $request->attributes->get('customer_id');
        $fixStages = Stage::all()->where('fixed', 1)->toArray();

        $funnel = FunnelStage::where('customer_id', $customer_id)->where('funnel_id', $id)->pluck('stage_id')
            ->toArray();
        $stages = Stage::whereIn('id', $funnel)->get()->toArray();


        return response()->json([
            'message' => 'Success',
            'data' => array_merge($fixStages, $stages)
        ]);
    }
}
