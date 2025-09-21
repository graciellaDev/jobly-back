<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerRelation;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public  function index(Request $request)
    {
        $customerId = $request->attributes->get('customer_id');
        $clientIds = array_column(
            CustomerRelation::where('user_id', $customerId)->select(['customer_id'])->get()
                ->toArray(),
            'customer_id'
        );

        $clients = Customer::with(['role'])
            ->where('role_id', CustomerController::$roleClient)
            ->whereIn('id', $clientIds)
            ->select(['id', 'name', 'role_id', 'email'])
            ->get();

        return response()->json([
            'message' => 'Success',
            'data' => $clients
        ]);
    }

    public function show(Request $request,  int $id)
    {
        $customerId = $request->attributes->get('customer_id');
        $clientIds = array_column(
            CustomerRelation::where('user_id', $customerId)->select(['customer_id'])->get()->toArray(),
            'customer_id'
        );
        $client = Customer::where('role_id', CustomerController::$roleClient)->whereIn('id', $clientIds)->select(['id', 'name'])->find($id);

        if (empty($client)) {
            return response()->json([
                'message' => 'Клиент с id = ' . $id . ' не найден'
            ], 404);
        }

        return response()->json([
            'message' => 'Success',
            'data' => $client
        ]);
    }
}
