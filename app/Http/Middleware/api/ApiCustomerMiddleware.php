<?php

namespace App\Http\Middleware\api;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiCustomerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->hasCookie('auth_user')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        } else {
            $authToken = request()->cookie('auth_user');
            $customer = Customer::where('auth_token', $authToken)->first();
            if (empty($customer)) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }
        }
        $request->attributes->set('customer_id', $customer->id);

        return $next($request);
    }
}
