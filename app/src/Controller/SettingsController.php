<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/settings')]
class SettingsController extends AbstractController
{
    #[Route('', name: 'app_settings')]
    public function index(): Response
    {
        return $this->render('settings/index.html.twig', ['user' => $this->getUser()]);
    }

    #[Route('/save', name: 'app_settings_save', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user          = $this->getUser();
        $addictionType = $request->request->get('addiction_type', $user->getAddictionType());
        $currency      = $request->request->get('currency', 'USD');
        $motivation    = trim($request->request->get('motivation', ''));

        $timezone = $request->request->get('timezone', $user->getTimezone());
        if (!in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            $timezone = 'UTC';
        }

        $user->setAddictionType($addictionType);
        $user->setCurrency($currency ?: 'USD');
        $user->setMotivation($motivation ?: null);
        $user->setTimezone($timezone);

        $parseDate = function (string $val) use ($timezone): ?\DateTime {
            if (!$val) return null;
            $dt = new \DateTime($val, new \DateTimeZone($timezone));
            $dt->setTimezone(new \DateTimeZone('UTC'));
            return $dt;
        };
        $parseCost = function (string $val): ?string {
            return $val !== '' && is_numeric($val) ? (string)(float)$val : null;
        };

        if ($addictionType === 'alcohol' || $addictionType === 'both') {
            $d = $parseDate($request->request->get('alcohol_quit_date', ''));
            if ($d) { $user->setAlcoholQuitDate($d); $user->setQuitDate($d); }
            $user->setAlcoholDailyCost($parseCost($request->request->get('alcohol_daily_cost', '')));
        }

        if ($addictionType === 'cigarettes' || $addictionType === 'both') {
            $d = $parseDate($request->request->get('cigarettes_quit_date', ''));
            if ($d) { $user->setCigarettesQuitDate($d); if (!$user->getQuitDate()) $user->setQuitDate($d); }
            $user->setCigarettesDailyCost($parseCost($request->request->get('cigarettes_daily_cost', '')));
        }

        $em->flush();
        $this->addFlash('success', 'Settings saved!');
        return $this->redirectToRoute('app_dashboard');
    }
}
