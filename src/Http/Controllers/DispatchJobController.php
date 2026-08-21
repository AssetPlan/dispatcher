<?php

namespace Assetplan\Dispatcher\Http\Controllers;

use Assetplan\Dispatcher\Dispatcher;
use Assetplan\Dispatcher\Rules\IsIlluminateJob;
use Illuminate\Http\Request;

class DispatchJobController
{
    public function __invoke(Request $request, Dispatcher $dispatcher)
    {
        $request->validate([
            'job' => ['required', new IsIlluminateJob],
            'payload' => 'required|array',
            'queue' => 'sometimes|required|string',
            'delay' => ['nullable', function (string $attribute, mixed $value, \Closure $fail) {
                if (! Dispatcher::isValidWireDelay($value)) {
                    $fail('The '.$attribute.' must be non-negative integer seconds or an ISO 8601 date.');
                }
            }],
            'signature' => 'required|string',
        ]);

        return response()->json(['id' => $dispatcher->receive(
            $request->input('job'),
            $request->input('payload'),
            $request->input('queue', 'default'),
            $request->input('delay'),
        )]);
    }
}
