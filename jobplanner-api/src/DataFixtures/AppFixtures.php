<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\AiGenerationLog;
use App\Entity\Application;
use App\Entity\ApplicationHistory;
use App\Entity\ApplicationHistoryActionType;
use App\Entity\ApplicationStatus;
use App\Entity\FollowUpRule;
use App\Entity\Interview;
use App\Entity\JobOffer;
use App\Entity\RecruiterEmail;
use App\Entity\ScheduledFollowUp;
use App\Entity\User;
use App\Entity\UserMailboxSettings;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $guest = $this->createGuestUser($manager);
        $jobOffers = $this->createJobOffers($manager, $guest);
        $applications = $this->createApplications($manager, $jobOffers, $guest);
        $this->createFollowUpRules($manager, $guest);
        $this->createUserMailboxSettings($manager, $guest);
        $this->createAiGenerationLogs($manager, $guest);
        $this->createApplicationHistory($manager, $applications);
        $this->createScheduledFollowUps($manager, $applications);
    }

    private function createGuestUser(ObjectManager $manager): User
    {
        $existing = $manager->getRepository(User::class)->findOneBy(['email' => 'guest@test.com']);
        if ($existing instanceof User) {
            return $existing;
        }

        $guest = new User();
        $guest->setEmail('guest@test.com');
        $guest->setPassword($this->passwordHasher->hashPassword($guest, 'guest'));
        $manager->persist($guest);

        return $guest;
    }

    /**
     * @return array<int, JobOffer>
     */
    private function createJobOffers(ObjectManager $manager, User $owner): array
    {
        $data = [
            ['Développeur Full Stack', 'TechCorp SAS', 'Paris', 'https://example.com/offre-1', 'Entretien technique prévu le 15/02', 'marie.dupont@techcorp.fr'],
            ['Ingénieur DevOps', 'CloudFactory', 'Lyon', 'https://example.com/offre-2', 'Relance envoyée', 'jean.martin@cloudfactory.io'],
            ['Lead Developer Vue.js', 'StartupHub', 'Toulouse (remote)', 'https://example.com/offre-3', '', 'rh@startuphub.co'],
            ['Architecte Solutions', 'BigTech France', 'Paris', 'https://example.com/offre-4', 'À postuler cette semaine', 'recrutement@bigtech.fr'],
            ['Product Manager', 'InnovateCo', 'Bordeaux', 'https://example.com/offre-5', 'Refus après 2 entretiens', 'sophie.bernard@innovateco.com'],
        ];

        $offers = [];
        foreach ($data as [$title, $company, $location, $url, $notes, $recruiterEmail]) {
            $offer = new JobOffer();
            $offer->setTitle($title);
            $offer->setCompany($company);
            $offer->setLocation($location);
            $offer->setUrl($url);
            $offer->setNotes($notes);
            $offer->setRecruiterContactEmail($recruiterEmail);
            $offer->setOwner($owner);
            $manager->persist($offer);
            $offers[] = $offer;
        }
        $manager->flush();

        return $offers;
    }

    /**
     * @param array<int, JobOffer> $jobOffers
     *
     * @return array<int, Application>
     */
    private function createApplications(ObjectManager $manager, array $jobOffers, User $owner): array
    {
        $appsData = [
            [0, ApplicationStatus::INTERVIEW, '2025-02-01', '2025-02-10'],
            [1, ApplicationStatus::OFFER, '2025-01-20', '2025-02-05'],
            [2, ApplicationStatus::APPLIED, '2025-02-08', null],
            [3, ApplicationStatus::WISHLIST, null, null],
            [4, ApplicationStatus::REJECTED, '2025-01-15', '2025-01-25'],
        ];

        $applications = [];
        foreach ($appsData as $i => [$offerIdx, $status, $appliedAt, $lastActivityAt]) {
            $offer = $jobOffers[$offerIdx];

            $app = new Application();
            $app->setJobOffer($offer);
            $app->setStatus($status);
            $app->setOwner($owner);
            $app->setPipelinePosition($i);
            if ($appliedAt) {
                $app->setAppliedAt(new \DateTimeImmutable($appliedAt));
            }
            if ($lastActivityAt) {
                $app->setLastActivityAt(new \DateTimeImmutable($lastActivityAt));
            }
            $manager->persist($app);
            $applications[] = $app;
        }
        $manager->flush();

        $this->createInterviews($manager, $applications);
        $this->createRecruiterEmails($manager, $applications);

        return $applications;
    }

    /**
     * @param array<int, Application> $applications
     */
    private function createRecruiterEmails(ObjectManager $manager, array $applications): void
    {
        $emailsData = [
            [0, 'marie.dupont@techcorp.fr', 'Convocation entretien technique - Développeur Full Stack', "Bonjour,\n\nNous avons bien reçu votre candidature pour le poste de Développeur Full Stack.\n\nNous vous convions à un entretien technique le 15 février 2025 à 14h.\n\nLien de connexion : https://meet.example.com/techcorp-interview\n\nN'hésitez pas à nous recontacter en cas de question.\n\nCordialement,\nMarie Dupont\nRecruteuse TechCorp SAS", '2025-02-10 09:30:00', 'Convocation entretien technique', 'INCOMING', true, false, false, ['Promising offers']],
            [0, 'marie.dupont@techcorp.fr', 'Re: Candidature Développeur Full Stack', "Bonjour,\n\nVotre profil nous intéresse beaucoup. Nous étudions actuellement les candidatures reçues et reviendrons vers vous sous peu pour un éventuel entretien.\n\nBonne journée,\nMarie", '2025-02-05 14:00:00', 'Accusé de réception positif', 'INCOMING', false, false, false, []],
            [1, 'jean.martin@cloudfactory.io', 'Re: Ingénieur DevOps - Candidature', "Bonjour,\n\nMerci pour votre relance. Nous sommes actuellement en train de finaliser nos recrutements pour le poste d'Ingénieur DevOps.\n\nJe vous recontacterai personnellement la semaine prochaine pour vous donner une réponse plus précise.\n\nBien cordialement,\nJean Martin\nResponsable technique CloudFactory", '2025-02-08 11:15:00', 'Réponse positive en attente', 'INCOMING', false, false, false, ['Work in Progress']],
            [2, 'rh@startuphub.co', 'Re: Lead Developer Vue.js', "Bonjour,\n\nVotre candidature pour le poste de Lead Developer Vue.js a bien été reçue.\n\nNous étudions actuellement tous les dossiers et nous reviendrons vers vous sous 2 semaines maximum.\n\nMerci de votre intérêt pour StartupHub.\n\nL'équipe RH", '2025-02-09 10:00:00', 'Accusé de réception standard', 'INCOMING', false, false, false, []],

            [0, 'Me', 'Re: Convocation entretien technique - Développeur Full Stack', "Bonjour Marie,\n\nMerci pour cette invitation. Je vous confirme ma présence pour l'entretien technique le 15 février à 14h.\n\nCordialement,\nSébastien", '2025-02-10 10:00:00', null, 'OUTGOING', false, false, false, []],

            [3, 'Me', 'Candidature spontanée', "Bonjour,\n\nJe souhaiterais vous proposer ma candidature pour...", '2025-02-12 12:00:00', null, 'OUTGOING', false, false, true, []],

            [4, 'newsletter@indeed.com', 'Nouvelles offres d\'emploi', 'Voici les nouvelles offres correspondant à votre profil...', '2025-01-30 08:00:00', 'Newsletter', 'INCOMING', false, true, false, ['Read later']],
        ];

        $msgIdx = 0;
        foreach ($emailsData as [$appIdx, $sender, $subject, $body, $receivedAt, $aiSummary, $direction, $isFav, $isDel, $isDraft, $labels]) {
            $email = new RecruiterEmail();
            $email->setApplication($applications[$appIdx]);
            $email->setOwner($applications[$appIdx]->getOwner());
            $email->setSender($sender);
            $email->setSubject($subject);
            $email->setBody($body);
            $email->setMessageId('fixture-msg-'.(++$msgIdx).'-'.bin2hex(random_bytes(8)));
            $email->setReceivedAt(new \DateTimeImmutable($receivedAt));
            $email->setAiSummary($aiSummary);

            $email->setDirection($direction);
            $email->setIsFavourite($isFav);
            $email->setIsDeleted($isDel);
            $email->setIsDraft($isDraft);
            $email->setLabels($labels);

            $manager->persist($email);
        }
        $manager->flush();
    }

    /**
     * @param array<int, Application> $applications
     */
    private function createInterviews(ObjectManager $manager, array $applications): void
    {
        $base = new \DateTimeImmutable('+3 days');
        $interviewsData = [
            [0, $base->format('Y-m-d').' 14:00', Interview::TYPE_VIDEO, 'Préparer démo du dernier projet', 'https://meet.example.com/xxx'],
            [1, $base->modify('+3 days')->format('Y-m-d').' 10:30', Interview::TYPE_PHONE, 'Entretien téléphonique RH', null],
            [2, $base->modify('+7 days')->format('Y-m-d').' 09:00', Interview::TYPE_ON_SITE, 'Entretien présentiel - Bureau Paris', null],
        ];

        foreach ($interviewsData as [$appIdx, $scheduledAt, $type, $notes, $link]) {
            $interview = new Interview();
            $interview->setApplication($applications[$appIdx]);
            $interview->setScheduledAt(new \DateTimeImmutable($scheduledAt));
            $interview->setType($type);
            $interview->setNotes($notes);
            $interview->setLocationOrLink($link);
            $manager->persist($interview);
        }
        $manager->flush();
    }

    private function createFollowUpRules(ObjectManager $manager, User $owner): void
    {
        $rules = [
            [7, FollowUpRule::TEMPLATE_FOLLOW_UP, true],
            [14, FollowUpRule::TEMPLATE_FOLLOW_UP, true],
        ];
        foreach ($rules as [$days, $template, $enabled]) {
            $rule = new FollowUpRule();
            $rule->setOwner($owner);
            $rule->setDaysWithoutReply($days);
            $rule->setTemplateType($template);
            $rule->setEnabled($enabled);
            $manager->persist($rule);
        }
        $manager->flush();
    }

    private function createUserMailboxSettings(ObjectManager $manager, User $user): void
    {
        $existing = $manager->getRepository(UserMailboxSettings::class)->findOneBy(['user' => $user]);
        if ($existing instanceof UserMailboxSettings) {
            return;
        }

        $settings = new UserMailboxSettings();
        $settings->setUser($user);
        $settings->setImapHost('imap.example.com');
        $settings->setImapPort(993);
        $settings->setImapEncryption('ssl');
        $settings->setImapUser('guest@test.com');
        $settings->setImapPassword('guest');
        $settings->setImapFolder('INBOX');
        $settings->setIsActive(true);
        $settings->setSmtpHost('mailpit');
        $settings->setSmtpPort(1025);
        $settings->setSmtpEncryption('none');
        $settings->setSmtpUser('guest@test.com');
        $settings->setSmtpPassword('guest');
        $manager->persist($settings);
        $manager->flush();
    }

    private function createAiGenerationLogs(ObjectManager $manager, User $user): void
    {
        $logsData = [
            ['follow_up', 'Génère une relance pour l\'offre TechCorp...', 150],
            ['thank_you', 'Génère un mail de remerciement après entretien...', 120],
            ['spontaneous', 'Génère une candidature spontanée pour StartupHub...', 200],
        ];

        foreach ($logsData as [$type, $prompt, $tokens]) {
            $log = new AiGenerationLog();
            $log->setUser($user);
            $log->setType($type);
            $log->setPrompt($prompt);
            $log->setTokensUsed($tokens);
            $manager->persist($log);
        }
        $manager->flush();
    }

    /**
     * @param array<int, Application> $applications
     */
    private function createApplicationHistory(ObjectManager $manager, array $applications): void
    {
        $historyData = [
            [0, ApplicationHistoryActionType::CREATED, 'Candidature créée'],
            [0, ApplicationHistoryActionType::STATUS_CHANGED, 'Statut: Wishlist → Postulé'],
            [0, ApplicationHistoryActionType::EMAIL_RECEIVED, 'Email reçu de marie.dupont@techcorp.fr'],
            [0, ApplicationHistoryActionType::INTERVIEW_SCHEDULED, 'Entretien technique programmé le 15/02'],
            [1, ApplicationHistoryActionType::CREATED, 'Candidature créée'],
            [1, ApplicationHistoryActionType::RELANCE_SENT, 'Relance envoyée à jean.martin@cloudfactory.io'],
            [2, ApplicationHistoryActionType::CREATED, 'Candidature créée'],
            [2, ApplicationHistoryActionType::STATUS_CHANGED, 'Statut: Wishlist → Postulé'],
        ];

        foreach ($historyData as [$appIdx, $actionType, $description]) {
            $history = new ApplicationHistory();
            $history->setApplication($applications[$appIdx]);
            $history->setActionType($actionType);
            $history->setDescription($description);
            $manager->persist($history);
        }
        $manager->flush();
    }

    /**
     * @param array<int, Application> $applications
     */
    private function createScheduledFollowUps(ObjectManager $manager, array $applications): void
    {
        $scheduledData = [
            [1, '+2 days 10:00', ScheduledFollowUp::STATUS_PENDING, 'Relance prévue si pas de réponse sous 7 jours'],
            [2, '+5 days 14:00', ScheduledFollowUp::STATUS_PENDING, null],
        ];

        foreach ($scheduledData as [$appIdx, $relative, $status, $content]) {
            $scheduled = new ScheduledFollowUp();
            $scheduled->setApplication($applications[$appIdx]);
            $scheduled->setScheduledAt(new \DateTimeImmutable($relative));
            $scheduled->setStatus($status);
            $scheduled->setGeneratedContent($content);
            $manager->persist($scheduled);
        }
        $manager->flush();
    }
}
