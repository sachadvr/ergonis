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
                'summary' => 'No AI provider is configured.',
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
                            'content' => "You are an assistant who writes follow-up emails for applications in the same language as the offer. Your tone: {$tone}. No too long formulas. Maximum 150 words.",
                        ],
                        [
                            'role' => 'user',
                            'content' => "Generate a follow-up email for my application for the post of {$offerTitle} at {$company}. Use the same language as the offer. I have not received a response.",
                        ],
                    ],
                    'temperature' => 0.7,
                ],
            ]);
            $data = $response->toArray();

            return trim($data['choices'][0]['message']['content'] ?? '') ?: $this->getFallbackEmail($offerTitle, $company);
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
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Summarize in one short sentence (max 100 characters) in the same language as the email.'],
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
                        ['role' => 'system', 'content' => 'Propose 2 or 3 short possible replies in the same language as the email (one per line, prefix "- ").'],
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
