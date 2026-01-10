<?php

namespace SchoolTry\AIDocumentationGenerator\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SchoolTry\AIDocumentationGenerator\Services\AIServiceUploader;

class BuildDocVectorDbCommand extends Command
{
    protected $signature = 'ai-docs:build-vector-db
        {--dir= : Directory containing markdown docs}
        {--force : Force rebuild of vector database}
        {--limit=0 : Limit number of files to process}
        {--upload : Upload to AI service after building}
        {--no-upload : Skip upload even if enabled in config}';

    protected $description = 'Build vector database from generated markdown documentation';

    public function handle(): int
    {
        $dir = $this->option('dir') ?? config('ai-docs.generation.output_dir');
        $dir = base_path($dir);
        $force = $this->option('force');
        $limit = (int) $this->option('limit');

        if (!File::exists($dir)) {
            $this->error("Directory not found: {$dir}");
            return Command::FAILURE;
        }

        $files = collect(File::files($dir))
            ->filter(fn ($f) => Str::endsWith($f->getFilename(), '.md'))
            ->values();

        if ($limit > 0) {
            $files = $files->take($limit);
        }

        if ($files->isEmpty()) {
            $this->warn('No markdown files found.');
            return Command::SUCCESS;
        }

        $this->info("📚 Found {$files->count()} markdown files");
        $this->newLine();

        if ($force) {
            $storePath = base_path(config('ai-docs.vector_db.store_path'));
            if (File::exists($storePath)) {
                File::delete($storePath);
                $this->info("🗑️  Deleted existing vector store");
            }
        }

        $progressBar = $this->output->createProgressBar($files->count());
        $progressBar->start();

        $docIndex = [];

        $ragInstance  = new  NeuronAI\RAG\RAG();
        foreach ($files as $file) {
            $progressBar->advance();

            $path = $file->getRealPath();
            $content = File::get($path);
            $ragInstance::make()->addDocuments($content);
            $docIndex[] = $this->buildDocIndexEntry($path, $content);
        }

        $progressBar->finish();
        $this->newLine(2);

        // Write document index
        $this->writeDocIndex($dir, $docIndex);

        $this->info('✅ Vector database build completed');
        $this->line("Processed: {$files->count()} documents");
        $this->newLine();

        // Handle upload to AI service
        if ($this->shouldUpload()) {
            $this->uploadToAIService($dir);
        }

        return Command::SUCCESS;
    }

    protected function buildDocIndexEntry(string $path, string $content): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $content);
        $title = null;
        $headings = [];

        foreach ($lines as $line) {
            if ($title === null && preg_match('/^#\s+(.+)$/', $line, $matches)) {
                $title = trim($matches[1]);
            }

            if (preg_match('/^#{2,6}\s+(.+)$/', $line, $matches)) {
                $headings[] = trim($matches[1]);
            }
        }

        return [
            'path' => $path,
            'source_name' => basename($path),
            'title' => $title ?? basename($path),
            'headings' => $headings,
            'keywords' => $this->extractKeywords($content),
        ];
    }

    protected function writeDocIndex(string $baseDir, array $docIndex): void
    {
        $indexPath = $baseDir . DIRECTORY_SEPARATOR . 'index.json';
        File::put($indexPath, json_encode($docIndex, JSON_PRETTY_PRINT));
        $this->info("📝 Document index written to: {$indexPath}");
    }

    protected function extractKeywords(string $content): array
    {
        $content = strtolower($content);
        $content = preg_replace('/[^a-z0-9\\s]/', ' ', $content);
        $parts = preg_split('/\\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);

        $stopwords = [
            'the', 'and', 'for', 'with', 'that', 'this', 'from', 'into', 'your', 'you',
            'are', 'can', 'not', 'have', 'has', 'will', 'use', 'using', 'used', 'page',
            'section', 'click', 'menu', 'button', 'view', 'see', 'select', 'open',
        ];

        $counts = [];
        foreach ($parts as $part) {
            if (strlen($part) < 4 || in_array($part, $stopwords, true)) {
                continue;
            }
            $counts[$part] = ($counts[$part] ?? 0) + 1;
        }

        arsort($counts);

        return array_slice(array_keys($counts), 0, 12);
    }

    /**
     * Determine if we should upload to AI service
     */
    protected function shouldUpload(): bool
    {
        // Explicit --no-upload flag takes precedence
        if ($this->option('no-upload')) {
            return false;
        }

        // Explicit --upload flag
        if ($this->option('upload')) {
            return true;
        }

        // Check config
        return config('ai-docs.ai_service.enabled', false);
    }

    /**
     * Upload neuron.store and index.json to AI service
     */
    protected function uploadToAIService(string $dir): void
    {
        $this->info('📤 Uploading to AI service...');

        $neuronStorePath = base_path(config('ai-docs.vector_db.store_path'));
        $indexPath = $dir . DIRECTORY_SEPARATOR . 'index.json';

        // Check if files exist
        if (!File::exists($neuronStorePath)) {
            $this->warn("⚠️  Neuron store not found: {$neuronStorePath}");
            $this->line('Skipping upload.');
            return;
        }

        if (!File::exists($indexPath)) {
            $this->warn("⚠️  Index file not found: {$indexPath}");
            $this->line('Skipping upload.');
            return;
        }

        // Create uploader
        $uploader = new AIServiceUploader(config('ai-docs.ai_service'));

        // Validate configuration
        $errors = $uploader->validateConfig();
        if (!empty($errors)) {
            $this->error('❌ Configuration errors:');
            foreach ($errors as $error) {
                $this->line("   - {$error}");
            }
            $this->newLine();
            $this->line('Set AI_SERVICE_URL and AI_SERVICE_API_KEY in your .env file');
            return;
        }

        // Perform upload
        $result = $uploader->upload($neuronStorePath, $indexPath);

        if ($result['success']) {
            $this->info('✅ ' . $result['message']);

            if (isset($result['data'])) {
                $this->line("   Client: " . ($result['data']['client'] ?? 'N/A'));
                $this->line("   Agent: " . ($result['data']['agent'] ?? 'N/A'));
            }
        } else {
            $this->error('❌ ' . $result['message']);

            if (isset($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    $this->line("   - {$error}");
                }
            }
        }

        $this->newLine();
    }
}

