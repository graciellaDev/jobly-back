<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Customer;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    private int $role = 5;
    public  function index(Request $request)
    {
        $customerId = $request->attributes->get('customer_id');
        $clients = Customer::where('role_id', $this->role)->select(['id', 'name', 'role_id'])->get();

        return response()->json([
            'message' => 'Success',
            'data' => $clients
        ]);
    }

    public function show(int $id)
    {
        $client = Customer::where('role_id', $this->role)->select(['id', 'name'])->find($id);

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
