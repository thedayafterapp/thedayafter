<?php

namespace App\Controller;

use App\Entity\MoodLog;
use App\Entity\User;
use App\Repository\MoodLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class MoodLogController extends AbstractController
{
    #[Route('/mood', name: 'app_mood')]
    public function index(): Response
    {
        return $this->render('mood/index.html.twig');
    }

    #[Route('/mood/history', name: 'app_mood_history')]
    public function history(MoodLogRepository $repo): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('mood/history.html.twig', [
            'user' => $user,
            'logs' => $repo->findRecentByUser($user, 50),
        ]);
    }

    #[Route('/mood/save', name: 'app_mood_save', methods: ['POST'])]
    public function save(
        Request $request,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrf,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$csrf->isTokenValid(new CsrfToken('mood_log', $request->request->get('_token')))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $log = new MoodLog();
        $log->setUser($user);
        $log->setMood((int) $request->request->get('mood', 5));
        $log->setNote($request->request->get('note') ?: null);

        $em->persist($log);
        $em->flush();

        $this->addFlash('success', 'Mood logged.');
        return $this->redirectToRoute('app_mood_history');
    }

    #[Route('/mood/{id}/update-note', name: 'app_mood_update_note', methods: ['POST'])]
    public function updateNote(
        MoodLog $log,
        Request $request,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrf,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($log->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$csrf->isTokenValid(new CsrfToken('mood_note_' . $log->getId(), $request->request->get('_token')))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $log->setNote($request->request->get('note') ?: null);
        $em->flush();

        return $this->redirectToRoute('app_mood_history');
    }

    #[Route('/mood/{id}/delete', name: 'app_mood_delete', methods: ['POST'])]
    public function delete(
        MoodLog $log,
        Request $request,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrf,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($log->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$csrf->isTokenValid(new CsrfToken('mood_delete_' . $log->getId(), $request->request->get('_token')))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $em->remove($log);
        $em->flush();

        return $this->redirectToRoute('app_mood_history');
    }
}
