<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Input;

class WxloginMiddleware
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
        //$value = $request->Header('AdminToken');
        $t = $request->header('Time');
        $s = $request->header('Sha');

        $curtime = time();

        if (!empty($t) && !empty($s) && $curtime-$t < 300 && sha1($key.$t) == $s) {
            return $next($request);
        }
        else {
            $ret = array('success'=>3, 'note'=>'sha1 failed' );
            return json_encode($ret);;
        }
    }
}
