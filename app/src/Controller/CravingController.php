<?php

namespace App\Controller;

use App\Entity\ChatMessage;
use App\Entity\CravingSession;
use App\Entity\User;
use App\Service\AchievementService;
use App\Service\ClaudeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/craving')]
class CravingController extends AbstractController
{
    #[Route('', name: 'app_craving')]
    public function index(): Response
    {
        return $this->render('craving/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/alex/new', name: 'app_craving_alex_new')]
    public function alexNew(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $session = new CravingSession();
        $session->setUser($user);
        $session->setAddictionType($user->getAddictionType());
        $em->persist($session);

        $greeting = $user->getMotivation()
            ? "Hey, I'm Alex — and I'm really glad you reached out. 💙\n\nYou said you're doing this for: \"{$user->getMotivation()}\"\n\nHold onto that. Now tell me — what's happening right now? Where do you feel the craving in your body?"
            : "Hey, I'm Alex. You came here instead of giving in — that took real courage. 💙\n\nTell me: what's happening right now? What does this craving feel like?";

        $msg = new ChatMessage();
        $msg->setSession($session)->setRole('assistant')->setContent($greeting);
        $em->persist($msg);
        $em->flush();

        return $this->redirectToRoute('app_craving_alex', ['id' => $session->getId()]);
    }

    #[Route('/alex/{id}', name: 'app_craving_alex')]
    public function alex(CravingSession $session): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($session->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $messages = [];
        foreach ($session->getMessages() as $msg) {
            $messages[] = ['role' => $msg->getRole(), 'content' => $msg->getContent()];
        }

        return $this->render('craving/chat.html.twig', [
            'user' => $user,
            'session' => $session,
            'messages' => $messages,
        ]);
    }

    #[Route('/alex/{id}/send', name: 'app_craving_alex_send', methods: ['POST'])]
    public function alexSend(CravingSession $session, Request $request, EntityManagerInterface $em, ClaudeService $claude, #[\Symfony\Component\DependencyInjection\Attribute\Autowire('%env(ADMIN_EMAIL)%')] string $adminEmail): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($session->getUser() !== $user) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');

        if (!$message) {
            return $this->json(['error' => 'Empty message'], 400);
        }

        if (strlen($message) > 500) {
            return $this->json(['error' => 'Message too long. Please keep it under 500 characters.'], 400);
        }

        if ($user->getEmail() !== $adminEmail && !$user->checkAndIncrementDailyMessages(20)) {
            return $this->json(['error' => '💙 I\'m very sorry — you\'ve reached today\'s message limit, which helps us keep Alex available for everyone (we are working on a solution). Try our Community Forum to connect with others. If you\'re in crisis, please reach out to a local crisis line or emergency services. Alex will be back tomorrow.'], 429);
        }

        $em->flush();

        $userMsg = new ChatMessage();
        $userMsg->setSession($session)->setRole('user')->setContent($message);
        $em->persist($userMsg);
        $em->flush();

        $history = [];
        foreach ($session->getMessages() as $msg) {
            $history[] = ['role' => $msg->getRole(), 'content' => $msg->getContent()];
        }

        try {
            $reply = $claude->chat($history, $session->getAddictionType() ?? $user->getAddictionType());
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 503);
        }

        $assistantMsg = new ChatMessage();
        $assistantMsg->setSession($session)->setRole('assistant')->setContent($reply);
        $em->persist($assistantMsg);
        $em->flush();

        return $this->json(['reply' => $reply]);
    }

    #[Route('/alex/{id}/survived', name: 'app_craving_alex_survived', methods: ['POST'])]
    public function alexSurvived(CravingSession $session, EntityManagerInterface $em, AchievementService $achievementService): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($session->getUser() !== $user) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $survivedMessage = "🎉 YOU DID IT. You rode that wave and it passed.\n\nThat craving is now a data point: you know you can survive it. Every single one you get through makes the next one weaker. Your brain is literally different now than it was 20 minutes ago.\n\nI am so proud of you. 🌟";

        if ($session->getOutcome() !== 'survived') {
            $session->setOutcome('survived');
            $session->setEndedAt(new \DateTime());
            $user->incrementCravingsSurvived();
            $user->addXp(100);

            $msg = new ChatMessage();
            $msg->setSession($session)->setRole('assistant')->setContent($survivedMessage);
            $em->persist($msg);
            $em->flush();
        }

        $newAchievements = $achievementService->checkAndAward($user);

        return $this->json([
            'survived_message' => $survivedMessage,
            'xp_gained' => 100,
            'total_xp' => $user->getTotalXp(),
            'cravings_survived' => $user->getCravingsSurvived(),
            'new_achievements' => array_map(fn($a) => [
                'name' => $a->getName(),
                'icon' => $a->getIcon(),
                'xp' => $a->getXpReward(),
            ], $newAchievements),
        ]);
    }

    #[Route('/history', name: 'app_craving_history')]
    public function history(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $sessions = $em->getRepository(CravingSession::class)->findBy(
            ['user' => $user],
            ['startedAt' => 'DESC']
        );

        return $this->render('craving/history.html.twig', [
            'sessions' => $sessions,
        ]);
    }

    #[Route('/sessions/delete-all', name: 'app_craving_delete_all', methods: ['POST'])]
    public function deleteAll(EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $sessions = $em->getRepository(CravingSession::class)->findBy(['user' => $user]);
        foreach ($sessions as $session) {
            $em->remove($session);
        }
        $em->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/session/{id}/delete', name: 'app_craving_delete', methods: ['POST'])]
    public function deleteSession(CravingSession $session, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($session->getUser() !== $user) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $em->remove($session);
        $em->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/survived', name: 'app_craving_survived', methods: ['POST'])]
    public function survived(
        Request $request,
        EntityManagerInterface $em,
        AchievementService $achievementService,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $sessionId = $data['session_id'] ?? null;

        $session = $sessionId
            ? $em->getRepository(CravingSession::class)->find($sessionId)
            : null;

        if ($session && $session->getUser() === $user) {
            $session->setOutcome('survived');
            $session->setEndedAt(new \DateTime());
        }

        $user->incrementCravingsSurvived();
        $user->addXp(100);
        $em->flush();

        $newAchievements = $achievementService->checkAndAward($user);

        return $this->json([
            'xp_gained' => 100,
            'total_xp' => $user->getTotalXp(),
            'cravings_survived' => $user->getCravingsSurvived(),
            'new_achievements' => array_map(fn($a) => [
                'name' => $a->getName(),
                'icon' => $a->getIcon(),
                'xp' => $a->getXpReward(),
            ], $newAchievements),
        ]);
    }
}
