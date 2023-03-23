<?php

use Assetplan\Dispatcher\Dispatcher;
use Illuminate\Support\Facades\Route;
use Assetplan\Dispatcher\DispatcherFacade;
use Illuminate\Http\Request;

Route::prefix('dispatcher')->middleware(['dispatcher-middleware'])->group(function () {
    Route::post('dispatch', function (Request $request, Dispatcher $dispatcher) {

        $request->validate([
            'job' => ['required', function ($input, $value, $fails) {
                if (!class_exists($value)) {
                    $fails('Job class does not exist');
                }

                if (!method_exists($value, 'handle')) {
                    $fails('Job class does not have a handle method');
                }

                if (!is_subclass_of($value, 'Illuminate\Contracts\Queue\ShouldQueue')) {
                    $fails('Job class does not implement Illuminate\Contracts\Queue\ShouldQueue');
                }
            }],
            'payload' => 'required',
            'queue' => 'sometimes',
            'signature' => 'required'
        ]);

        $queue = 'default';

        if ($request->filled('queue')) {
            $queue = $request->input('queue');
        }

        return response()->json(['jobId' => $dispatcher->receive($request->job, $request->payload, $queue)]);
    })->name('dispatcher.dispatch');
});
