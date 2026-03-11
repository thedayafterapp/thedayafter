<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserAchievement;
use App\Repository\AchievementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AchievementsController extends AbstractController
{
    #[Route('/achievements', name: 'app_achievements')]
    public function index(AchievementRepository $repo): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $all = $repo->findAll();

        $earned = [];
        foreach ($user->getUserAchievements() as $ua) {
            $earned[$ua->getAchievement()->getSlug()] = $ua->getEarnedAt();
        }

        return $this->render('achievements/index.html.twig', [
            'user' => $user,
            'achievements' => $all,
            'earned' => $earned,
        ]);
    }

    #[Route('/achievements/reset', name: 'app_achievements_reset', methods: ['POST'])]
    public function reset(
        Request $request,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrf,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$csrf->isTokenValid(new CsrfToken('reset_achievements', $request->request->get('_token')))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        foreach ($user->getUserAchievements() as $ua) {
            $em->remove($ua);
        }
        $em->flush();

        $this->addFlash('success', 'All achievements reset.');
        return $this->redirectToRoute('app_achievements');
    }
}
