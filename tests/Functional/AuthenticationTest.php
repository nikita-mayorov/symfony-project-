<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Repository\UserRepositoryInterface;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthenticationTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    private function createVerifiedUser(string $email, string $password): User
    {
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $hasher = static::getContainer()->get('security.user_password_hasher');

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, $password));
        $user->setIsVerified(true);
        $userRepository->save($user);

        return $user;
    }

    private function login(string $email, string $password): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Login')->form();

        $this->client->submit($form, [
            '_username' => $email,
            '_password' => $password,
        ]);
    }

    public function testRegistrationWithInvalidEmailRejected(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $form = $crawler->selectButton('Register')->form();

        $this->client->submit($form, [
            'registration[email]' => '1@1',
            'registration[plainPassword][first]' => 'password123',
            'registration[plainPassword][second]' => 'password123',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.invalid-feedback', 'not a valid email');
    }

    public function testRegistrationWithShortPasswordRejected(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $form = $crawler->selectButton('Register')->form();

        $this->client->submit($form, [
            'registration[email]' => 'valid@example.com',
            'registration[plainPassword][first]' => '12',
            'registration[plainPassword][second]' => '12',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.invalid-feedback', 'at least 8 characters');
    }

    public function testSuccessfulRegistrationCreatesUnverifiedUser(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $form = $crawler->selectButton('Register')->form();

        $this->client->submit($form, [
            'registration[email]' => 'NewUser@Test.Com',
            'registration[plainPassword][first]' => 'password12345',
            'registration[plainPassword][second]' => 'password12345',
        ]);

        $this->assertResponseRedirects('/verify?email=newuser@test.com');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $user = $userRepository->findByEmail('newuser@test.com');
        $this->assertNotNull($user, 'User should be created with lowercase email');
        $this->assertFalse($user->isVerified(), 'New user should not be verified');
        $this->assertNotNull($user->getVerificationCode(), 'Verification code should be generated');
    }

    public function testDuplicateEmailCaseInsensitiveRejected(): void
    {
        $this->createVerifiedUser('existing@test.com', 'password12345');

        $crawler = $this->client->request('GET', '/register');
        $form = $crawler->selectButton('Register')->form();

        $this->client->submit($form, [
            'registration[email]' => 'EXISTING@TEST.COM',
            'registration[plainPassword][first]' => 'password12345',
            'registration[plainPassword][second]' => 'password12345',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.invalid-feedback', 'already registered');
    }

    public function testUnverifiedUserCannotLogin(): void
    {
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $hasher = static::getContainer()->get('security.user_password_hasher');

        $user = new User();
        $user->setEmail('unverified@test.com');
        $user->setPassword($hasher->hashPassword($user, 'password12345'));
        $userRepository->save($user);

        $this->login('unverified@test.com', 'password12345');

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert', 'verify your email');
    }

    public function testEmailVerification(): void
    {
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $hasher = static::getContainer()->get('security.user_password_hasher');

        $user = new User();
        $user->setEmail('verify@test.com');
        $user->setPassword($hasher->hashPassword($user, 'password12345'));
        $user->setVerificationCode('123456');
        $user->setVerificationCodeExpiresAt(new \DateTimeImmutable('+1 hour'));
        $userRepository->save($user);

        $crawler = $this->client->request('GET', '/verify?email=verify@test.com');
        $this->assertResponseIsSuccessful();

        $csrfToken = $crawler->filter('input[name="_csrf_token"]')->attr('value');

        $this->client->request('POST', '/verify', [
            'email' => 'verify@test.com',
            'code' => '123456',
            '_csrf_token' => $csrfToken,
        ]);

        $this->assertResponseRedirects('/login');

        $user = $userRepository->findByEmail('verify@test.com');
        $this->assertNotNull($user);
        $this->assertTrue($user->isVerified(), 'User should be verified');
        $this->assertNull($user->getVerificationCode(), 'Code should be cleared');
    }

    public function testVerifiedUserCanLogin(): void
    {
        $this->createVerifiedUser('verified@test.com', 'password12345');

        $this->login('verified@test.com', 'password12345');

        $this->assertResponseRedirects('/');
    }

    public function testExistingUserCanLoginWithCaseInsensitiveEmail(): void
    {
        $this->createVerifiedUser('CaseSensitive@test.com', 'password12345');

        $this->login('casesensitive@test.com', 'password12345');

        $this->assertResponseRedirects('/');
    }
}
