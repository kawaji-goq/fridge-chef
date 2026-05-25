<?php

namespace App\Providers;

use App\Services\Bedrock\Contracts\BedrockClient;
use App\Services\Bedrock\FakeBedrockClient;
use App\Services\Bedrock\RealBedrockClient;
use Aws\BedrockRuntime\BedrockRuntimeClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BedrockClient::class, function ($app) {
            $driver = env('BEDROCK_DRIVER', 'fake');

            if ($driver === 'real') {
                $aws = new BedrockRuntimeClient([
                    'region' => env('BEDROCK_REGION', 'ap-northeast-1'),
                    'version' => 'latest',
                    'credentials' => [
                        'key' => env('AWS_ACCESS_KEY_ID'),
                        'secret' => env('AWS_SECRET_ACCESS_KEY'),
                    ],
                ]);

                return new RealBedrockClient($aws, env('BEDROCK_MODEL_ID'));
            }

            return new FakeBedrockClient();
        });
    }

    public function boot(): void
    {
        //
    }
}
