<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AchievementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
}
