<?php

namespace SchoolTry\AIDocumentationGenerator\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use SchoolTry\AIDocumentationGenerator\Contracts\AIProviderInterface;

class DocumentationGenerator
{
    use DocumentationGeneratorExtension;
    use DocumentationGeneratorMethods;

    protected AIProviderInterface $aiProvider;
    protected array $config;
    protected array $allRoutes = [];
    protected array $navigationMemory = [];

    public function __construct(AIProviderInterface $aiProvider, array $config)
    {
        $this->aiProvider = $aiProvider;
        $this->config = $config;
    }

    /**
     * Parse routes from the router file
     */
    public function parseRoutes(string $routerFile): array
    {
        if (!File::exists($routerFile)) {
            throw new Exception("Router file not found: {$routerFile}");
        }

        $content = File::get($routerFile);
        $routes = [];

        // Find the main routes array
        if (preg_match('/const routes\s*=\s*\[(.*?)\]\s*;/s', $content, $routesMatch)) {
            $routesContent = $routesMatch[1];
            $this->parseRouteDefinitions($routesContent, $routes);
        }

        // Post-process: Fix paths based on component locations to handle duplicates
        $routes = $this->deduplicateRoutesByComponent($routes);

        $this->allRoutes = $routes;
        return $routes;
    }

    /**
     * Deduplicate routes by inferring correct paths from component locations
     */
    protected function deduplicateRoutesByComponent(array $routes): array
    {
        $fixed = [];
        $pathGroups = [];
        $seen = [];

        // Group routes by path
        foreach ($routes as $route) {
            $pathGroups[$route['path']][] = $route;
        }

        // Process each group
        foreach ($pathGroups as $path => $group) {
            if (count($group) === 1) {
                // No duplicates, keep as is
                $fixed[] = $group[0];
            } else {
                // Multiple routes with same path - infer correct path from component
                foreach ($group as $route) {
                    $component = $route['component'];

                    // Check if this exact route (path + component) was already added
                    $routeKey = $route['path'] . '|' . $route['component'];
                    if (isset($seen[$routeKey])) {
                        // Skip exact duplicates
                        continue;
                    }
                    $seen[$routeKey] = true;

                    // Infer prefix from component path
                    $prefix = $this->inferPrefixFromComponent($component);

                    if ($prefix && !str_starts_with($path, $prefix)) {
                        // Add prefix to path
                        $route['path'] = rtrim($prefix, '/') . '/' . ltrim($path, '/');
                    }

                    // Check for subdirectory variations (e.g., ExamOffice)
                    if (preg_match('/\/(ExamOffice|ExamOffice)\//', $component)) {
                        // Add /exam-office suffix if not already present
                        if (!str_contains($route['path'], '/exam-office')) {
                            $parts = explode('/', $route['path']);
                            // Insert exam-office after the role prefix
                            if (count($parts) >= 2) {
                                array_splice($parts, 2, 0, 'exam-office');
                                $route['path'] = implode('/', $parts);
                            }
                        }
                    }

                    $fixed[] = $route;
                }
            }
        }

        return $fixed;
    }

    /**
     * Infer route prefix from component path
     */
    protected function inferPrefixFromComponent(string $componentPath): ?string
    {
        // Extract the role/section from component path
        // e.g., /path/to/Views/Admin/... -> /admin
        // e.g., /path/to/Views/Student/... -> /student

        if (preg_match('/\/Views\/([^\/]+)\//', $componentPath, $matches)) {
            $section = $matches[1];

            // Map component sections to route prefixes
            $prefixMap = [
                'Admin' => '/admin',
                'Student' => '/student',
                'Lecturer' => '/lecturer',
                'Department' => '/department',
                'Faculty' => '/faculty',
                'Institutes' => '/institute',
                'Director' => '/director',
                'SupportStaff' => '/support-staff',
                'AdministrativeOfficer' => '/administrative-officer',
                'FinanceOfficer' => '/finance-officer',
                'HostelManager' => '/hostel-manager',
                'AcademicLevelAdviser' => '/academic-level-adviser',
                'Cordinator' => '/cordinator',
                'ProgrammeCordinator' => '/programme-cordinator',
            ];

            return $prefixMap[$section] ?? null;
        }

        return null;
    }

    /**
     * Parse route definitions recursively
     */
    protected function parseRouteDefinitions(string $content, array &$routes, string $parentPath = ''): void
    {
        // Normalize content
        $content = str_replace(["\r\n", "\n", "\r"], ' ', $content);
        $content = preg_replace('/\s+/', ' ', $content);

        // Find route objects
        $pattern = '/\{[^{}]*(?:path\s*:\s*[\'"][^\'"]+[\'"])[^{}]*(?:component\s*:\s*[^,}]+|children\s*:\s*\[[^]]+\])[^{}]*\}/';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $routeText = $match[0];

            // Extract path
            if (preg_match('/path\s*:\s*[\'"]([^\'"]+)[\'"]/', $routeText, $pathMatch)) {
                $path = trim($pathMatch[1]);

                // Handle parent path for nested routes
                if ($parentPath && !str_starts_with($path, '/')) {
                    $fullPath = rtrim($parentPath, '/') . '/' . ltrim($path, '/');
                } else {
                    $fullPath = $path;
                }

                // Ensure path always starts with /
                if (!str_starts_with($fullPath, '/')) {
                    $fullPath = '/' . $fullPath;
                }

                // Skip redirects
                if (str_contains($routeText, 'redirect:') && !str_contains($routeText, 'component:')) {
                    continue;
                }

                // Extract component
                $component = $this->extractComponentFromRoute($routeText);

                if ($component) {
                    $componentPath = $this->resolveComponentPath($component);

                    if ($componentPath && File::exists($componentPath)) {
                        $routes[] = [
                            'path' => $fullPath,
                            'component' => $componentPath,
                            'import' => $component,
                            'parent_path' => $parentPath,
                        ];
                    }
                }

                // Check for children routes
                if (preg_match('/children\s*:\s*\[(.*?)\]\s*,?\s*[}\]]/s', $routeText, $childrenMatch)) {
                    $childrenContent = $childrenMatch[1];
                    $this->parseRouteDefinitions($childrenContent, $routes, $fullPath);
                }
            }
        }
    }

    /**
     * Extract component from route definition
     */
    protected function extractComponentFromRoute(string $routeText): ?string
    {
        $importPatterns = [
            '/import\([^)]*?\/\*[^*]+\*\/[^)]*?[\'"]([^\'"]+)[\'"][^)]*?\)/',
            '/import\([^)]*?[\'"]([^\'"]+)[\'"][^)]*?\)/',
        ];

        foreach ($importPatterns as $pattern) {
            if (preg_match($pattern, $routeText, $importMatch)) {
                $import = trim($importMatch[1]);
                $import = preg_replace('/\/\*[^*]+\*\//', '', $import);
                $import = preg_replace('/\s+/', ' ', $import);
                $import = trim($import, " '\"\t\n\r\0\x0B");

                if (empty($import) || $import === '*/' ||
                    (!str_contains($import, '/') && !str_contains($import, '.') && !str_starts_with($import, '@'))) {
                    continue;
                }

                return $import;
            }
        }

        return null;
    }

    /**
     * Resolve component path
     */
    protected function resolveComponentPath(string $import): ?string
    {
        $import = preg_replace('/\/\*.*?\*\//', '', $import);
        $import = trim($import, " '\"\t\n\r\0\x0B");

        // Handle different path patterns
        if (str_starts_with($import, '@/')) {
            $path = str_replace('@/', 'resources/js/', $import);
        } elseif (str_starts_with($import, '../')) {
            $path = 'resources/js/' . substr($import, 3);
        } elseif (str_starts_with($import, './')) {
            $path = 'resources/js' . substr($import, 1);
        } else {
            $path = 'resources/js/' . $import;
        }

        // Ensure .vue extension
        if (!str_ends_with($path, '.vue') && !str_ends_with($path, '.js') && !str_ends_with($path, '.ts')) {
            $path .= '.vue';
        }

        $fullPath = base_path($path);

        return File::exists($fullPath) ? $fullPath : null;
    }

    /**
     * Crawl Vue file and extract content
     */
    public function crawlVueFile(string $file, array &$visited = [], int $depth = 0): array
    {
        $maxDepth = $this->config['generation']['max_depth'] ?? 5;

        if ($depth > $maxDepth || isset($visited[$file]) || !File::exists($file)) {
            return [];
        }

        $visited[$file] = true;
        $content = File::get($file);

        // Extract Vue content
        $vueContent = $this->extractVueContent($content);

        // Extract imports
        $imports = $this->extractImports($content, dirname($file));

        $children = [];
        foreach ($imports as $import) {
            $child = $this->crawlVueFile($import, $visited, $depth + 1);
            if (!empty($child)) {
                $children[] = $child;
            }
        }

        return [
            'file' => $file,
            'content' => $vueContent,
            'imports' => $children,
            'raw_content' => $content,
        ];
    }

    /**
     * Extract Vue content (template and script)
     */
    protected function extractVueContent(string $content): string
    {
        // Extract template
        preg_match('/<template>([\s\S]*?)<\/template>/', $content, $templateMatches);
        $template = $templateMatches[1] ?? '';

        // Extract script
        preg_match('/<script[^>]*>([\s\S]*?)<\/script>/', $content, $scriptMatches);
        $script = $scriptMatches[1] ?? '';

        // Combine and clean
        $combined = trim($template . "\n\n" . $script);
        $combined = preg_replace('/\s+/', ' ', $combined);

        return substr($combined, 0, 10000);
    }

    /**
     * Extract imports from Vue file
     */
    protected function extractImports(string $content, string $baseDir): array
    {
        $imports = [];

        preg_match_all('/import\s+.*?\s+from\s+[\'"](.+?)[\'"]/', $content, $matches);

        foreach ($matches[1] ?? [] as $import) {
            if (str_ends_with($import, '.vue') || str_contains($import, 'components/')) {
                $resolved = $this->resolveImportPath($import, $baseDir);
                if ($resolved) {
                    $imports[] = $resolved;
                }
            }
        }

        return array_filter($imports);
    }

    /**
     * Resolve import path
     */
    protected function resolveImportPath(string $import, string $baseDir): ?string
    {
        if (str_starts_with($import, './')) {
            $path = realpath($baseDir . '/' . $import);
        } elseif (str_starts_with($import, '../')) {
            $path = realpath($baseDir . '/' . $import);
        } elseif (str_starts_with($import, '@/')) {
            $path = base_path('resources/js/' . substr($import, 2));
        } else {
            $path = base_path('resources/js/' . $import);
        }

        if ($path && !str_ends_with($path, '.vue') && !str_ends_with($path, '.js')) {
            $path .= '.vue';
        }

        return File::exists($path) ? $path : null;
    }

    /**
     * Flatten page context for processing
     */
    protected function flattenPageContext(array $ctx): string
    {
        $out = "=== FILE: " . basename($ctx['file']) . " ===\n";
        $out .= $ctx['content'] . "\n\n";

        foreach ($ctx['imports'] ?? [] as $child) {
            $childContent = $this->flattenPageContext($child);
            if (strlen($childContent) > 100) {
                $out .= $childContent;
            }
        }

        return $out;
    }

    /**
     * Chunk content for processing
     */
    protected function chunkContext(string $content, ?int $size = null): array
    {
        $size = $size ?? $this->config['generation']['chunk_size'] ?? 2000;

        if (strlen($content) <= $size) {
            return [$content];
        }

        $chunks = [];
        $length = strlen($content);

        for ($i = 0; $i < $length; $i += $size) {
            $chunk = substr($content, $i, $size);

            // Try to break at sentence end
            $lastPeriod = strrpos($chunk, '.');
            $lastNewline = strrpos($chunk, "\n");
            $breakPoint = max($lastPeriod, $lastNewline);

            if ($breakPoint > $size * 0.8) {
                $chunks[] = substr($chunk, 0, $breakPoint + 1);
                $i -= ($size - $breakPoint - 1);
            } else {
                $chunks[] = $chunk;
            }
        }

        return $chunks;
    }
}

