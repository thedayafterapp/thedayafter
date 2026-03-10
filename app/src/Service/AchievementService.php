<?php

namespace App\Service;

use App\Entity\Achievement;
use App\Entity\User;
use App\Entity\UserAchievement;
use App\Repository\AchievementRepository;
use App\Repository\CheckInRepository;
use App\Repository\JournalEntryRepository;
use Doctrine\ORM\EntityManagerInterface;

class AchievementService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AchievementRepository $achievementRepo,
        private readonly CheckInRepository $checkInRepo,
        private readonly JournalEntryRepository $journalRepo,
    ) {}

    /**
     * Check all achievements for user and award any newly earned ones.
     * Returns array of newly awarded Achievement objects.
     */
    public function checkAndAward(User $user): array
    {
        $earned = [];
        $earnedSlugs = $this->getEarnedSlugs($user);
        $days = $user->getDaysSinceQuit();
        $checkInCount = $this->checkInRepo->countByUser($user);
        $journalCount = $this->journalRepo->countByUser($user);
        $cravings = $user->getCravingsSurvived();

        $checks = [
            // Streak achievements
            'first-step'      => $days >= 1,
            'one-week'        => $days >= 7,
            'two-weeks'       => $days >= 14,
            'one-month'       => $days >= 30,
            'three-months'    => $days >= 90,
            'six-months'      => $days >= 180,
            'one-year'        => $days >= 365,
            // Check-in achievements
            'first-checkin'   => $checkInCount >= 1,
            'checkin-7'       => $checkInCount >= 7,
            'checkin-30'      => $checkInCount >= 30,
            // Journal achievements
            'first-journal'   => $journalCount >= 1,
            'journal-10'      => $journalCount >= 10,
            // Craving achievements
            'first-craving'   => $cravings >= 1,
            'craving-5'       => $cravings >= 5,
            'craving-20'      => $cravings >= 20,
            // Money achievements
            'saved-10'        => $user->getMoneySaved() >= 10,
            'saved-100'       => $user->getMoneySaved() >= 100,
            'saved-500'       => $user->getMoneySaved() >= 500,
        ];

        foreach ($checks as $slug => $condition) {
            if ($condition && !in_array($slug, $earnedSlugs)) {
                $achievement = $this->achievementRepo->findBySlug($slug);
                if ($achievement) {
                    $ua = new UserAchievement($user, $achievement);
                    $this->em->persist($ua);
                    $user->addXp($achievement->getXpReward());
                    $earned[] = $achievement;
                }
            }
        }

        if (!empty($earned)) {
            $this->em->flush();
        }

        return $earned;
    }

    private function getEarnedSlugs(User $user): array
    {
        return array_map(
            fn(UserAchievement $ua) => $ua->getAchievement()->getSlug(),
            $user->getUserAchievements()->toArray()
        );
    }

    public function getProgressToNext(User $user): array
    {
        $days = $user->getDaysSinceQuit();
        $milestones = [1, 7, 14, 30, 90, 180, 365];
        foreach ($milestones as $m) {
            if ($days < $m) {
                $prev = $days === 0 ? 0 : ($milestones[array_search($m, $milestones) - 1] ?? 0);
                $progress = $prev > 0 ? (($days - $prev) / ($m - $prev)) * 100 : ($days / $m) * 100;
                return [
                    'next_days' => $m,
                    'current_days' => $days,
                    'progress_pct' => min(100, round($progress)),
                    'days_left' => $m - $days,
                ];
            }
        }
        return ['next_days' => null, 'current_days' => $days, 'progress_pct' => 100, 'days_left' => 0];
    }
}
