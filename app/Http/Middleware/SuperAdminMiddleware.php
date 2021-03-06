<?php

namespace App\Http\Middleware;

use Closure;
use App\User as User;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $header = $request->header();
        $check = User::where('api_token',$header['api-token'])->first();
        if($check){
            if($check->role == 'super'){
                return $next($request);
            }
            return response('No access', 403);
        }
        else{
            return ('unathorized 401');
        }
    }
}
