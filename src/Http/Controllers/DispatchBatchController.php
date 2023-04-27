<?php

namespace Assetplan\Dispatcher\Http\Controllers;

use Assetplan\Dispatcher\Dispatcher;
use Assetplan\Dispatcher\Rules\IsIlluminateJob;
use Illuminate\Http\Request;

class DispatchBatchController
{
    public function __invoke(Request $request, Dispatcher $dispatcher)
    {
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
    }
}
