<?php

namespace App\Controller;

use App\Entity\PushSubscription;
use App\Entity\User;
use App\Repository\PushSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/push')]
class PushController extends AbstractController
{
    #[Route('/vapid-public-key', name: 'app_push_vapid_key', methods: ['GET'])]
    public function vapidKey(): JsonResponse
    {
        return new JsonResponse(['key' => $_ENV['VAPID_PUBLIC_KEY']]);
    }

    #[Route('/subscribe', name: 'app_push_subscribe', methods: ['POST'])]
    public function subscribe(
        Request $request,
        EntityManagerInterface $em,
        PushSubscriptionRepository $repo,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $endpoint = $data['endpoint'] ?? '';
        $p256dh   = $data['keys']['p256dh'] ?? '';
        $auth     = $data['keys']['auth'] ?? '';

        if (!$endpoint || !$p256dh || !$auth) {
            return new JsonResponse(['error' => 'Invalid subscription'], 400);
        }

        $existing = $repo->findOneBy(['endpoint' => $endpoint]);
        if (!$existing) {
            $sub = new PushSubscription();
            $sub->setUser($user)->setEndpoint($endpoint)->setP256dh($p256dh)->setAuth($auth);
            $em->persist($sub);
            $em->flush();
        }

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/unsubscribe', name: 'app_push_unsubscribe', methods: ['POST'])]
    public function unsubscribe(
        Request $request,
        EntityManagerInterface $em,
        PushSubscriptionRepository $repo,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $endpoint = $data['endpoint'] ?? '';

        $sub = $repo->findOneBy(['endpoint' => $endpoint, 'user' => $user]);
        if ($sub) {
            $em->remove($sub);
            $em->flush();
        }

        return new JsonResponse(['ok' => true]);
    }
}
