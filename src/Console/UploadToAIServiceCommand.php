<?php

namespace SchoolTry\AIDocumentationGenerator\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use SchoolTry\AIDocumentationGenerator\Services\AIServiceUploader;

class UploadToAIServiceCommand extends Command
{
    protected $signature = 'ai-docs:upload
        {--dir= : Directory containing neuron.store and index.json}
        {--neuron-store= : Path to neuron.store file}
        {--index= : Path to index.json file}
        {--agent= : Agent name (overrides config)}
        {--check-status : Check AI service status before uploading}';

    protected $description = 'Upload documentation vector database to AI service';

    public function handle(): int
    {
        $this->info('📤 AI Documentation Upload');
        $this->newLine();

        // Get configuration
        $config = config('ai-docs.ai_service');

        if (!($config['enabled'] ?? false)) {
            $this->warn('⚠️  AI service upload is not enabled in configuration.');
            $this->line('Set AI_DOCS_UPLOAD_ENABLED=true in your .env file');
            return Command::FAILURE;
        }

        // Create uploader
        $uploader = new AIServiceUploader($config);

        // Validate configuration
        $errors = $uploader->validateConfig();
        if (!empty($errors)) {
            $this->error('❌ Configuration errors:');
            foreach ($errors as $error) {
                $this->line("   - {$error}");
            }
            $this->newLine();
            return Command::FAILURE;
        }

        // Check status if requested
        if ($this->option('check-status')) {
            $this->checkStatus($uploader);
            $this->newLine();
        }

        // Determine file paths
        [$neuronStorePath, $indexPath] = $this->resolveFilePaths();

        if (!$neuronStorePath || !$indexPath) {
            return Command::FAILURE;
        }

        // Display upload info
        $this->info('Upload Configuration:');
        $this->line("   URL: {$config['url']}");
        $this->line("   Agent: " . ($this->option('agent') ?: $config['agent']));
        $this->line("   Neuron Store: {$neuronStorePath}");
        $this->line("   Index: {$indexPath}");
        $this->line("   Neuron Store Size: " . $this->formatBytes(File::size($neuronStorePath)));
        $this->line("   Index Size: " . $this->formatBytes(File::size($indexPath)));
        $this->newLine();

        // Confirm upload
        if (!$this->confirm('Proceed with upload?', true)) {
            $this->info('Upload cancelled.');
            return Command::SUCCESS;
        }

        // Override agent if specified
        if ($this->option('agent')) {
            $config['agent'] = $this->option('agent');
            $uploader = new AIServiceUploader($config);
        }

        // Perform upload
        $this->info('Uploading...');
        try{

            $result = $uploader->upload($neuronStorePath, $indexPath);
        }catch(\Exception $e){
            $this->error('❌ Upload failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->newLine();

        if ($result['success']) {
            $this->info('✅ ' . $result['message']);
            
            if (isset($result['data'])) {
                $this->line("   Client: " . ($result['data']['client'] ?? 'N/A'));
                $this->line("   Agent: " . ($result['data']['agent'] ?? 'N/A'));
            }

            return Command::SUCCESS;
        }

        $this->error('❌ ' . $result['message']);
        
        if (isset($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $this->line("   - {$error}");
            }
        }

        if (isset($result['status'])) {
            $this->line("   HTTP Status: {$result['status']}");
        }

        return Command::FAILURE;
    }

    /**
     * Check AI service status
     */
    protected function checkStatus(AIServiceUploader $uploader): void
    {
        $this->info('Checking AI service status...');
        
        $agent = $this->option('agent') ?: config('ai-docs.ai_service.agent');
        $result = $uploader->checkStatus($agent);

        if ($result['success']) {
            $data = $result['data'];
            $exists = $data['exists'] ?? false;

            if ($exists) {
                $this->info("✅ Knowledge base exists for agent: {$agent}");
            } else {
                $this->warn("⚠️  No knowledge base found for agent: {$agent}");
            }
        } else {
            $this->warn('⚠️  Could not check status: ' . $result['message']);
        }
    }

    /**
     * Resolve file paths from options or defaults
     */
    protected function resolveFilePaths(): array
    {
        $neuronStorePath = storage_path('app/'.config('ai-docs.vector_db.store_path').'/neuron.store');
        $indexPath =  storage_path('app/'.config('ai-docs.vector_db.store_path').'/index.json');

        // Check explicit file paths
        if ($this->option('neuron-store') && $this->option('index')) {
            $neuronStorePath = $this->option('neuron-store');
            $indexPath = $this->option('index');
        }
        // Check directory option
        elseif ($this->option('dir')) {
            $dir = $this->option('dir');
            $neuronStorePath = base_path(config('ai-docs.vector_db.store_path'));
            $indexPath = $dir . DIRECTORY_SEPARATOR . 'index.json';
        }

        // Validate files exist
        if (!File::exists($neuronStorePath)) {
            $this->error("❌ Neuron store not found: {$neuronStorePath}");
            $this->line('Run: php artisan ai-docs:build-vector-db first');
            return [null, null];
        }

        if (!File::exists($indexPath)) {
            $this->error("❌ Index file not found: {$indexPath}");
            $this->line('Run: php artisan ai-docs:build-vector-db first');
            return [null, null];
        }

        return [$neuronStorePath, $indexPath];
    }

    /**
     * Format bytes to human-readable size
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}

