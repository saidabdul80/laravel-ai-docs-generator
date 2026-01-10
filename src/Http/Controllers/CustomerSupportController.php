<?php

namespace SchoolTry\AIDocumentationGenerator\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CustomerSupportController extends Controller
{
    /**
     * Handle customer support query with streaming support.
     */
    public function query(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'session_id' => 'nullable|string',
            'stream' => 'nullable|boolean',
            'instructions' => 'nullable|string|max:2000',
        ]);

        $message = trim($request->input('message'));
        $sessionId = $request->input('session_id', 'support_' . $request->ip());
        $stream = $request->boolean('stream', true);
        $agent = $request->input('agent', $this->supportConfig('ai_service.default_agent', 'documentation'));

        $threadKey = auth()->check() ? auth()->id() . '_thread_id' : 'guest_thread_id';
        session([$threadKey => $sessionId]);

        return $this->handleAIServiceQuery(
            $message,
            $sessionId,
            $agent,
            $stream,
            $request->input('user_context', []),
            $request->input('instructions')
        );
    }

    protected function handleAIServiceQuery(
        string $message,
        string $threadId,
        string $agent,
        bool $stream,
        array $userContext,
        ?string $instructions
    ) {
        $aiServiceUrl = $this->supportConfig('ai_service.url', config('ai-docs.ai_service.url', 'http://localhost:8000'));
        $aiServiceKey = $this->supportConfig('ai_service.api_key', config('ai-docs.ai_service.api_key'));

        if (empty($aiServiceKey)) {
            return response()->json([
                'success' => false,
                'error' => 'AI service is not configured. Please set AI_SERVICE_API_KEY.',
            ], 503);
        }

        if (auth()->check()) {
            $user = auth()->user();
            $userContext = array_merge($userContext, [
                'user_id' => $user->id,
                'user_name' => $user->name ?? $user->email,
                'user_role' => $user->role ?? 'user',
            ]);
        }

        if ($stream) {
            return $this->streamAIServiceResponse(
                $aiServiceUrl,
                $aiServiceKey,
                $message,
                $threadId,
                $agent,
                $userContext
            );
        }

        try {
            $timeout = (int) $this->supportConfig('ai_service.timeout', 60);

            $response = Http::withToken($aiServiceKey)
                ->timeout($timeout)
                ->post("{$aiServiceUrl}/api/chat", [
                    'message' => $message,
                    'thread_id' => $threadId,
                    'agent' => $agent,
                    'stream' => false,
                    'user_context' => $userContext,
                    'instructions' => $instructions,
                ]);

            if ($response->successful()) {
                $data = $response->json();

               // $this->storeHistory($message, $threadId, $agent, $data, $data['tokens_used'] ?? null);

                return response()->json([
                    'success' => true,
                    'response' => $data['reply'] ?? $data['response'] ?? 'No response',
                    'thread_id' => $data['thread_id'] ?? $threadId,
                    'timestamp' => $data['timestamp'] ?? now()->toIso8601String(),
                ]);
            }

            Log::error('AI service request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'AI service request failed: ' . $response->body(),
            ], $response->status());
        } catch (\Exception $e) {
            Log::error('AI service communication error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to communicate with AI service: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get conversation history for a session.
     */
    public function history(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $historyModel = $this->resolveHistoryModel();
        if (!$historyModel) {
            return response()->json([
                'success' => false,
                'error' => 'Chat history model is not configured.',
            ], 501);
        }

        $user = $request->user();
        $sessionId = $request->input('session_id');

        try {
            $history = $historyModel::query()
                ->where('session_id', $sessionId)
                ->when($user, fn($q) => $q->where('user_id', $user->id))
                ->orderBy('created_at', 'asc')
                ->limit(50)
                ->get()
                ->map(function ($record) {
                    $response = json_decode($record->response, true);
                    return [
                        'id' => $record->id,
                        'query' => $record->query,
                        'response' => is_array($response) ? ($response['response'] ?? $record->response) : $record->response,
                        'timestamp' => $record->created_at->toIso8601String(),
                    ];
                });

            return response()->json([
                'success' => true,
                'history' => $history,
                'session_id' => $sessionId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve conversation history', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve conversation history',
            ], 500);
        }
    }

    /**
     * Clear conversation memory for a session.
     */
    public function clearMemory(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $historyModel = $this->resolveHistoryModel();
        if (!$historyModel) {
            return response()->json([
                'success' => false,
                'error' => 'Chat history model is not configured.',
            ], 501);
        }

        $user = $request->user();
        $sessionId = $request->input('session_id');

        try {
            $historyModel::query()
                ->where('session_id', $sessionId)
                ->when($user, fn($q) => $q->where('user_id', $user->id))
                ->delete();

            Log::info('Conversation memory cleared', [
                'user_id' => $user?->id,
                'session_id' => $sessionId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Conversation memory cleared successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear conversation memory', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to clear conversation memory',
            ], 500);
        }
    }

    /**
     * Query AI service with documentation context.
     */
    public function queryAIService(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'thread_id' => 'nullable|string',
            'agent' => 'nullable|string',
            'stream' => 'nullable|boolean',
            'user_context' => 'nullable|array',
            'instructions' => 'nullable|string|max:2000',
        ]);

        $message = trim($request->input('message'));
        $threadId = $request->input('thread_id', 'support_' . uniqid());
        $agent = $request->input('agent', $this->supportConfig('ai_service.default_agent', 'documentation'));
        $stream = $request->boolean('stream', false);
        $userContext = $request->input('user_context', []);
        $instructions = $request->input('instructions');

        $aiServiceUrl = $this->supportConfig('ai_service.url', config('ai-docs.ai_service.url', 'http://localhost:8000'));
        $aiServiceKey = $this->supportConfig('ai_service.api_key', config('ai-docs.ai_service.api_key'));

        if (empty($aiServiceKey)) {
            return response()->json([
                'success' => false,
                'error' => 'AI service is not configured. Please set AI_SERVICE_API_KEY.',
            ], 503);
        }

        if (auth()->check()) {
            $user = auth()->user();
            $userContext = array_merge($userContext, [
                'user_id' => $user->id,
                'user_name' => $user->name ?? $user->email,
                'user_role' => $user->role ?? 'user',
            ]);
        }

    
        if ($stream) {
            return $this->streamAIServiceResponse(
                $aiServiceUrl,
                $aiServiceKey,
                $message,
                $threadId,
                $agent,
                $userContext,
                $instructions
            );
        }

        try {
            $timeout = (int) $this->supportConfig('ai_service.timeout', 60);

            $response = Http::withToken($aiServiceKey)
                ->timeout($timeout)
                ->post("{$aiServiceUrl}/api/chat", [
                    'message' => $message,
                    'thread_id' => $threadId,
                    'agent' => $agent,
                    'stream' => false,
                    'user_context' => $userContext,
                    'instructions' => $instructions,
                ]);

            if ($response->successful()) {
                $data = $response->json();

//                $this->storeHistory($message, $threadId, $agent, $data, $data['tokens_used'] ?? null);

                return response()->json([
                    'success' => true,
                    'response' => $data['reply'] ?? $data['response'] ?? 'No response',
                    'thread_id' => $data['thread_id'] ?? $threadId,
                    'timestamp' => $data['timestamp'] ?? now()->toIso8601String(),
                ]);
            }

            Log::error('AI service request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'AI service request failed: ' . $response->body(),
            ], $response->status());
        } catch (\Exception $e) {
            Log::error('AI service communication error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to communicate with AI service: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stream response from AI service (internal helper method).
     */
    protected function streamAIServiceResponse(
        string $aiServiceUrl,
        string $aiServiceKey,
        string $message,
        string $threadId,
        string $agent,
        array $userContext
    ) {
        Log::info('Sending query to AI service4', [
            'thread_id' => $threadId,
            'agent' => $agent,
            'message_length' => strlen($message),
        ]);
        $instructions = request()->input('instructions');
        return response()->stream(function () use ($aiServiceUrl, $aiServiceKey, $message, $threadId, $agent, $userContext, $instructions) {
            try {
                echo "data: " . json_encode([
                    'type' => 'status',
                    'message' => 'Connecting to AI service...',
                    'thread_id' => $threadId,
                ]) . "\n\n";
                flush();

                $timeout = (int) $this->supportConfig('ai_service.stream_timeout', 120);

                $response = Http::withToken($aiServiceKey)
                    ->withHeaders(['Accept' => 'text/event-stream'])
                    ->timeout($timeout)
                    ->withOptions(['stream' => true])
                    ->post("{$aiServiceUrl}/api/chat", [
                        'message' => $message,
                        'thread_id' => $threadId,
                        'agent' => $agent,
                        'stream' => true,
                        'user_context' => $userContext,
                        'instructions' => $instructions,
                    ]);

                $fullResponse = '';
                $body = $response->toPsrResponse()->getBody();

                while (! $body->eof()) {
                    $chunk = $body->read(1024);
                    if ($chunk === '') {
                        usleep(10000);
                        continue;
                    }

                    echo $chunk;
                    flush();

                    $fullResponse .= $chunk;
                }

            //    $this->storeHistory($message, $threadId, $agent, $fullResponse, null);
            } catch (\Exception $e) {
                Log::error('AI service streaming error', [
                    'error' => $e->getMessage(),
                ]);

                echo "data: " . json_encode([
                    'type' => 'error',
                    'message' => 'Streaming error: ' . $e->getMessage(),
                ]) . "\n\n";
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    protected function resolveHistoryModel(): ?string
    {
        if (!$this->supportConfig('history_enabled', true)) {
            return null;
        }

        $modelClass = $this->supportConfig('history_model');
        if (!$modelClass || !class_exists($modelClass)) {
            return null;
        }

        return $modelClass;
    }

    protected function storeHistory(string $message, string $threadId, string $agent, mixed $data, ?int $tokensUsed): void
    {
        $historyModel = $this->resolveHistoryModel();
        if (!$historyModel) {
            return;
        }

        try {
            $historyModel::create([
                'user_id' => auth()->id(),
                'session_id' => $threadId,
                'query' => $message,
                'response' => is_string($data) ? $data : json_encode($data),
                'model' => $agent,
                'tokens_used' => $tokensUsed,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to store chat history', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function supportConfig(string $key, mixed $default = null): mixed
    {
        $config = config('ai-docs.customer_support', []);
        if ($key === '') {
            return $config;
        }

        $segments = explode('.', $key);
        $value = $config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value ?? $default;
    }
}
