<?php

declare(strict_types=1);

namespace App\Service\Ai;

final class AiServiceFactory
{
    public function __construct(
        private readonly NullAiService $nullAi,
        private readonly OpenAiService $openAi,
        private readonly AnthropicService $anthropic,
        private readonly OllamaService $ollama,
        private readonly string $provider,
    ) {
    }

    public function get(): AiServiceInterface
    {
        return match (strtolower($this->provider)) {
            'openai' => $this->openAi,
            'anthropic' => $this->anthropic,
            'ollama' => $this->ollama,
            default => $this->nullAi,
        };
    }
}
