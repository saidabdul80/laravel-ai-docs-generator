<?php

namespace SchoolTry\AIDocumentationGenerator\Services;

use Exception;

/**
 * Additional methods for DocumentationGenerator
 */
trait DocumentationGeneratorMethods
{
    /**
     * Generate comprehensive guide with route links
     */
    protected function generateComprehensiveGuide(array $route, array $memory): string
    {
        $routeLinksEnabled = $this->config['route_links']['enabled'] ?? true;
        $baseUrl = $this->config['route_links']['base_url'] ?? '';
        $currentUrl = $baseUrl . $route['path'];

        // Find related routes
        $relatedRoutes = $this->findRelatedRoutes($route['path']);
        $relatedLinksText = '';

        if ($routeLinksEnabled && !empty($relatedRoutes)) {
            $relatedLinksText = "\n\nRelated Pages:\n";
            foreach ($relatedRoutes as $relatedRoute) {
                $relatedUrl = $baseUrl . $relatedRoute['path'];
                $relatedLinksText .= "- [{$relatedRoute['path']}]({$relatedUrl})\n";
            }
        }

        $memory[] = [
            'role' => 'user',
            'content' => <<<USR
CREATE FINAL END-USER DOCUMENTATION:

Based on all your knowledge (navigation structure + page analysis), create a comprehensive guide.

REQUIRED STRUCTURE:

# [Page Title]

**Page URL:** {$currentUrl}

## 1. Overview & Purpose
- What is this page for?
- Who uses it?
- When should it be used?

## 2. How to Access This Page - SPECIFIC NAVIGATION
This is CRITICAL. Use your navigation knowledge to provide EXACT steps:

[Provide step-by-step navigation for each relevant user role]
Example format:
**For [User Role]:**
1. Log in to [Portal Name]
2. In sidebar, click "[Exact Menu Section]"
3. Select "[Exact Menu Item]"
4. Click "[Specific Link]"

## 3. Page Layout & What You'll See
- Describe the main screen layout
- What sections/panels appear
- Key information displayed

## 4. Step-by-Step User Guide
Detailed instructions for ALL features:
- How to use forms
- How to interact with tables/data
- How to use buttons and actions
- What to expect after each step

## 5. Common Tasks & Real Examples
Practical scenarios with exact steps:
- Example 1: [Complete a common task]
- Example 2: [Another common task]
- Example 3: [Special scenario]

## 6. Troubleshooting & FAQ
Common problems and solutions:
- Problem 1 → Solution
- Problem 2 → Solution
- Problem 3 → Solution

## 7. Related Pages & Next Steps{$relatedLinksText}
- Where to go after this
- For more information
- To manage settings

RULES:
- NO technical terms (Vue, components, props, etc.)
- YES to specific menu/sidebar item names
- YES to practical, actionable instructions
- Write for non-technical users
- Include clickable route links where appropriate

Page: {$route['path']}
USR
        ];

        try {
            $guide = $this->aiProvider->chat($memory, 'standard');
            return $this->removeTechnicalTerms($guide);
        } catch (Exception $e) {
            return "# Documentation Generation Failed\n\nError: " . $e->getMessage();
        }
    }

    /**
     * Find related routes
     */
    protected function findRelatedRoutes(string $currentPath): array
    {
        $maxRelated = $this->config['route_links']['max_related'] ?? 5;
        $related = [];
        $pathParts = explode('/', trim($currentPath, '/'));

        foreach ($this->allRoutes as $route) {
            if ($route['path'] === $currentPath) {
                continue;
            }

            $otherParts = explode('/', trim($route['path'], '/'));
            $common = array_intersect($pathParts, $otherParts);

            if (count($common) > 0) {
                $related[] = $route;
            }

            if (count($related) >= $maxRelated) {
                break;
            }
        }

        return $related;
    }

    /**
     * Remove technical terms from content
     */
    protected function removeTechnicalTerms(string $content): string
    {
        $technicalTerms = [
            '/Vue(\.js)?/i' => 'the system',
            '/component/i' => 'page',
            '/props?/i' => 'settings',
            '/emit/i' => 'send',
            '/method/i' => 'function',
            '/computed/i' => 'calculated',
            '/watch(er)?/i' => 'monitor',
            '/v-model/i' => 'input field',
            '/v-if|v-else|v-for/i' => '',
            '/@click/i' => 'when you click',
            '/:class/i' => 'styling',
            '/router-link/i' => 'link',
        ];

        foreach ($technicalTerms as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    /**
     * Trim memory to keep it manageable
     */
    protected function trimMemory(array &$memory, int $max = 30): void
    {
        if (count($memory) > $max) {
            $memory = array_merge(
                [$memory[0]], // Keep system message
                array_slice($memory, -($max - 1))
            );
        }
    }
}

