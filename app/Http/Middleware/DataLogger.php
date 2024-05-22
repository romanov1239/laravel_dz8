<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use App\Models\Log;

class DataLogger
{
    private $start_time;

    /**
     * Handle an incoming request.
     *
     * @param Illuminate\Http\Request $request
     * @param Closure $next
     * @return Illuminate\Http\Response
     */
    public function handle(Request $request, Closure $next)
    {
        $this->start_time = microtime(true);
        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     *
     * @param Illuminate\Http\Request $request
     * @param Illuminate\Http\Response $response
     * @return void
     */
    public function terminate(Request $request, Response $response)
    {
        if (env('API_DATALOGGER', true)) {
            $endTime = microtime(true);
            $duration = round($endTime - $this->start_time);

            if (env('APP_DATALOGGER_USE_DB', true)) {
                $log = new Log();
                $log->time = gmdate("Y-m-d H:i:s");
                $log->duration = $duration;
                $log->ip = $request->ip();
                $log->url = $request->fullUrl();
                $log->method = $request->method();
                $log->input = $request->getContent();
                $log->save();
            } else {
                $filename = 'api_datalogger_' . date('d-m-y') . '.log';
                $dataToLog = 'Time: ' . gmdate("F j, Y, g:i a") . PHP_EOL;
                $dataToLog .= 'Duration: ' . $duration . PHP_EOL;
                $dataToLog .= 'IP Address: ' . $request->ip() . PHP_EOL;
                $dataToLog .= 'URL: ' . $request->fullUrl() . PHP_EOL;
                $dataToLog .= 'Method: ' . $request->method() . PHP_EOL;
                $dataToLog .= 'Input: ' . $request->getContent() . PHP_EOL;
                $logPath = storage_path('logs' . DIRECTORY_SEPARATOR . $filename);
                File::append($logPath, $dataToLog . "\n" . str_repeat("-", 20) . "\n\n");
            }
        }
    }
}
