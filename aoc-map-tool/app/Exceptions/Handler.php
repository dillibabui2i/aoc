<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Database\QueryException;
use Illuminate\Session\TokenMismatchException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * @param Throwable $exception
     * @return void
     * @throws Throwable
     */
    public function report(Throwable $exception): void
    {
        try {
            if (env('APP_ENV') === 'production') {
                if ($this->isDeadlock($exception) || app()->runningInConsole()) {
                    $message = [
                        "@type" => "MessageCard",
                        "@context" => "http://schema.org/extensions",
                        "summary" => "Deadlock detected",
                        "sections" => [
                            [
                                "activityTitle" => "⚠️ Deadlock detected",
                                "facts" => [
                                    ["name" => "Message", "value" => $exception->getMessage()],
                                    ["name" => "Context", "value" => $this->getExecutionContext()]
                                ]
                            ]
                        ]
                    ];

                    $ch = curl_init(env('TEAMS_WEBHOOK'));
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    $response = curl_exec($ch);

                    if ($response === false) {
                        echo "Error: " . curl_error($ch);
                    } else {
                        echo "Message sent successfully!";
                    }

                    curl_close($ch);
                }
            }
        } catch (Exception $exception) {
            parent::report($exception);
        }

        parent::report($exception);
    }

    /**
     * @param $request
     * @param Throwable $exception
     * @return mixed
     * @throws Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof TokenMismatchException) {
            $route = $request->route();
            \Log::info('route ExceptionHandler =' . $route->getName());
            if ($route && strpos($route->getName(), 'nthlanding') !== false) {
                if ($route->getName() == 'nthlanding.saveNthCaseInterest') {
                    return response()->json(
                        ['status' => false, 'message' => 'Something went wrong. Please try again later.']
                    );
                }
                Log::info('Inside render function of Handler class of ExceptionHandler');

                return redirect()->back()->withInput()->with(
                    'error',
                    'Your session has expired. Please try again.'
                );
            }

            return redirect('/login');
        }

        return parent::render($request, $exception);
    }

    protected function isDeadlock(Throwable $e): bool
    {
        return str_contains($e->getMessage(), 'Deadlock found')
            || str_contains($e->getMessage(), 'Serialization failure')
            || ($e instanceof QueryException && $e->getCode() === '40001');
    }

    protected function getExecutionContext(): string
    {
        if (app()->runningInConsole()) {
            return 'command: ' . implode(' ', $_SERVER['argv'] ?? []);
        }

        if (request()->isMethodCacheable()) {
            return 'http: ' . request()->method() . ' ' . request()->fullUrl();
        }

        return 'unknown';
    }
}
