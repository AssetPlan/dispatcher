<?php

namespace Assetplan\Dispatcher\Http\Middleware;

use Assetplan\Dispatcher\DispatcherFacade;
use Illuminate\Http\Request;

class DispatcherMiddleware
{
    public function handle(Request $request, \Closure $next)
    {

        if (!$request->wantsJson()) {
            abort(400, 'Only JSON requests are accepted');
        }

        if (!DispatcherFacade::verify($request->job, $request->payload, $request->signature)) {
            abort(403, 'Invalid signature');
        }

        return $next($request);
    }
}
