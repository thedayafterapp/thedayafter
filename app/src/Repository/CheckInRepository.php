<?php

namespace App\Repository;

use App\Entity\CheckIn;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CheckInRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CheckIn::class);
    }

    public function findRecentByUser(User $user, int $limit = 7): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hadCheckInToday(User $user): bool
    {
        $today    = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        $count = (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.user = :user AND c.createdAt >= :today AND c.createdAt < :tomorrow')
            ->setParameter('user', $user)
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();
        return $count > 0;
    }

    public function getWeeklyStats(User $user): array
    {
        $weekStart = new \DateTime('monday this week midnight');
        $checkIns  = $this->createQueryBuilder('c')
            ->where('c.user = :user AND c.createdAt >= :start')
            ->setParameter('user', $user)
            ->setParameter('start', $weekStart)
            ->getQuery()
            ->getResult();

        $moodSum = 0;
        $cravingSum = 0;
        foreach ($checkIns as $ci) {
            $moodSum    += $ci->getMood();
            $cravingSum += $ci->getCravingIntensity();
        }
        $count = count($checkIns);

        return [
            'checkin_count' => $count,
            'avg_mood'      => $count > 0 ? round($moodSum / $count, 1) : null,
            'avg_craving'   => $count > 0 ? round($cravingSum / $count, 1) : null,
        ];
    }

    public function getTriggerAnalysis(User $user): ?array
    {
        $checkIns = $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(60)
            ->getQuery()
            ->getResult();

        if (count($checkIns) < 5) return null;

        $triggerCounts = [];
        $dayNames      = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $dayCravings   = array_fill(0, 7, 0);
        $dayTotal      = array_fill(0, 7, 0);

        foreach ($checkIns as $ci) {
            $dow = (int) $ci->getCreatedAt()->format('w');
            $dayTotal[$dow]++;
            if ($ci->getCravingIntensity() >= 5) {
                $dayCravings[$dow]++;
            }
            foreach ($ci->getTriggers() as $trigger) {
                $triggerCounts[$trigger] = ($triggerCounts[$trigger] ?? 0) + 1;
            }
        }

        arsort($triggerCounts);
        $topTriggers = array_slice($triggerCounts, 0, 3, true);

        // Find worst craving day
        $worstDay     = null;
        $worstDayPct  = 0;
        foreach ($dayCravings as $dow => $count) {
            if ($dayTotal[$dow] > 0) {
                $pct = $count / $dayTotal[$dow];
                if ($pct > $worstDayPct) {
                    $worstDayPct = $pct;
                    $worstDay    = $dayNames[$dow];
                }
            }
        }

        return [
            'top_triggers' => $topTriggers,
            'worst_day'    => $worstDay,
            'worst_day_pct' => round($worstDayPct * 100),
            'total'        => count($checkIns),
        ];
    }
}
