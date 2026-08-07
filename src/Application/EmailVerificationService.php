<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Repository\UserRepositoryInterface;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class EmailVerificationService
{
    private const CODE_LENGTH = 6;
    private const CODE_TTL_SECONDS = 3600;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly MailerInterface $mailer,
    ) {
    }

    public function sendVerificationCode(User $user): void
    {
        $code = $this->generateCode();
        $user->setVerificationCode($code);
        $user->setVerificationCodeExpiresAt(new \DateTimeImmutable('+'.self::CODE_TTL_SECONDS.' seconds'));
        $this->userRepository->save($user);

        $email = (new Email())
            ->from('noreply@taskmanager.local')
            ->to($user->getEmail())
            ->subject('Verify your email address')
            ->text(\sprintf("Your verification code is: %s\n\nThis code expires in 1 hour.", $code))
            ->html(\sprintf(
                '<p>Your verification code is: <strong>%s</strong></p><p>This code expires in 1 hour.</p>',
                $code
            ));

        $this->mailer->send($email);
    }

    public function verify(User $user, string $code): bool
    {
        if (null === $user->getVerificationCode()) {
            return false;
        }

        if ($user->getVerificationCodeExpiresAt() < new \DateTimeImmutable()) {
            return false;
        }

        if ($user->getVerificationCode() !== $code) {
            return false;
        }

        $user->setIsVerified(true);
        $user->setVerificationCode(null);
        $user->setVerificationCodeExpiresAt(null);
        $this->userRepository->save($user);

        return true;
    }

    private function generateCode(): string
    {
        $code = '';
        for ($i = 0; $i < self::CODE_LENGTH; ++$i) {
            $code .= (string) random_int(0, 9);
        }

        return $code;
    }
}
