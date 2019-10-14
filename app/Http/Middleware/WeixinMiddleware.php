<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Input;


class WeixinMiddleware
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

        $key = config('rjz.AppSecret');
        $t = $request->header('Time');
        $s = $request->header('Sha');
        $curtime = time();
        


        if (!empty($token) && !empty($t) && !empty($s) && $curtime-$t < 300 && sha1($key.$t) == $s) {
            echo "{'success':true}";
            return $next($request);

        }
        else {
            return $next($request);
            echo "{'success':false}";

        }
    }
}
