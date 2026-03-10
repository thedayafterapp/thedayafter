<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\AchievementService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authUtils): Response
    {
        if ($this->getUser()) return $this->redirectToRoute('app_dashboard');
        return $this->render('security/login.html.twig', [
            'error'      => $authUtils->getLastAuthenticationError(),
            'last_email' => $authUtils->getLastUsername(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('Intercepted by firewall.');
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        Security $security,
        AchievementService $achievementService,
    ): Response {
        if ($this->getUser()) return $this->redirectToRoute('app_dashboard');

        $error = null;

        if ($request->isMethod('POST')) {
            $name          = trim($request->request->get('name', ''));
            $email         = trim($request->request->get('email', ''));
            $password      = $request->request->get('password', '');
            $addictionType = $request->request->get('addiction_type', 'both');
            $currency      = $request->request->get('currency', 'USD');
            $motivation    = trim($request->request->get('motivation', ''));
            $timezone      = $request->request->get('timezone', 'UTC');
            if (!in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
                $timezone = 'UTC';
            }

            if (strlen($name) < 2) {
                $error = 'Please enter your name.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } elseif (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters.';
            } elseif ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
                $error = 'An account with this email already exists.';
            } else {
                $user = new User();
                $user->setName($name)
                    ->setEmail($email)
                    ->setPassword($hasher->hashPassword($user, $password))
                    ->setAddictionType($addictionType)
                    ->setCurrency($currency)
                    ->setMotivation($motivation ?: null)
                    ->setTimezone($timezone);

                $parseDate = function (string $val) use ($timezone): \DateTime {
                    if (!$val) {
                        $dt = new \DateTime('now', new \DateTimeZone($timezone));
                    } else {
                        $dt = new \DateTime($val, new \DateTimeZone($timezone));
                    }
                    $dt->setTimezone(new \DateTimeZone('UTC'));
                    return $dt;
                };

                if ($addictionType === 'alcohol' || $addictionType === 'both') {
                    $date = $parseDate($request->request->get('alcohol_quit_date', ''));
                    $user->setAlcoholQuitDate($date);
                    $user->setQuitDate($date);
                    $cost = $request->request->get('alcohol_daily_cost', '');
                    if (is_numeric($cost)) $user->setAlcoholDailyCost((string)(float)$cost);
                }

                if ($addictionType === 'cigarettes' || $addictionType === 'both') {
                    $date = $parseDate($request->request->get('cigarettes_quit_date', ''));
                    $user->setCigarettesQuitDate($date);
                    if (!$user->getQuitDate()) $user->setQuitDate($date);
                    $cost = $request->request->get('cigarettes_daily_cost', '');
                    if (is_numeric($cost)) $user->setCigarettesDailyCost((string)(float)$cost);
                }

                $em->persist($user);
                $em->flush();
                $achievementService->checkAndAward($user);

                $security->login($user, 'form_login', 'main');
                $this->addFlash('success', 'Welcome to TheDayAfter, ' . $name . '! Your journey starts now. 🌱');
                return $this->redirectToRoute('app_dashboard');
            }
        }

        return $this->render('security/register.html.twig', ['error' => $error]);
    }
}
