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
                    'system' => 'Respond only with valid JSON.',
                ],
            ]);
            $data = $response->toArray();
            $text = $data['content'][0]['text'] ?? '{}';
            $decoded = $this->extractJsonObject($text);
            if (\is_array($decoded)) {
                return $this->buildExtractionResult($decoded, $title, $content, $text);
            }
        } catch (\Throwable) {
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
        }

        return [
            'overall_fit' => [
                'score' => 0,
                'level' => 'unknown',
                'recommendation' => 'analysis_failed',
            ],
            'summary' => 'AI analysis unavailable.',
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
        $offerTitle = $jobOffer->getTitle();
        $company = $jobOffer->getCompany();

        if ('' === $this->apiKey) {
            return "Hello,\n\nI have the pleasure of contacting you regarding my application for the post of {$offerTitle} at {$company}.\n\nI remain at your disposal for any additional information.\n\nSincerely";
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
                            'content' => "Generate a follow-up email for my application for the post of {$offerTitle} at {$company}. Your tone: {$tone}. No response received. Max 150 words.",
                        ],
                    ],
                    'system' => 'You write professional application emails.',
                ],
            ]);
            $data = $response->toArray();

            return trim($data['content'][0]['text'] ?? '') ?: $this->getFallbackEmail($offerTitle, $company);
        } catch (\Throwable) {
            return $this->getFallbackEmail($offerTitle, $company);
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
                            'content' => 'Summarize in one sentence (max 100 chars): '.substr($emailBody, 0, 2000),
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
                            'content' => 'Propose 2-3 short possible replies (one per line, prefix "- "): '.substr($emailBody, 0, 1500),
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
