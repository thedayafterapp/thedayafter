<?php

namespace App\Command;

use App\Repository\MoodLogRepository;
use App\Repository\PushSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:send-mood-reminders', description: 'Send mood reminder push notifications')]
class SendMoodRemindersCommand extends Command
{
    public function __construct(
        private PushSubscriptionRepository $repo,
        private MoodLogRepository $moodRepo,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $auth = [
            'VAPID' => [
                'subject'    => 'mailto:thedayafterapp@gmail.com',
                'publicKey'  => $_ENV['VAPID_PUBLIC_KEY'],
                'privateKey' => $_ENV['VAPID_PRIVATE_KEY'],
            ],
        ];

        $webPush = new WebPush($auth);
        $payload = json_encode([
            'title' => 'How are you feeling? 💭',
            'body'  => 'Take 10 seconds to log your mood.',
            'url'   => '/mood/history',
        ]);

        $now = new \DateTime();
        $oneHourAgo = (new \DateTime())->modify('-1 hour');

        $sent = 0;
        $subscriptions = $this->repo->findAll();

        foreach ($subscriptions as $sub) {
            // Skip if notified less than 1 hour ago
            if ($sub->getLastNotifiedAt() && $sub->getLastNotifiedAt() > $oneHourAgo) {
                continue;
            }

            // Skip if user already logged a mood in the last 3 hours
            $recentLog = $this->moodRepo->findOneBy(
                ['user' => $sub->getUser()],
                ['createdAt' => 'DESC']
            );
            if ($recentLog && $recentLog->getCreatedAt() > $oneHourAgo) {
                continue;
            }

            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $sub->getEndpoint(),
                    'keys'     => ['p256dh' => $sub->getP256dh(), 'auth' => $sub->getAuth()],
                ]),
                $payload
            );

            $sub->setLastNotifiedAt($now);
            $sent++;
        }

        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                $output->writeln('Failed: ' . $report->getReason());
            }
        }

        $this->em->flush();

        $output->writeln("Sent to {$sent} / " . count($subscriptions) . ' subscribers.');
        return Command::SUCCESS;
    }
}
