<?php

declare(strict_types=1);

namespace SchoolTry\AIDocumentationGenerator\Services\Agent;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use NeuronAI\RAG\Embeddings\AbstractEmbeddingsProvider;

use function json_decode;
use function trim;

class OllamaEmbedingProvider extends AbstractEmbeddingsProvider
{
    protected Client $client;

    public function __construct(
        protected string $model,
        protected string $url = 'http://localhost:8000/api',
        protected string $route = 'embeding',
        protected array $parameters = [],
    ) {
        $this->client = new Client(['base_uri' => trim($this->url, '/').'/']);
    }

    public function embedText(string $text): array
    {
        try{
            
            $response = $this->client->post($this->route, [
                RequestOptions::JSON => [
                    'model' => $this->model,
                    'input' => $text,
                    ...$this->parameters,
                    ]
                    ])->getBody()->getContents();
                $response = json_decode($response, true);
            return $response['embedding'];
        }catch(\Exception $e){

            \Log::error($e);
        }
        
    }
}
