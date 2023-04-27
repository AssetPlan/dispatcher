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

### Generating a secret key
The `dispatcher:generate-secret` command allows you to generate a secret key that is used to sign the dispatched jobs. You can use this command to generate a new secret key or replace an existing one.

To generate a new secret key, run the following command:

```bash
php artisan dispatcher:generate-secret
```

By default, the command generates a 64-character secret key and saves it in the .env file of your application. If the .env file does not exist, the command will display an error message.

You can use the `--length` option to specify the length of the secret key. For example, to generate a 128-character secret key, run the following command:

```bash
php artisan dispatcher:generate-secret --length=128
```

You can use the --show option to display the generated secret key in the console. For example, to generate a new secret key and display it in the console, run the following command:

```bash
php artisan dispatcher:generate-secret --show
```

You can use the --no-replace option to generate a new secret key without replacing an existing one. For example, to generate a new secret key without replacing an existing one, run the following command:

```bash
php artisan dispatcher:generate-secret --no-replace
```

If the `.env` file already contains a `DISPATCHER_BACKEND_SECRET` variable, the command will replace its value with the newly generated secret key. If the `.env` file does not contain a `DISPATCHER_BACKEND_SECRET` variable, the command will add the variable and its value to the file.

**Note:** If you are running the command in a production environment, the command will display a warning message and exit without generating a new secret key. This is to prevent accidental changes to production settings.

### Configuration

The package comes with a configuration file that allows you to customize its behavior. You can publish the configuration file by running the following command:
```bash
php artisan vendor:publish --provider="Assetplan\Dispatcher\DispatcherServiceProvider" --tag="config"
```

Once you have published the configuration file, you can customize the following options:

- `url`: The URL of the backend server where the jobs will be dispatched.
- `secret`: The secret key that will be used to sign the jobs before dispatching them.
- `is_backend`: Whether the current server is the backend server or not.
- `aliases`: You can register aliases to make it easier to dispatch your jobs. These aliases only need to be registered in the backend server and are optional.

### Aliases

The `aliases` configuration option allows you to register custom aliases for job classes. This can be useful to avoid typing the fully qualified class name of a job every time you dispatch it.

Here's an example of how to register an alias in the dispatcher.php config file:
```php
<?php
'aliases' => [
    'send-welcome-email' => 'App\Jobs\SendWelcomeEmail',
],
```

You can then use the alias when dispatching the job:

```php
<?php
    $dispatcher->dispatch('send-welcome-email', ['user' => $user]);
```

**Note:** Registering aliases is entirely optional and it only needs to be done in the backend server.

### Dispatching a job
To dispatch a job from your application, use the dispatch method of the Dispatcher class. The method takes two parameters:

- `$job`: The fully qualified name of the job class to be dispatched.
- `$payload`: An array of data to be passed to the job.

Here's an example:
```php
<?php

use Assetplan\Dispatcher\Dispatcher;
use Illuminate\Http\Request;

class ExampleController
{
    public function store(Request $request, Dispatcher $dispatcher)
    {
        $result = $dispatcher->dispatch(MyJob::class, ['foo' => 'bar']);
        if ($result->failed()) {
            // do something if failed dispatch
        }

        return $result->getId(); // the result object allows access to the dispatched job id
    }
}
```
The `dispatch` method will return the result of the dispatched job. You can use this result to track the status of the job or to perform further processing.

### Dispatching a batch of jobs

To dispatch a batch of jobs, use the `batch` method of the `Dispatcher` class. The method takes three parameters:

- `$jobs`: an array of `Assetplan\Dispatcher\Queue\Job` objects: each with the name of the target job and it's payload
- `$queue`: the queue to which the jobs should be dispatched
- `$shouldBatch`: whether the batch should be dispatched as a Laravel Queue Batch or simply dispatch all the jobs separately

Here's an example:
```php
<?php

use Assetplan\Dispatcher\Dispatcher;
use App\Jobs\SendWelcomeEmail;
use Illuminate\Http\Request;

class ExampleController
{
    public function store(Request $request, Dispatcher $dispatcher)
    {
        $jobs = [
            new Job(
                SendWelcomeEmail::class,
                ['email'=>'user@example.com']
            ),
            new Job(
                'App\Jobs\InviteToUserGroup',
                ['email'=>'user@example.com']
            ),
        ];
        $result = $dispatcher->batch($jobs, 'emails', shouldBatch: true);
        if ($result->failed()) {
            // do something if failed dispatch
        }

        return $result->getId(); // the result object allows access to the dispatched batch id
    }
}

```

**Note:** When dispatching with `shouldBatch=false` the batch id will be generated as the local batch UUID.



## Credits

-   [Assetplan](https://github.com/assetplan)
-   [Cristian Fuentes](https://github.com/cfuentessalgado)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

