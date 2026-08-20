<?php

namespace Assetplan\Dispatcher;

use Assetplan\Dispatcher\Skeleton\SkeletonClass;
use Illuminate\Support\Facades\Facade;

/**
 * @see SkeletonClass
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
