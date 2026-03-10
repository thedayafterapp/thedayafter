<?php

namespace App\Controller;

use App\Entity\RelapseLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/relapse')]
class RelapseController extends AbstractController
{
    #[Route('', name: 'app_relapse')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        return $this->render('relapse/index.html.twig', ['user' => $user]);
    }

    #[Route('/history', name: 'app_relapse_history')]
    public function history(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $logs = $em->getRepository(RelapseLog::class)
            ->findBy(['user' => $user], ['relapsedAt' => 'DESC']);

        return $this->render('relapse/history.html.twig', [
            'user' => $user,
            'logs' => $logs,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_relapse_delete', methods: ['POST'])]
    public function delete(
        RelapseLog $log,
        Request $request,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrf,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($log->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$csrf->isTokenValid(new CsrfToken('delete_relapse_' . $log->getId(), $request->request->get('_token')))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $em->remove($log);
        $em->flush();

        $this->addFlash('success', 'Entry removed.');
        return $this->redirectToRoute('app_relapse_history');
    }

    #[Route('/confirm', name: 'app_relapse_confirm', methods: ['POST'])]
    public function confirm(Request $request, EntityManagerInterface $em, CsrfTokenManagerInterface $csrf): Response
    {
        /** @var User $user */
        $user          = $this->getUser();

        if (!$csrf->isTokenValid(new CsrfToken('confirm_relapse', $request->request->get('_token')))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $type          = $request->request->get('addiction_type', $user->getAddictionType());
        $notes         = $request->request->get('notes');
        $newQuitDate   = new \DateTime('today');

        // Log the relapse before resetting
        $moneySaved = $user->getMoneySaved($type === 'both' ? 'total' : $type);
        $log = new RelapseLog();
        $log->setUser($user)
            ->setAddictionType($type)
            ->setNotes($notes)
            ->setPreviousQuitDate($user->getQuitDateFor($type))
            ->setMoneySavedAtRelapse($moneySaved > 0 ? $moneySaved : null);

        // Reset the appropriate quit date
        if ($type === 'alcohol' || $type === 'both') {
            $user->setAlcoholQuitDate($newQuitDate);
        }
        if ($type === 'cigarettes' || $type === 'both') {
            $user->setCigarettesQuitDate($newQuitDate);
        }
        if ($user->getAddictionType() !== 'both') {
            $user->setQuitDate($newQuitDate);
        }

        $em->persist($log);
        $em->flush();

        $this->addFlash('success', 'Your streak has been reset — but your XP, achievements, and everything you\'ve learned stays. Today is Day 1 again. You\'ve done it before. You\'ll do it again. 💙');
        return $this->redirectToRoute('app_dashboard');
    }
}
