<?php

namespace SchoolTry\AIDocumentationGenerator\Services\Agent;
use NeuronAI\RAG\RAG;
use App\AI\Neuron\OllamaProvider;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\HttpClientOptions;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\VectorStore\FileVectorStore;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use NeuronAI\SystemPrompt;


class VectorBuilderAgent extends RAG
{
    protected string $name = 'builder';

    public function instructions(): string
    {
        return (string) new SystemPrompt([]);
    }

    protected function topK(): int
    {
        return 5;
    }

    protected function threshold(): float
    {
        return 0.7;
    }


     protected function provider(): AIProviderInterface
    {
        $provider = config('ai-docs.provider', 'ollama');
        $providerConfig = config('ai-docs.providers')[$provider] ?? [];
        $url = $providerConfig['base_url'] ?? 'http://localhost:11434';
        $model = $providerConfig['models']['lightweight'] ?? 'qwen2.5:3b-instruct';
        return new Ollama(
            url: $url.'/api',
            model: $model,
            parameters: [], // Add custom params (temperature, logprobs, etc)
            httpOptions: new HttpClientOptions(timeout: 30),
        );
    }

    protected function embeddings(): EmbeddingsProviderInterface
    {
        $provider = config('ai-docs.provider', 'schooltry');
        $providerConfig = config('ai-docs.providers')[$provider] ?? [];
        $url = $providerConfig['base_url'] ?? 'http://localhost:11434';
        $model = $providerConfig['model_embedding'] ?? 'nomic-embed-text';

        return new OllamaEmbedingProvider(
            url: $url,
            model: $model,
        );
    }



    protected function vectorStore(): VectorStoreInterface
    {
        return new FileVectorStore(
            directory:storage_path('app/'.config('ai-docs.vector_db.store_path')),
            topK: 4
        );
    }

}
