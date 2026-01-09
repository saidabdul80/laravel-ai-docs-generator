<?php

namespace SchoolTry\AIDocumentationGenerator\Services;

use Exception;
use Illuminate\Support\Facades\File;

/**
 * Extension methods for DocumentationGenerator
 * These will be merged into the main class
 */
trait DocumentationGeneratorExtension
{
    /**
     * Analyze and store navigation structure
     */
    public function analyzeNavigation(): array
    {
        $navigationMemory = [[
            'role' => 'system',
            'content' => <<<SYS
You are analyzing the navigation/sidebar structure of an application.
Your task is to understand how users navigate through the system.

Extract:
1. Main sidebar sections and menu items
2. Menu hierarchy and organization
3. Common navigation patterns
4. How different user roles access different pages

You will use this knowledge to provide navigation instructions for each page.
SYS
        ]];

        // Load navigation components
        $navigationDir = base_path($this->config['layout_files']['navigation'] ?? 'resources/js/components/Navigation');

        if (File::exists($navigationDir)) {
            $files = File::files($navigationDir);

            foreach ($files as $file) {
                if ($file->getExtension() === 'vue') {
                    $content = File::get($file->getPathname());
                    $vueContent = $this->extractVueContent($content);

                    $navigationMemory[] = [
                        'role' => 'user',
                        'content' => <<<USR
Analyze this navigation/layout component:

Filename: {$file->getFilename()}
Content: {$vueContent}

Extract:
1. What menu sections are defined?
2. What menu items exist and what pages do they link to?
3. How is the navigation organized (hierarchy)?
4. What user roles can access which sections?
USR
                    ];

                    $response = $this->aiProvider->chat($navigationMemory, 'lightweight');

                    $navigationMemory[] = [
                        'role' => 'assistant',
                        'content' => $response ?: 'Analyzed navigation component.',
                    ];

                    $this->trimMemory($navigationMemory, 20);
                }
            }
        }

        // Consolidate navigation knowledge
        $navigationMemory[] = [
            'role' => 'user',
            'content' => <<<USR
Based on all navigation components analyzed, create a comprehensive navigation guide.

Summarize:
1. Main navigation sections
2. Typical navigation paths for each user role
3. Common menu item names and what they lead to
4. How to describe navigation in user-friendly terms
USR
        ];

        $consolidated = $this->aiProvider->chat($navigationMemory, 'standard');

        $navigationMemory[] = [
            'role' => 'assistant',
            'content' => $consolidated,
        ];

        $this->navigationMemory = $navigationMemory;
        return $navigationMemory;
    }

    /**
     * Generate documentation for a route
     */
    public function generateDocumentation(array $route, array $pageContext): array
    {
        $memory = $this->navigationMemory;

        // Add documentation-specific prompt
        $memory[] = [
            'role' => 'user',
            'content' => <<<USR
IMPORTANT: You now need to create documentation for a specific page.
Use your knowledge of the navigation system to provide EXACT navigation instructions.

Page to document: {$route['path']}

Based on the navigation structure you analyzed earlier:
1. How does a user navigate to this page?
2. What sidebar/menu items do they click?
3. What user role is needed?
4. What's the exact path from login?

Provide navigation instructions first, then analyze the page content.
USR
        ];

        $flat = $this->flattenPageContext($pageContext);

        // Fast mode: skip chunk processing, go straight to guide generation
        $fastMode = $this->config['generation']['fast_mode'] ?? false;

        if ($fastMode) {
            // Add all content in one go
            $memory[] = [
                'role' => 'user',
                'content' => "Page Content:\n\n" . substr($flat, 0, 15000) . "\n\nAnalyze this page and prepare to generate documentation.",
            ];

            $response = $this->aiProvider->chat($memory, 'lightweight');
            $memory[] = [
                'role' => 'assistant',
                'content' => $response ?: 'Analyzed page content.',
            ];
        } else {
            // Normal mode: process in chunks
            $chunks = $this->chunkContext($flat);

            // Process content chunks
            foreach ($chunks as $index => $chunk) {
                $memory[] = [
                    'role' => 'user',
                    'content' => $this->buildChunkPrompt($index, $chunk),
                ];

                $response = $this->aiProvider->chat($memory, 'lightweight');

                $memory[] = [
                    'role' => 'assistant',
                    'content' => $response ?: 'Analyzed page content.',
                ];

                $this->trimMemory($memory, $this->config['generation']['memory_limit'] ?? 40);
            }
        }

        // Generate comprehensive guide
        $fullGuide = $this->generateComprehensiveGuide($route, $memory);

        return [
            'guide' => $fullGuide,
            'route' => $route,
        ];
    }

    /**
     * Build chunk prompt
     */
    protected function buildChunkPrompt(int $index, string $chunk): string
    {
        return <<<USR
Page Content Chunk {$index}:

{$chunk}

Based on this content and your navigation knowledge:
1. What can users do on this page?
2. What interface elements are present?
3. How do they use this page?
4. What happens after actions are taken?

Remember: Use non-technical language.
USR;
    }
}
