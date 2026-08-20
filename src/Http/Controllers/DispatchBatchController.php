<?php

namespace Assetplan\Dispatcher\Http\Controllers;

use Assetplan\Dispatcher\Dispatcher;
use Assetplan\Dispatcher\Rules\IsIlluminateJob;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DispatchBatchController
{
    public function __invoke(Request $request, Dispatcher $dispatcher)
    {
        $request->validate([
            'batch' => 'required|array',
            'queue' => 'sometimes|required|string',
            'signature' => 'required|string',
            'payload.shouldBatch' => 'required|boolean',
            'batch.*.name' => ['required', new IsIlluminateJob],
            'batch.*.payload' => 'required|array',
            'batch.*.queue' => 'nullable|string',
            'batch.*.delay' => ['nullable', function (string $attribute, mixed $value, \Closure $fail) {
                if (! Dispatcher::isValidWireDelay($value)) {
                    $fail('The '.$attribute.' must be non-negative integer seconds or an ISO 8601 date.');
                }
            }],
        ]);

        $queue = $request->input('queue', 'default');
        if ($request->input('payload.shouldBatch') && collect($request->input('batch'))->contains(
            fn (array $job) => isset($job['queue']) && $job['queue'] !== $queue
        )) {
            throw ValidationException::withMessages([
                'batch' => 'All jobs in a Laravel batch must use the batch queue.',
            ]);
        }

        return response()->json($dispatcher->receiveBatch(
            $request->input('batch'),
            $queue,
            $request->boolean('payload.shouldBatch'),
        ));
    }
}
