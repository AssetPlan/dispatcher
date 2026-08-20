<?php

namespace Assetplan\Dispatcher\Http\Middleware;

use Assetplan\Dispatcher\DispatcherFacade;
use Illuminate\Http\Request;

class DispatcherMiddleware
{
    public function handle(Request $request, \Closure $next)
    {
        if (! $request->wantsJson()) {
            abort(400, 'Only JSON requests are accepted');
        }

        if (! DispatcherFacade::verifyRequest([
            'job' => $request->input('job'),
            'payload' => $request->input('payload', []),
            'queue' => $request->input('queue', 'default'),
            'delay' => $request->input('delay'),
            'batch' => $request->input('batch'),
        ], $request->input('signature', ''))) {
            abort(403, 'Invalid signature');
        }

        return $next($request);
    }
}
