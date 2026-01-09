<?php

namespace SchoolTry\AIDocumentationGenerator\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIServiceUploader
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Check if upload is enabled
     */
    public function isEnabled(): bool
    {
        return $this->config['enabled'] ?? false;
    }

    /**
     * Validate configuration
     */
    public function validateConfig(): array
    {
        $errors = [];

        if (empty($this->config['url'])) {
            $errors[] = 'AI service URL is not configured';
        }

        if (empty($this->config['api_key'])) {
            $errors[] = 'AI service API key is not configured';
        }

        return $errors;
    }

    /**
     * Upload neuron.store and index.json to AI service
     */
    public function upload(string $neuronStorePath, string $indexPath): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'AI service upload is not enabled',
            ];
        }

        // Validate configuration
        $errors = $this->validateConfig();
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Configuration errors',
                'errors' => $errors,
            ];
        }

        // Check if files exist
        if (!File::exists($neuronStorePath)) {
            return [
                'success' => false,
                'message' => "Neuron store file not found: {$neuronStorePath}",
            ];
        }

        if (!File::exists($indexPath)) {
            return [
                'success' => false,
                'message' => "Index file not found: {$indexPath}",
            ];
        }

        try {
            $url = rtrim($this->config['url'], '/') . '/api/knowledge';
            $agent = $this->config['agent'] ?? 'documentation';
            $timeout = $this->config['timeout'] ?? 60;
            $verifySSL = $this->config['verify_ssl'] ?? true;

            Log::info('Uploading documentation to AI service', [
                'url' => $url,
                'agent' => $agent,
                'neuron_store_size' => File::size($neuronStorePath),
                'index_size' => File::size($indexPath),
            ]);

            $response = Http::withToken($this->config['api_key'])
                ->timeout($timeout)
                ->withOptions(['verify' => $verifySSL])
                ->attach('neuron_store', File::get($neuronStorePath), 'neuron.store')
                ->attach('index', File::get($indexPath), 'index.json')
                ->post($url, [
                    'agent' => $agent,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Documentation uploaded successfully', [
                    'response' => $data,
                ]);

                return [
                    'success' => true,
                    'message' => 'Documentation uploaded successfully',
                    'data' => $data,
                ];
            }

            $errorMessage = $response->json('message') ?? $response->body();

            Log::error('Failed to upload documentation', [
                'status' => $response->status(),
                'error' => $errorMessage,
            ]);

            return [
                'success' => false,
                'message' => "Upload failed: {$errorMessage}",
                'status' => $response->status(),
            ];

        } catch (Exception $e) {
            Log::error('Exception during documentation upload', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Upload exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check AI service status
     */
    public function checkStatus(?string $agent = null): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'AI service upload is not enabled',
            ];
        }

        try {
            $url = rtrim($this->config['url'], '/') . '/api/knowledge/status';
            $agent = $agent ?? $this->config['agent'] ?? 'documentation';

            $response = Http::withToken($this->config['api_key'])
                ->timeout(10)
                ->get($url, ['agent' => $agent]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to check status',
                'status' => $response->status(),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }
}

