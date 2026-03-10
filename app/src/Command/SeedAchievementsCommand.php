<?php

namespace App\Command;

use App\Entity\Achievement;
use App\Repository\AchievementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:seed-achievements', description: 'Seed achievement data')]
class SeedAchievementsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AchievementRepository $repo,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $achievements = [
            // Streak achievements
            ['first-step',   'First Step',        'Completed your first day clean',                   '🌱', 'streak', 1,   0,  100],
            ['one-week',     'One Week Wonder',    'Seven days of freedom — the hardest week is done', '⭐', 'streak', 7,   0,  250],
            ['two-weeks',    'Fortnight Fighter',  'Two full weeks — your brain is literally rewiring','💪', 'streak', 14,  0,  400],
            ['one-month',    'Monthly Milestone',  'One month! Your body is healing in measurable ways','🏆','streak', 30,  0,  750],
            ['three-months', 'Quarter Conqueror',  '90 days — a new normal is taking shape',           '🔥', 'streak', 90,  0, 1500],
            ['six-months',   'Half Year Hero',     'Six months of choosing yourself every single day', '💎', 'streak', 180, 0, 3000],
            ['one-year',     'Year of Victory',    'One full year — you are proof that change is real','👑', 'streak', 365, 0, 5000],
            // Check-in achievements
            ['first-checkin','Self Aware',         'Completed your first daily check-in',              '📊', 'checkin', 0, 1,   50],
            ['checkin-7',    'Habit Builder',      'Checked in 7 days in a row',                       '📈', 'checkin', 0, 7,  150],
            ['checkin-30',   'Consistent Champion','30 daily check-ins — consistency is your superpower','🎯','checkin', 0, 30, 500],
            // Journal achievements
            ['first-journal','Voice Found',        'Wrote your first journal entry',                   '📝', 'journal', 0, 1,   50],
            ['journal-10',   'Deep Reflector',     '10 journal entries — self-awareness transforms lives','🧠','journal',0,10, 200],
            // Craving achievements
            ['first-craving','Craving Crushed',    'Survived your first craving — this is huge!',      '⚡', 'craving', 0, 1,  100],
            ['craving-5',    'Wave Rider',         'Surfed 5 cravings without giving in',              '🌊', 'craving', 0, 5,  300],
            ['craving-20',   'Urge Surfer',        '20 cravings survived — you\'ve mastered the wave', '🏄', 'craving', 0, 20, 750],
            // Money achievements
            ['saved-10',     'First Savings',      'Saved your first 10 — addiction costs more than money',    '💰', 'money', 0, 0,   50],
            ['saved-100',    'Triple Digits',      'Saved 100 — what will you treat yourself to?',             '💵', 'money', 0, 0,  200],
            ['saved-500',    'Freedom Funds',      'Saved 500 — you earned every single unit of that',         '🤑', 'money', 0, 0,  500],
        ];

        $count = 0;
        $updated = 0;
        foreach ($achievements as [$slug, $name, $desc, $icon, $category, $days, $cnt, $xp]) {
            $existing = $this->repo->findBySlug($slug);
            if (!$existing) {
                $a = new Achievement();
                $a->setSlug($slug)->setName($name)->setDescription($desc)
                  ->setIcon($icon)->setCategory($category)
                  ->setRequirementDays($days)->setRequirementCount($cnt)
                  ->setXpReward($xp);
                $this->em->persist($a);
                $count++;
            } else {
                // Update description in case it changed
                $existing->setDescription($desc);
                $updated++;
            }
        }

        $this->em->flush();
        $io->success("Seeded $count new, updated $updated existing achievements.");
        return Command::SUCCESS;
    }
}
