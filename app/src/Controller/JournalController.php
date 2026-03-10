<?php

namespace App\Controller;

use App\Entity\JournalEntry;
use App\Entity\User;
use App\Repository\JournalEntryRepository;
use App\Service\AchievementService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/journal')]
class JournalController extends AbstractController
{
    #[Route('', name: 'app_journal')]
    public function index(JournalEntryRepository $repo): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $entries = $repo->findRecentByUser($user, 20);

        return $this->render('journal/index.html.twig', [
            'user' => $user,
            'entries' => $entries,
        ]);
    }

    #[Route('/new', name: 'app_journal_new', methods: ['POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        AchievementService $achievementService,
        CsrfTokenManagerInterface $csrf,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$csrf->isTokenValid(new CsrfToken('new_journal', $request->request->get('_token')))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $content = trim($request->request->get('content', ''));
        if (!$content) {
            $this->addFlash('error', 'Journal entry cannot be empty.');
            return $this->redirectToRoute('app_journal');
        }

        $entry = new JournalEntry();
        $entry->setUser($user)
            ->setTitle($request->request->get('title'))
            ->setContent($content)
            ->setMood($request->request->get('mood'));

        $user->addXp(30);
        $em->persist($entry);
        $em->flush();

        $newAchievements = $achievementService->checkAndAward($user);
        foreach ($newAchievements as $a) {
            $this->addFlash('achievement', $a->getIcon() . ' ' . $a->getName() . ' unlocked! +' . $a->getXpReward() . ' XP');
        }

        $this->addFlash('success', 'Journal entry saved! +30 XP 📝');
        return $this->redirectToRoute('app_journal');
    }
}
