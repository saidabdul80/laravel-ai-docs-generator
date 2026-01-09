<?php

namespace SchoolTry\AIDocumentationGenerator\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use SchoolTry\AIDocumentationGenerator\Services\DocumentationGenerator;

class GenerateFrontendDocsCommand extends Command
{
    protected $signature = 'ai-docs:generate
        {--routes= : Path to routes file}
        {--max-depth= : Maximum depth for Vue component crawling}
        {--refresh-navigation : Refresh navigation analysis}
        {--test-single= : Test a single route path}
        {--concurrency= : Number of concurrent chunk requests}
        {--force : Regenerate docs even if documentation already exists}';

    protected $description = 'Generate end-user documentation from Vue frontend using AI';

    protected DocumentationGenerator $generator;

    public function __construct(DocumentationGenerator $generator)
    {
        parent::__construct();
        $this->generator = $generator;
    }

    public function handle(): int
    {
        $this->info('🚀 Starting AI Documentation Generation');
        $this->newLine();

        // Get configuration
        $routesFile = $this->option('routes') ?? config('ai-docs.layout_files.router');
        $routesFile = base_path($routesFile);

        $this->info("🔍 Looking for routes file at: {$routesFile}");

        if (!File::exists($routesFile)) {
            $this->error("❌ Routes file not found: {$routesFile}");
            return Command::FAILURE;
        }

        $this->info("✅ Routes file found");

        // Parse routes
        try {
            $routes = $this->generator->parseRoutes($routesFile);
            $this->info("Found " . count($routes) . " routes to process");
        } catch (Exception $e) {
            $this->error("❌ Failed to parse routes: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Analyze navigation
        if ($this->option('refresh-navigation') || !$this->hasNavigationCache()) {
            $this->info("🧭 Analyzing navigation structure...");
            try {
                $this->generator->analyzeNavigation();
                $this->cacheNavigation();
                $this->info("✅ Navigation analysis complete");
            } catch (Exception $e) {
                $this->warn("⚠️  Navigation analysis failed: " . $e->getMessage());
            }
        } else {
            $this->info("📖 Loading cached navigation memory...");
            $this->loadNavigationCache();
        }

        // Test single route if specified
        $testSingle = $this->option('test-single');
        if ($testSingle) {
            $routes = array_filter($routes, fn($r) => $r['path'] === $testSingle);
            $this->info("Testing single route: {$testSingle}");
        }

        // Process routes
        $processed = 0;
        $progressBar = $this->output->createProgressBar(count($routes));
        $progressBar->start();

        foreach ($routes as $route) {
            $progressBar->advance();

            if (!$this->option('force') && $this->shouldSkipExisting($route)) {
                continue;
            }

            if (!File::exists($route['component'])) {
                continue;
            }

            try {
                $pageContext = $this->generator->crawlVueFile($route['component']);

                if (empty($pageContext['content'])) {
                    continue;
                }

                $docs = $this->generator->generateDocumentation($route, $pageContext);
                $this->storeDocumentation($route, $docs);

                $processed++;

            } catch (Exception $e) {
                Log::error("Frontend docs generation failed for {$route['path']}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("🎉 Documentation generation completed. Processed: {$processed}/" . count($routes));
        return Command::SUCCESS;
    }

    protected function hasNavigationCache(): bool
    {
        $cacheFile = storage_path('app/ai_docs/navigation_memory.json');
        return File::exists($cacheFile);
    }

    protected function cacheNavigation(): void
    {
        $cacheFile = storage_path('app/ai_docs/navigation_memory.json');
        File::ensureDirectoryExists(dirname($cacheFile));
        // Implementation would save navigation memory
    }

    protected function loadNavigationCache(): void
    {
        // Implementation would load navigation memory
    }

    protected function shouldSkipExisting(array $route): bool
    {
        $outputDir = config('ai-docs.generation.output_dir');
        $slug = $this->slugFromPath($route['path']);
        $existingPath = storage_path("app/{$outputDir}/{$slug}.json");

        return File::exists($existingPath);
    }

    protected function storeDocumentation(array $route, array $docs): void
    {
        $outputDir = config('ai-docs.generation.output_dir');
        $slug = $this->slugFromPath($route['path']);
        $storagePath = storage_path("app/{$outputDir}/{$slug}");

        File::ensureDirectoryExists(dirname($storagePath));

        // Store markdown
        $guideContent = "# {$route['path']}\n\n" . $docs['guide'];
        File::put("{$storagePath}.md", $guideContent);

        // Store JSON
        $jsonData = [
            'path' => $route['path'],
            'component' => $route['component'],
            'generated_at' => now()->toISOString(),
            'guide' => $docs['guide'],
        ];

        File::put("{$storagePath}.json", json_encode($jsonData, JSON_PRETTY_PRINT));
    }

    protected function slugFromPath(string $path): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '_', trim($path, '/'));
        return $slug ?: 'home';
    }
}

