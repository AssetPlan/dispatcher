<?php

use Assetplan\Dispatcher\Dispatcher;
use Assetplan\Dispatcher\Rules\IsIlluminateJob;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::prefix('dispatcher')->middleware(['dispatcher-middleware'])->group(function () {
    Route::post('dispatch/batch', function (Request $request, Dispatcher $dispatcher) {

        $request->validate([
            'batch' => 'required|array',
            'queue' => 'sometimes',
            'signature' => 'required',
            'payload.shouldBatch' => 'required|boolean',
            'batch.*.name' => ['required', new IsIlluminateJob],
        ]);


        $queue = 'default';

        if ($request->filled('queue')) {
            $queue = $request->input('queue');
        }

        $results = $dispatcher->receiveBatch($request->batch, $queue, $request->payload['shouldBatch']);

        return response()->json($results);
    })->name('dispatcher.dispatch.batch');

    Route::post('dispatch', function (Request $request, Dispatcher $dispatcher) {

        $request->validate([
            'job' => ['required', new IsIlluminateJob],
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
