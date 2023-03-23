# Assetplan Dispatcher

Assetplan Dispatcher is a lightweight PHP package that allows you to dispatch jobs between multiple applications using HTTP requests. This can be useful in distributed systems where jobs need to be executed on remote machines.

## Installation

You can install the package via composer:

```bash
composer require assetplan/dispatcher
```

## Usage

### Setting up the backend
Before you can start dispatching jobs, you need to set up a backend application that will receive the dispatched jobs. This can be any PHP application that can handle HTTP requests. You'll need to set the following environment variables to configure the backend:

```bash
# in the backend
DISPATCHER_BACKEND_SECRET=your-secret
DISPATCHER_IS_BACKEND=true
```

```bash
# in the machine that will dispatch the jobs
DISPATCHER_BACKEND_URL=http://localhost:8000
DISPATCHER_BACKEND_SECRET=your-secret
```

The `DISPATCHER_BACKEND_URL` variable should point to the URL of the backend application. The `DISPATCHER_BACKEND_SECRET` variable is a shared secret that is used to sign the dispatched jobs. Make sure to keep this secret secure.


### Dispatching a job
To dispatch a job from your application, use the dispatch method of the Dispatcher class. The method takes two parameters:

- `$job`: The fully qualified name of the job class to be dispatched.
- `$payload`: An array of data to be passed to the job.

Here's an example:
```php
<?php

use Assetplan\Dispatcher\DispatcherFacade;

$result = DispatcherFacade::dispatch(MyJob::class, ['foo' => 'bar']);
```
The `dispatch` method will return the result of the dispatched job. You can use this result to track the status of the job or to perform further processing.

## Credits

-   [Cristian Fuentes](https://github.com/assetplan)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

