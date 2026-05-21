<?php

declare(strict_types=1);

namespace App\Service\Ai;

use App\Entity\Application;
use App\Entity\ScheduledFollowUp;

final class FollowUpEmailPromptBuilder
{
    /**
     * @return array{system: string, user: string}
     */
    public static function build(Application $application, string $tone, string $templateType): array
    {
        $jobOffer = $application->getJobOffer();
        $offerTitle = $jobOffer->getTitle();
        $company = $jobOffer->getCompany();
        $owner = $application->getOwner();
        $senderEmail = $owner?->getUserIdentifier() ?? '';
        $appliedAt = $application->getAppliedAt()?->format('d/m/Y') ?? '';
        $appliedLine = '' !== $appliedAt ? "Application sent on: {$appliedAt}." : '';

        $sharedSystem = "You write the BODY of an email only — never a subject line, never an 'Objet:' line. "
            ."Use the same language as the offer title. Tone: {$tone}. Max 150 words. "
            ."Do NOT include any placeholders like [Name], [Date], [Phone], [LinkedIn], brackets, or instructions to fill in. "
            ."Sign with the sender email as-is: {$senderEmail}. No fake personal details. "
            ."Output the email body only — no preamble, no markdown, no quotes around it.";

        return match ($templateType) {
            ScheduledFollowUp::TEMPLATE_THANK_YOU => [
                'system' => $sharedSystem,
                'user' => "Write a short thank-you email after my interview for the position of {$offerTitle} at {$company}. "
                    ."Thank the interviewer briefly, restate genuine interest, mention one concrete fit point if natural, and close politely. {$appliedLine}",
            ],
            ScheduledFollowUp::TEMPLATE_SPONTANEOUS => [
                'system' => $sharedSystem,
                'user' => "Write a spontaneous application email to {$company} for a role like {$offerTitle}. "
                    ."State motivation in 1-2 sentences, give one concrete strength, and offer availability for a short call. Close politely.",
            ],
            default => [
                'system' => $sharedSystem,
                'user' => "Write a polite follow-up email for my application for the position of {$offerTitle} at {$company}. "
                    ."{$appliedLine} I have not received a reply. Reiterate interest in one short sentence and ask about the status. Close politely.",
            ],
        };
    }
}
