<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CheckInRepository;
use App\Repository\JournalEntryRepository;
use App\Service\AchievementService;
use App\Service\QuoteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        CheckInRepository $checkInRepo,
        JournalEntryRepository $journalRepo,
        AchievementService $achievementService,
        QuoteService $quoteService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $newAchievements = $achievementService->checkAndAward($user);
        foreach ($newAchievements as $a) {
            $this->addFlash('achievement', $a->getIcon() . ' Achievement unlocked: ' . $a->getName() . '! +' . $a->getXpReward() . ' XP');
        }

        $recentCheckIns  = $checkInRepo->findRecentByUser($user, 7);
        $recentJournals  = $journalRepo->findRecentByUser($user, 3);
        $progress        = $achievementService->getProgressToNext($user);
        $checkedInToday  = $checkInRepo->hadCheckInToday($user);
        $weeklyStats     = $checkInRepo->getWeeklyStats($user);
        $triggerAnalysis = $checkInRepo->getTriggerAnalysis($user);
        $quote           = $quoteService->getDailyQuote();

        // Build tracks for the dashboard
        $tracks = $this->buildTracks($user);

        $xpProgress = $user->getXpForNextLevel() > 0
            ? min(100, round(($user->getTotalXp() / $user->getXpForNextLevel()) * 100))
            : 100;

        return $this->render('dashboard/index.html.twig', [
            'user'             => $user,
            'tracks'           => $tracks,
            'recent_checkins'  => $recentCheckIns,
            'recent_journals'  => $recentJournals,
            'progress'         => $progress,
            'checked_in_today' => $checkedInToday,
            'weekly_stats'     => $weeklyStats,
            'trigger_analysis' => $triggerAnalysis,
            'quote'            => $quote,
            'xp_progress'      => $xpProgress,
        ]);
    }

    private function buildTracks(User $user): array
    {
        $type = $user->getAddictionType();
        $tracks = [];

        if ($type === 'alcohol' || $type === 'both') {
            $days  = $user->getDaysSinceQuit('alcohol');
            $hours = $user->getHoursSinceQuit('alcohol');
            $tracks['alcohol'] = [
                'label'     => 'Alcohol Free',
                'icon'      => '🍷',
                'color'     => 'from-red-500 to-pink-600',
                'days'      => $days,
                'quit_date' => $user->getQuitDateFor('alcohol'),
                'milestones'=> $this->getHealthMilestones('alcohol', $hours),
                'money'     => $user->getMoneySaved('alcohol'),
            ];
        }

        if ($type === 'cigarettes' || $type === 'both') {
            $days  = $user->getDaysSinceQuit('cigarettes');
            $hours = $user->getHoursSinceQuit('cigarettes');
            $tracks['cigarettes'] = [
                'label'     => 'Smoke Free',
                'icon'      => '🚬',
                'color'     => 'from-amber-500 to-orange-600',
                'days'      => $days,
                'quit_date' => $user->getQuitDateFor('cigarettes'),
                'milestones'=> $this->getHealthMilestones('cigarettes', $hours),
                'money'     => $user->getMoneySaved('cigarettes'),
            ];
        }

        if ($type === 'cannabis') {
            $days  = $user->getDaysSinceQuit('cannabis');
            $hours = $user->getHoursSinceQuit('cannabis');
            $tracks['cannabis'] = [
                'label'     => 'Cannabis Free',
                'icon'      => '🌿',
                'color'     => 'from-green-500 to-emerald-600',
                'days'      => $days,
                'quit_date' => $user->getQuitDateFor('cannabis'),
                'milestones'=> $this->getHealthMilestones('cannabis', $hours),
                'money'     => $user->getMoneySaved('cannabis'),
            ];
        }

        return $tracks;
    }

    private function getHealthMilestones(string $type, float $hours): array
    {
        $alcohol = [
            ['hours' => 12,    'label' => '12 Hours',  'desc' => 'Blood sugar normalizes, body starts detox'],
            ['hours' => 24,    'label' => '1 Day',     'desc' => 'Body actively clearing toxins'],
            ['hours' => 72,    'label' => '3 Days',    'desc' => 'Withdrawal peaks then decreases'],
            ['hours' => 168,   'label' => '1 Week',    'desc' => 'Skin improving, hydration restored'],
            ['hours' => 336,   'label' => '2 Weeks',   'desc' => 'Liver inflammation reduces'],
            ['hours' => 720,   'label' => '1 Month',   'desc' => 'Liver functioning better, mental clarity'],
            ['hours' => 2160,  'label' => '3 Months',  'desc' => 'Immune system stronger, memory sharpening'],
            ['hours' => 4320,  'label' => '6 Months',  'desc' => 'Blood pressure normalizing'],
            ['hours' => 8760,  'label' => '1 Year',    'desc' => 'Cancer risk reducing, heart strengthening'],
        ];

        $cigarettes = [
            ['hours' => 0.333, 'label' => '20 Minutes','desc' => 'Heart rate and blood pressure drop'],
            ['hours' => 8,     'label' => '8 Hours',   'desc' => 'Carbon monoxide levels halve'],
            ['hours' => 24,    'label' => '1 Day',     'desc' => 'Risk of heart attack decreases'],
            ['hours' => 48,    'label' => '2 Days',    'desc' => 'Smell and taste start returning'],
            ['hours' => 72,    'label' => '3 Days',    'desc' => 'Breathing becomes easier'],
            ['hours' => 336,   'label' => '2 Weeks',   'desc' => 'Circulation improves'],
            ['hours' => 720,   'label' => '1 Month',   'desc' => 'Coughing reduces significantly'],
            ['hours' => 2160,  'label' => '3 Months',  'desc' => 'Lung function up 30%'],
            ['hours' => 8760,  'label' => '1 Year',    'desc' => 'Heart disease risk halved'],
        ];

        $cannabis = [
            ['hours' => 24,   'label' => '1 Day',     'desc' => 'THC levels dropping, appetite normalizing'],
            ['hours' => 72,   'label' => '3 Days',    'desc' => 'Withdrawal peaks — irritability, insomnia pass soon'],
            ['hours' => 168,  'label' => '1 Week',    'desc' => 'Sleep begins to improve, mood lifting'],
            ['hours' => 336,  'label' => '2 Weeks',   'desc' => 'Memory and focus noticeably sharper'],
            ['hours' => 720,  'label' => '1 Month',   'desc' => 'Mood stabilized, lung function improving'],
            ['hours' => 2160, 'label' => '3 Months',  'desc' => 'Brain fog gone, cognitive function restored'],
            ['hours' => 4320, 'label' => '6 Months',  'desc' => 'Memory and attention largely back to baseline'],
            ['hours' => 8760, 'label' => '1 Year',    'desc' => "Brain's reward system substantially healed"],
        ];

        return array_map(fn($m) => array_merge($m, ['achieved' => $hours >= $m['hours']]),
            match($type) {
                'cigarettes' => $cigarettes,
                'cannabis'   => $cannabis,
                default      => $alcohol,
            }
        );
    }
}
