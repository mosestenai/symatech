<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckActivation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //check if user has been blocked
        if(auth()->check() && (auth()->user()->activatedstatus == 0)){
           
            return response()->json(['responseCode' => 200, 'error' => 'Unauthorised']);

    }

    return $next($request);
    }
}
