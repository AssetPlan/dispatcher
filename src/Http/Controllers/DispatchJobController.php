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
            'payload' => 'required',
            'queue' => 'sometimes',
            'signature' => 'required',
        ]);

        $queue = 'default';

        if ($request->filled('queue')) {
            $queue = $request->input('queue');
        }

        return response()->json(['id' => $dispatcher->receive($request->job, $request->payload, $queue)]);
    }
}
