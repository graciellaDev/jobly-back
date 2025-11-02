<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerRelation;
use Illuminate\Http\Request;

class EmployeesController extends Controller
{
    public  function index(Request $request)
    {
        $customerId = $request->attributes->get('customer_id');
        $filterStatus = $request->get('status');

        $customer = Customer::find($customerId);
        if ($customer->role->id == CustomerController::$roleAdmin) {
            $clientIds = CustomerRelation::where('user_id', $customerId)->select(['customer_id']);
        } else {
            $admin = CustomerRelation::where('customer_id', $customerId)->select(['user_id'])->get()->first();

            if (empty($admin)) {
                return response()->json([
                    'message' => 'Success',
                    'data' => []
                ]);
            }
            $clientIds = CustomerRelation::where('user_id', $admin->user_id)->select(['customer_id']);
        }

        if (in_array($filterStatus, self::$validStatuses)) {
            $clientIds->where('status', $filterStatus);
        }
        $clientIds = $clientIds->pluck('customer_id')->toArray();

        $clients = Customer::with(['role'])
            ->whereIn('id', $clientIds)
            ->select(['id', 'name', 'role_id', 'email'])
            ->get();

        return response()->json([
            'message' => 'Success',
            'data' => $clients
        ]);
    }
}
