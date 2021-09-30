<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class T5Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if( 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9' === $request->bearerToken()){
            return $next($request);
        }
        else{
            return redirect('home');
        }
       
    }
}
