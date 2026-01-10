<?php

namespace SchoolTry\AIDocumentationGenerator\Providers;

use Illuminate\Support\ServiceProvider;
use SchoolTry\AIDocumentationGenerator\Console\GenerateFrontendDocsCommand;
use SchoolTry\AIDocumentationGenerator\Console\BuildDocVectorDbCommand;
use SchoolTry\AIDocumentationGenerator\Console\UploadToAIServiceCommand;
use SchoolTry\AIDocumentationGenerator\Services\AIProviderFactory;
use SchoolTry\AIDocumentationGenerator\Services\DocumentationGenerator;

class AIDocumentationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/ai-docs.php',
            'ai-docs'
        );

        // Register the documentation generator
        $this->app->singleton(DocumentationGenerator::class, function ($app) {
            $config = config('ai-docs');
            $providerName = $config['provider'];
            $providerConfig = $config['providers'][$providerName] ?? [];

            $aiProvider = AIProviderFactory::make($providerName, $providerConfig);

            return new DocumentationGenerator($aiProvider, $config);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');

        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/ai-docs.php' => config_path('ai-docs.php'),
        ], 'ai-docs-config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateFrontendDocsCommand::class,
                BuildDocVectorDbCommand::class,
                UploadToAIServiceCommand::class,
            ]);
        }
    }
}
