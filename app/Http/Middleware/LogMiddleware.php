<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Input;
use App\Models\ProgramLog;
use App\Models\Program;

class LogMiddleware
{

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $log=$request->get('log');
        if($log!=null){
            $programLog = new ProgramLog();
            $programLog['program_id']=$log['program_id'];
            $programLog['value']=$log;
            $programLog->save();
        }
        


        return $response;
    }
}
