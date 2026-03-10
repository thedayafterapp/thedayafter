<?php

namespace App\Controller;

use App\Entity\ForumPost;
use App\Entity\ForumReply;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/forum')]
#[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_USER')]
class ForumController extends AbstractController
{
    private const CATEGORIES = ['all', 'wins', 'support', 'general', 'feedback'];

    #[Route('', name: 'app_forum')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $category = $request->query->get('category', 'all');
        if (!in_array($category, self::CATEGORIES, true)) $category = 'all';

        $qb = $em->getRepository(ForumPost::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.replies', 'r')
            ->addSelect('COUNT(r.id) AS HIDDEN replyCount')
            ->groupBy('p.id')
            ->orderBy('p.createdAt', 'DESC');

        if ($category !== 'all') {
            $qb->where('p.category = :cat')->setParameter('cat', $category);
        }

        $posts = $qb->getQuery()->getResult();

        return $this->render('forum/index.html.twig', [
            'posts'    => $posts,
            'category' => $category,
            'user'     => $this->getUser(),
        ]);
    }

    #[Route('/setup', name: 'app_forum_setup')]
    public function setup(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getForumUsername()) {
            return $this->redirectToRoute('app_forum');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $username = trim($request->request->get('forum_username', ''));

            if (strlen($username) < 3 || strlen($username) > 30) {
                $error = 'Username must be between 3 and 30 characters.';
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                $error = 'Only letters, numbers, and underscores allowed.';
            } elseif ($em->getRepository(User::class)->findOneBy(['forumUsername' => $username])) {
                $error = 'That username is already taken.';
            } else {
                $user->setForumUsername($username);
                $em->flush();
                $this->addFlash('success', 'Welcome to the community, ' . $username . '!');
                return $this->redirectToRoute('app_forum');
            }
        }

        return $this->render('forum/setup.html.twig', ['error' => $error]);
    }

    #[Route('/new', name: 'app_forum_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->getForumUsername()) {
            return $this->redirectToRoute('app_forum_setup');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $title    = trim($request->request->get('title', ''));
            $body     = trim($request->request->get('body', ''));
            $category = $request->request->get('category', 'general');

            if (!in_array($category, ['wins', 'support', 'general', 'feedback'], true)) $category = 'general';

            if (strlen($title) < 3) {
                $error = 'Title must be at least 3 characters.';
            } elseif (strlen($body) < 10) {
                $error = 'Post must be at least 10 characters.';
            } else {
                $post = new ForumPost();
                $post->setUser($user)
                     ->setCategory($category)
                     ->setTitle($title)
                     ->setBody($body);

                $em->persist($post);
                $em->flush();

                return $this->redirectToRoute('app_forum_post', ['id' => $post->getId()]);
            }
        }

        return $this->render('forum/new.html.twig', [
            'error' => $error,
            'category' => $request->query->get('category', 'general'),
        ]);
    }

    #[Route('/{id}', name: 'app_forum_post', requirements: ['id' => '\d+'])]
    public function post(ForumPost $post, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $error = null;

        if ($request->isMethod('POST')) {
            if (!$user->getForumUsername()) {
                return $this->redirectToRoute('app_forum_setup');
            }

            $body = trim($request->request->get('body', ''));

            if (strlen($body) < 2) {
                $error = 'Reply cannot be empty.';
            } else {
                $reply = new ForumReply();
                $reply->setPost($post)
                      ->setUser($user)
                      ->setBody($body);

                $em->persist($reply);
                $em->flush();

                return $this->redirectToRoute('app_forum_post', ['id' => $post->getId(), '_fragment' => 'reply-' . $reply->getId()]);
            }
        }

        return $this->render('forum/post.html.twig', [
            'post'  => $post,
            'user'  => $user,
            'error' => $error,
        ]);
    }

    #[Route('/post/{id}/flag', name: 'app_forum_flag_post', methods: ['POST'])]
    public function flagPost(
        ForumPost $post,
        Request $request,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrf,
    ): Response {
        if (!$csrf->isTokenValid(new CsrfToken('flag_post_' . $post->getId(), $request->request->get('_token')))) {
            throw $this->createAccessDeniedException();
        }

        $post->setIsFlagged(true);
        $em->flush();

        $this->addFlash('info', 'Post flagged for review. Thank you.');
        return $this->redirectToRoute('app_forum_post', ['id' => $post->getId()]);
    }

    #[Route('/reply/{id}/flag', name: 'app_forum_flag_reply', methods: ['POST'])]
    public function flagReply(
        ForumReply $reply,
        Request $request,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrf,
    ): Response {
        if (!$csrf->isTokenValid(new CsrfToken('flag_reply_' . $reply->getId(), $request->request->get('_token')))) {
            throw $this->createAccessDeniedException();
        }

        $reply->setIsFlagged(true);
        $em->flush();

        $this->addFlash('info', 'Reply flagged for review. Thank you.');
        return $this->redirectToRoute('app_forum_post', ['id' => $reply->getPost()->getId()]);
    }

    #[Route('/post/{id}/delete', name: 'app_forum_delete_post', methods: ['POST'])]
    public function deletePost(
        ForumPost $post,
        Request $request,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrf,
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire('%env(ADMIN_EMAIL)%')] string $adminEmail,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($post->getUser() !== $user && $user->getEmail() !== $adminEmail) {
            throw $this->createAccessDeniedException();
        }

        if (!$csrf->isTokenValid(new CsrfToken('delete_post_' . $post->getId(), $request->request->get('_token')))) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($post);
        $em->flush();

        $this->addFlash('success', 'Post deleted.');
        return $this->redirectToRoute('app_forum');
    }

    #[Route('/reply/{id}/delete', name: 'app_forum_delete_reply', methods: ['POST'])]
    public function deleteReply(
        ForumReply $reply,
        Request $request,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrf,
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire('%env(ADMIN_EMAIL)%')] string $adminEmail,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($reply->getUser() !== $user && $user->getEmail() !== $adminEmail) {
            throw $this->createAccessDeniedException();
        }

        if (!$csrf->isTokenValid(new CsrfToken('delete_reply_' . $reply->getId(), $request->request->get('_token')))) {
            throw $this->createAccessDeniedException();
        }

        $postId = $reply->getPost()->getId();
        $em->remove($reply);
        $em->flush();

        $this->addFlash('success', 'Reply deleted.');
        return $this->redirectToRoute('app_forum_post', ['id' => $postId]);
    }
}
