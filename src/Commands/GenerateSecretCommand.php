<?php

namespace Assetplan\Dispatcher\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class GenerateSecretCommand extends Command
{
    const DISPATCHER_BACKEND_SECRET = 'DISPATCHER_BACKEND_SECRET';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dispatcher:generate-secret {--no-replace} {--show} {--length=64 : The length of the secret key must be between 32 and 128}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate secret key for dispatcher';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        if (App::environment('production')) {
            $this->warn('You are in production environment. Please do not run this command in production environment.');

            return self::FAILURE;
        }

        $length = $this->option('length');

        $validator = Validator::make(['length' => $length], [
            'length' => 'numeric|min:32|max:128',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->get('length') as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $secretKey = Str::random($length);

        $this->info('Secret key generated successfully.');

        if ($this->option('show')) {
            $this->info('Secret key: '.$secretKey);
        }

        if ($this->option('no-replace')) {
            return self::SUCCESS;
        }

        if (! file_exists(base_path('.env'))) {
            $this->error('.env file not found.');

            return self::FAILURE;
        }

        if ($this->confirm('Do you want to replace the existing '.self::DISPATCHER_BACKEND_SECRET.' key in .env file?', true)) {
            // read .env file if it has DISPATCHER_BACKEND_SECRET replace it or add it
            $envFile = File::get(base_path('.env'));

            if (strpos($envFile, self::DISPATCHER_BACKEND_SECRET) !== false) {
                $envFile = preg_replace('/'.self::DISPATCHER_BACKEND_SECRET.'=.*/', self::DISPATCHER_BACKEND_SECRET.'='.$secretKey, $envFile);
            } else {
                $envFile .= "\n".self::DISPATCHER_BACKEND_SECRET.'='.$secretKey;
            }

            File::put(base_path('.env'), $envFile);
        }

        return self::SUCCESS;
    }
}
