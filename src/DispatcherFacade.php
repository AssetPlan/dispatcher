<?php

namespace Assetplan\Dispatcher;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Assetplan\Dispatcher\Skeleton\SkeletonClass
 */
class DispatcherFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'dispatcher';
    }
}
