<?php

namespace App\Controller;

use App\Entity\CheckIn;
use App\Entity\User;
use App\Repository\CheckInRepository;
use App\Service\AchievementService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/checkin')]
class CheckInController extends AbstractController
{
    #[Route('', name: 'app_checkin')]
    public function index(CheckInRepository $repo): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $hadToday = $repo->hadCheckInToday($user);
        $recent = $repo->findRecentByUser($user, 14);

        return $this->render('checkin/index.html.twig', [
            'user' => $user,
            'had_today' => $hadToday,
            'recent' => $recent,
        ]);
    }

    #[Route('/submit', name: 'app_checkin_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        EntityManagerInterface $em,
        CheckInRepository $repo,
        AchievementService $achievementService,
        CsrfTokenManagerInterface $csrf,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$csrf->isTokenValid(new CsrfToken('submit_checkin', $request->request->get('_token')))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if ($repo->hadCheckInToday($user)) {
            $this->addFlash('info', 'You already checked in today!');
            return $this->redirectToRoute('app_dashboard');
        }

        $checkin = new CheckIn();
        $checkin->setUser($user)
            ->setMood((int)$request->request->get('mood', 5))
            ->setCravingIntensity((int)$request->request->get('craving_intensity', 0))
            ->setTriggers($request->request->all('triggers') ?? [])
            ->setNotes($request->request->get('notes'));

        $user->addXp(50);
        $em->persist($checkin);
        $em->flush();

        $newAchievements = $achievementService->checkAndAward($user);
        foreach ($newAchievements as $a) {
            $this->addFlash('achievement', $a->getIcon() . ' ' . $a->getName() . ' unlocked! +' . $a->getXpReward() . ' XP');
        }

        $this->addFlash('success', 'Daily check-in complete! +50 XP 💫');
        return $this->redirectToRoute('app_checkin');
    }

    #[Route('/edit/{id}', name: 'app_checkin_edit', methods: ['POST'])]
    public function edit(
        CheckIn $checkIn,
        Request $request,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrf,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($checkIn->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$csrf->isTokenValid(new CsrfToken('edit_checkin_' . $checkIn->getId(), $request->request->get('_token')))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $checkIn->setMood((int) $request->request->get('mood', 5))
                ->setCravingIntensity((int) $request->request->get('craving_intensity', 0))
                ->setTriggers($request->request->all('triggers') ?? [])
                ->setNotes($request->request->get('notes') ?: null);

        $em->flush();

        $this->addFlash('success', 'Check-in updated.');
        return $this->redirectToRoute('app_checkin');
    }

    #[Route('/delete/{id}', name: 'app_checkin_delete', methods: ['POST'])]
    public function delete(
        CheckIn $checkIn,
        Request $request,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrf,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($checkIn->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$csrf->isTokenValid(new CsrfToken('delete_checkin_' . $checkIn->getId(), $request->request->get('_token')))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $em->remove($checkIn);
        $em->flush();

        $this->addFlash('success', 'Check-in deleted.');
        return $this->redirectToRoute('app_checkin');
    }
}
