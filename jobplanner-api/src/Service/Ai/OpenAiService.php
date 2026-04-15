<?php

declare(strict_types=1);

namespace App\Service\Ai;

use App\Entity\Application;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OpenAiService extends AbstractAiService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        \App\Service\CompanyExtractor $companyExtractor,
        private readonly string $apiKey,
        private readonly string $model = 'gpt-4o-mini',
    ) {
        parent::__construct($companyExtractor);
    }

    public function extractJobOfferFromContent(string $url, string $title, string $content): array
    {
        if ('' === $this->apiKey) {
            return $this->buildFallbackExtraction($title, $content);
        }

        try {
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => self::JOB_EXTRACTION_PROMPT,
                        ],
                        [
                            'role' => 'user',
                            'content' => "URL: {$url}\nTitre: {$title}\n\nContenu:\n".substr($content, 0, 4000),
                        ],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 2048,
                ],
            ]);
            $data = $response->toArray();
            $text = $data['choices'][0]['message']['content'] ?? '{}';
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
        if ('' === $this->apiKey) {
            return [
                'overall_fit' => [
                    'score' => 0,
                    'level' => 'unknown',
                    'recommendation' => 'provider_not_configured',
                ],
                'summary' => 'Aucun provider IA n\'est configuré.',
                'strong_matches' => [],
                'gaps' => [],
                'ats_keywords_to_add' => [],
                'cv_customization_points' => [],
                'motivation_letter_points' => [],
                'interview_topics_to_prepare' => [],
                'red_flags_or_unclear_points' => [],
            ];
        }

        try {
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ],
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
                    'temperature' => 0.2,
                    'max_tokens' => 1800,
                    'response_format' => ['type' => 'json_object'],
                ],
            ]);

            $data = $response->toArray();
            $text = $data['choices'][0]['message']['content'] ?? '{}';
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
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ],
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
                    'temperature' => 0.7,
                ],
            ]);
            $data = $response->toArray();

            return trim($data['choices'][0]['message']['content'] ?? '') ?: $this->getFallbackEmail($poste, $entreprise);
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
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Résume en une phrase courte (max 100 caractères).'],
                        ['role' => 'user', 'content' => substr($emailBody, 0, 2000)],
                    ],
                    'temperature' => 0.3,
                ],
            ]);
            $data = $response->toArray();

            return trim($data['choices'][0]['message']['content'] ?? '') ?: substr($emailBody, 0, 100).'...';
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
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Propose 2 ou 3 réponses courtes possibles (une par ligne, préfixe "- ").'],
                        ['role' => 'user', 'content' => substr($emailBody, 0, 1500)],
                    ],
                    'temperature' => 0.3,
                ],
            ]);
            $data = $response->toArray();
            $text = trim($data['choices'][0]['message']['content'] ?? '');

            return $this->parseReplySuggestions($text);
        } catch (\Throwable) {
            return [];
        }
    }
}
