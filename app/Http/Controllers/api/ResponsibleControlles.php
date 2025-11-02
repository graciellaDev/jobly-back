<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerRelation;
use Illuminate\Http\Request;

class ResponsibleControlles extends Controller
{
    public  function index(Request $request)
    {
        $customerId = $request->attributes->get('customer_id');
        $customer = Customer::find($customerId);
        $responsibles = [];

        if ($customer && $customer->role_id == CustomerController::$roleAdmin) {
            $users = CustomerRelation::where('user_id', $customerId)
                ->pluck('customer_id')
                ->toArray();
            if (count($users)) {
                $users[] = $customerId;
                $responsibles = Customer::whereIn('id', $users)
                    ->whereNot('role_id', CustomerController::$roleClient)
                    ->with('role')
                    ->select(['id', 'name', 'role_id'])
                    ->get();
            }
        }

        if ($customer->role_id == CustomerController::$roleRecruiter
            || $customer->role_id == CustomerController::$roleClient) {
            $admin = CustomerRelation::where('customer_id', $customerId)->pluck('user_id')->toArray();
            if (count($admin)) {
                $users = CustomerRelation::where('user_id', $admin[0])->pluck('customer_id')->toArray();
                $users[] = $admin[0];
                $responsibles = Customer::whereIn('id', $users)
                    ->whereNot('role_id', CustomerController::$roleClient)
                    ->with('role')
                    ->select(['id', 'name', 'role_id'])
                    ->get();
            }
        }

        return response()->json([
            'message' => 'Success',
            'data' => $responsibles
        ]);
    }
}
