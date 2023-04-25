<?php

use Assetplan\Dispatcher\Dispatcher;
use Illuminate\Support\Facades\Route;
use Assetplan\Dispatcher\DispatcherFacade;
use Assetplan\Dispatcher\Queue\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;

Route::prefix('dispatcher')->middleware(['dispatcher-middleware'])->group(function () {
    Route::post('dispatch/batch', function (Request $request) {

        $request->validate([
            'batch' => 'required|array',
            'queue' => 'sometimes',
            'signature' => 'required',
            'payload.shouldBatch' => 'required|boolean',
        ]);


        $queue = 'default';

        if ($request->filled('queue')) {
            $queue = $request->input('queue');
        }

        $jobs = [];
        foreach ($request->batch as $job) {
            $job = Job::fromJson($job);
            $jobs[] = app()->make($job->name, $job->payload);
        }

        if ($request->payload['shouldBatch']) {
            return Bus::batch($jobs)->onQueue($queue)->dispatch();
        }

        $results = [];
        foreach ($jobs as $job) {

            $results[] = ['jobId' => dispatch($job)->onQueue($queue)];;
        }

        return response()->json($results);
    })->name('dispatcher.dispatch.batch');

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
