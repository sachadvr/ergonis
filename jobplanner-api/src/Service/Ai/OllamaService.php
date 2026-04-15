<?php

declare(strict_types=1);

namespace App\Service\Ai;

use App\Entity\Application;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OllamaService extends AbstractAiService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        \App\Service\CompanyExtractor $companyExtractor,
        private readonly LoggerInterface $logger,
        private readonly string $baseUrl = 'http://localhost:11434',
        private readonly string $model = 'bjoernb/gemma4-31b-think:latest',
    ) {
        parent::__construct($companyExtractor);
    }

    public function extractJobOfferFromContent(string $url, string $title, string $content): array
    {
        $response = null;
        try {
            $userContent = "URL: {$url}\nTitre: {$title}\n\nContenu:\n".substr($content, 0, 4000);
            $this->logger->warning('Ollama extraction prompt size', [
                'system_len' => strlen(self::JOB_EXTRACTION_PROMPT),
                'user_len' => strlen($userContent),
                'content_len' => strlen($content),
                'title_len' => strlen($title),
                'url_len' => strlen($url),
            ]);

            $response = $this->httpClient->request('POST', $this->baseUrl.'/api/chat', [
                'timeout' => 300,
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => self::JOB_EXTRACTION_PROMPT,
                        ],
                        [
                            'role' => 'user',
                            'content' => $userContent,
                        ],
                    ],
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.3,
                    ],
                ],
            ]);

            $data = $response->toArray();
            $text = $this->stripThinkingTags($data['message']['content'] ?? '{}');
            $decoded = $this->extractJsonObject($text);
            if (\is_array($decoded)) {
                return $this->buildExtractionResult($decoded, $title, $content, $text);
            }
        } catch (\Throwable $exception) {
            $this->logger->warning('Ollama job offer extraction failed', [
                'error' => $exception->getMessage(),
                'status' => $response?->getStatusCode(),
                'body' => $response?->getContent(false),
            ]);
        }

        return $this->buildFallbackExtraction($title, $content);
    }

    public function analyzeApplicationFit(Application $application, string $cvText): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->baseUrl.'/api/chat', [
                'timeout' => 300,
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => self::APPLICATION_FIT_PROMPT,
                        ],
                        [
                            'role' => 'user',
                            'content' => $this->buildApplicationFitContext($application, $cvText),
                        ],
                    ],
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.2,
                    ],
                ],
            ]);

            $data = $response->toArray();
            $text = $this->stripThinkingTags($data['message']['content'] ?? '{}');
            $decoded = $this->extractJsonObject($text);

            if (is_array($decoded)) {
                return $decoded;
            }
        } catch (\Throwable) {
            // fallback
        }

        return [
            'overall_fit' => [
                'score' => 0,
                'level' => 'unknown',
                'recommendation' => 'analysis_failed',
            ],
            'summary' => 'Analyse IA indisponible.',
            'strong_matches' => [],
            'gaps' => [],
            'ats_keywords_to_add' => [],
            'cv_customization_points' => [],
            'motivation_letter_points' => [],
            'interview_topics_to_prepare' => [],
            'red_flags_or_unclear_points' => [],
        ];
    }

    public function generateFollowUpEmail(Application $application, string $tone = 'professionnel'): string
    {
        $jobOffer = $application->getJobOffer();
        $poste = $jobOffer->getTitle();
        $entreprise = $jobOffer->getCompany();

        try {
            $response = $this->httpClient->request('POST', $this->baseUrl.'/api/chat', [
                'timeout' => 300,
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "Tu es un assistant qui rédige des emails de relance pour des candidatures. Ton: {$tone}. Pas de formules trop longues. Maximum 150 mots.",
                        ],
                        [
                            'role' => 'user',
                            'content' => "Génère un email de relance pour ma candidature au poste de {$poste} chez {$entreprise}. Je n'ai pas eu de réponse.",
                        ],
                    ],
                    'stream' => false,
                ],
            ]);
            $data = $response->toArray();
            $text = $this->stripThinkingTags($data['message']['content'] ?? '');

            return trim($text) ?: $this->getFallbackEmail($poste, $entreprise);
        } catch (\Throwable) {
            return $this->getFallbackEmail($poste, $entreprise);
        }
    }

    public function summarizeEmail(string $emailBody): string
    {
        if (strlen($emailBody) < 200) {
            return substr($emailBody, 0, 500).(strlen($emailBody) > 500 ? '...' : '');
        }

        try {
            $response = $this->httpClient->request('POST', $this->baseUrl.'/api/chat', [
                'timeout' => 300,
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Résume en une phrase courte (max 100 caractères).'],
                        ['role' => 'user', 'content' => substr($emailBody, 0, 2000)],
                    ],
                    'stream' => false,
                ],
            ]);
            $data = $response->toArray();
            $text = $this->stripThinkingTags($data['message']['content'] ?? '');

            return trim($text) ?: substr($emailBody, 0, 100).'...';
        } catch (\Throwable) {
            return substr($emailBody, 0, 500).(strlen($emailBody) > 500 ? '...' : '');
        }
    }

    public function suggestReplies(string $emailBody): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->baseUrl.'/api/chat', [
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Propose 2 ou 3 réponses courtes possibles (une par ligne, préfixe "- ").'],
                        ['role' => 'user', 'content' => substr($emailBody, 0, 1500)],
                    ],
                    'stream' => false,
                ],
            ]);
            $data = $response->toArray();
            $text = $this->stripThinkingTags($data['message']['content'] ?? '');

            return $this->parseReplySuggestions($text);
        } catch (\Throwable) {
            return [];
        }
    }

    private function stripThinkingTags(string $text): string
    {
        // Remove <think>...</think> blocks if present (common in "think" models)
        return preg_replace('/<think>.*?<\/think>/s', '', $text);
    }
}
