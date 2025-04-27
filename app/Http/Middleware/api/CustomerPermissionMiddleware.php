<?php

namespace App\Http\Middleware\api;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
       $id = $request->attributes->get('customer_id');
       $customer = Customer::find($id);

       if ($customer->role_id != 1) {
           return response()->json([
               'message' => 'Нет прав'
           ], 403);
       }

        return $next($request);
    }
}
