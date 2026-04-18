<?php

declare(strict_types=1);

namespace App\Service\Ai;

use App\Entity\Application;

interface AiServiceInterface
{
    public const JOB_EXTRACTION_PROMPT = <<<PROMPT
Extract ATS-useful candidate data from 1 job offer. Return only JSON.

Rules:
- Use only explicit posting content
- No invention
- Missing/unclear -> null or []
- No empty strings
- Use the same language as the posting for free-text fields; keep enum values exactly as specified
- salary.min/max: number only if clearly stated, else null
- Booleans: true only if explicit; else conservative false
- Keep ATS-relevant wording when useful
- Deduplicate similar items
- Separate required vs preferred strictly
- keywords_ats only from posting
- Keep values short, normalized, candidate-useful
- Prefer short labels
- recruiter_contact_email = exact lowercase email if explicitly shown

Schema:
job_title,
company_name,
location{city,region,country,remote_policy,travel_required:false},
contract{type,duration,start_date,work_time},
salary{min,max,currency,period,raw_text},
job_summary,
recruiter_contact_email,
main_responsibilities[],
required_skills{technical[],tools[],soft_skills[],languages[],certifications[],education[],experience[]},
preferred_skills{technical[],tools[],soft_skills[],languages[],certifications[],education[],experience[]},
keywords_ats[],
candidate_fit{seniority_level,target_profile,main_selection_criteria[],must_have_keywords[],nice_to_have_keywords[]},
application_insights{cv_customization_points[],motivation_letter_points[],interview_topics_to_prepare[]},
benefits[],
recruitment_process[],
red_flags_or_unclear_points[],
source_language,
raw_offer_cleaned.

Meanings:
- required_skills = explicit must-have items
- preferred_skills = explicit nice-to-have items
- keywords_ats = exact/near-exact posting terms
- application_insights = candidate advice based only on posting
- red_flags_or_unclear_points = ambiguity, contradiction, missing info, absent salary, unclear seniority, unrealistic scope
- raw_offer_cleaned = cleaned posting text without repeated/noisy formatting
PROMPT;

    public const APPLICATION_FIT_PROMPT = <<<PROMPT
Analyze fit between 1 job offer and 1 CV for ATS + candidate feedback. Return only JSON.

Rules:
- Use only explicit offer + CV content
- No invention
- Missing/unclear -> null or []
- No empty strings
- Use the language of the offer/CV for free-text fields; keep enum values exactly as specified
- Keep ATS-relevant wording when useful
- Be candid on matches, gaps, unclear points
- Focus on actionable improvements (CV, cover letter, interview)

Schema:
overall_fit{score:0-100,level:"strong|medium|weak",recommendation:"apply|apply_with_adjustments|not_a_fit"},
summary,
strong_matches[],
gaps[],
ats_keywords_to_add[],
cv_customization_points[],
motivation_letter_points[],
interview_topics_to_prepare[],
red_flags_or_unclear_points[].

Meanings:
- strong_matches = clear alignment CV vs offer
- gaps = missing key skills/experience/keywords
- ats_keywords_to_add = add only if truthful
- red_flags_or_unclear_points = ambiguity, contradiction, missing info, unrealistic fit
PROMPT;

    /**
     * Extracts and structures the information from a job offer from the raw content.
     *
     * @return array<string, mixed>
     */
    public function extractJobOfferFromContent(string $url, string $title, string $content): array;

    /**
     * Analyzes the fit between a job offer and a text CV.
     *
     * @return array<string, mixed>
     */
    public function analyzeApplicationFit(Application $application, string $cvText): array;

    /**
     * Generates a follow-up email adapted to the application.
     */
    public function generateFollowUpEmail(Application $application, string $tone = 'professionnel'): string;

    /**
     * Summarizes the content of an email.
     */
    public function summarizeEmail(string $emailBody): string;

    /**
     * Suggests replies ready to send.
     *
     * @return array<string>
     */
    public function suggestReplies(string $emailBody): array;
}
