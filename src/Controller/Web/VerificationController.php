<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Application\EmailVerificationService;
use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VerificationController extends AbstractController
{
    #[Route('/verify', name: 'app_verify_email', methods: ['GET'])]
    public function showForm(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $email = $request->query->get('email');

        return $this->render('security/verify_email.html.twig', [
            'email' => $email,
        ]);
    }

    #[Route('/verify', name: 'app_verify_email_submit', methods: ['POST'])]
    public function verify(
        Request $request,
        UserRepositoryInterface $userRepository,
        EmailVerificationService $verificationService,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $email = (string) $request->request->get('email');
        $code = (string) $request->request->get('code');

        if ('' === $email || '' === $code) {
            $this->addFlash('error', 'Email and verification code are required.');

            return $this->redirectToRoute('app_verify_email', ['email' => $email]);
        }

        $user = $userRepository->findByEmail($email);

        if (null === $user) {
            $this->addFlash('error', 'No account found with this email.');

            return $this->redirectToRoute('app_verify_email');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Email is already verified. Please log in.');

            return $this->redirectToRoute('app_login');
        }

        if ($verificationService->verify($user, $code)) {
            $this->addFlash('success', 'Email verified successfully. You can now log in.');

            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('error', 'Invalid or expired verification code.');

        return $this->redirectToRoute('app_verify_email', ['email' => $email]);
    }

    #[Route('/verify/resend', name: 'app_verify_resend', methods: ['POST'])]
    public function resend(
        Request $request,
        UserRepositoryInterface $userRepository,
        EmailVerificationService $verificationService,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $email = (string) $request->request->get('email');
        $user = $userRepository->findByEmail($email);

        if (null === $user || $user->isVerified()) {
            $this->addFlash('success', 'If the account exists, a new verification code has been sent.');

            return $this->redirectToRoute('app_login');
        }

        $verificationService->sendVerificationCode($user);
        $this->addFlash('success', 'A new verification code has been sent to your email.');

        return $this->redirectToRoute('app_verify_email', ['email' => $email]);
    }
}
