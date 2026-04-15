<?php

declare(strict_types=1);

namespace App\Service\Ai;

use App\Entity\Application;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class AnthropicService extends AbstractAiService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        \App\Service\CompanyExtractor $companyExtractor,
        private readonly string $apiKey,
        private readonly string $model = 'claude-3-5-haiku-20241022',
    ) {
        parent::__construct($companyExtractor);
    }

    public function extractJobOfferFromContent(string $url, string $title, string $content): array
    {
        if ('' === $this->apiKey) {
            return $this->buildFallbackExtraction($title, $content);
        }

        try {
            $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'max_tokens' => 2048,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => self::JOB_EXTRACTION_PROMPT."\n\nURL: {$url}, Titre: {$title}\n\nContenu:\n".substr($content, 0, 8000),
                        ],
                    ],
                    'system' => 'Réponds uniquement en JSON valide.',
                ],
            ]);
            $data = $response->toArray();
            $text = $data['content'][0]['text'] ?? '{}';
            $decoded = $this->extractJsonObject($text);
            if (\is_array($decoded)) {
                return $this->buildExtractionResult($decoded, $title, $content, $text);
            }
        } catch (\Throwable) {
            // fallback
        }

        return $this->buildFallbackExtraction($title, $content);
    }

    public function analyzeApplicationFit(Application $application, string $cvText): array
    {
        try {
            $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'max_tokens' => 1800,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $this->buildApplicationFitContext($application, $cvText),
                        ],
                    ],
                    'system' => self::APPLICATION_FIT_PROMPT,
                ],
            ]);

            $data = $response->toArray();
            $text = $data['content'][0]['text'] ?? '{}';
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

        if ('' === $this->apiKey) {
            return "Bonjour,\n\nJ'ai le plaisir de vous contacter concernant ma candidature au poste de {$poste} chez {$entreprise}.\n\nJe reste à votre disposition pour tout complément d'information.\n\nCordialement";
        }

        try {
            $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'max_tokens' => 512,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => "Génère un email de relance pour ma candidature au poste de {$poste} chez {$entreprise}. Ton: {$tone}. Pas de réponse reçue. Max 150 mots.",
                        ],
                    ],
                    'system' => 'Tu rédiges des emails professionnels de candidature.',
                ],
            ]);
            $data = $response->toArray();

            return trim($data['content'][0]['text'] ?? '') ?: $this->getFallbackEmail($poste, $entreprise);
        } catch (\Throwable) {
            return $this->getFallbackEmail($poste, $entreprise);
        }
    }

    public function summarizeEmail(string $emailBody): string
    {
        if ('' === $this->apiKey || strlen($emailBody) < 200) {
            return substr($emailBody, 0, 500).(strlen($emailBody) > 500 ? '...' : '');
        }

        try {
            $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'max_tokens' => 128,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => 'Résume en une phrase (max 100 chars): '.substr($emailBody, 0, 2000),
                        ],
                    ],
                ],
            ]);
            $data = $response->toArray();

            return trim($data['content'][0]['text'] ?? '') ?: substr($emailBody, 0, 100).'...';
        } catch (\Throwable) {
            return substr($emailBody, 0, 500).(strlen($emailBody) > 500 ? '...' : '');
        }
    }

    public function suggestReplies(string $emailBody): array
    {
        if ('' === $this->apiKey) {
            return [];
        }

        try {
            $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'max_tokens' => 256,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => 'Propose 2-3 réponses courtes possibles (une par ligne, préfixe "- "): '.substr($emailBody, 0, 1500),
                        ],
                    ],
                ],
            ]);
            $data = $response->toArray();
            $text = trim($data['content'][0]['text'] ?? '');

            return $this->parseReplySuggestions($text);
        } catch (\Throwable) {
            return [];
        }
    }
}
