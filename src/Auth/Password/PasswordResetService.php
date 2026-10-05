<?php

namespace App\Auth\Password;

use App\Core\Entity\EntityManager;
use App\Core\Mail\Mailer;
use App\User\Token\TokenService;
use App\User\Repository\UserRepositoryInterface;
use PHPMailer\PHPMailer\Exception as MailException;

class PasswordResetService
{
    private UserRepositoryInterface $userRepository;

    private string $baseUrl;

    public const SUBJECT = 'password-reset';
    public const TTL = 3600;


    public function __construct(EntityManager $entityManager, private TokenService $tokens, private Mailer $mailer, string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
        $userRepository = $entityManager->getRepository('user');
        if ($userRepository instanceof UserRepositoryInterface) {
            $this->userRepository = $userRepository;
        } else {
            throw new \RuntimeException('User repository must implement UserRepositoryInterface.');
        }
    }

    public function requestReset(string $email): void
    {
        if (empty($this->baseUrl)) {
            throw new \InvalidArgumentException('Base URL is required for password reset.');
        }

        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            return;
        }

        $token = $this->tokens->generateToken($user->id, self::SUBJECT, self::TTL);
        $url = rtrim($this->baseUrl, '/') . '/auth/password/reset?token=' . rawurlencode($token);
        try {
            $this->sendResetEmail($user->email, $url);
        } catch (PasswordResetMailException $exception) {
            $this->tokens->revokeToken($token);
            throw $exception;
        }
    }

    public function isValidToken(string $token): bool
    {
        $record = $this->tokens->findValidToken($token, self::SUBJECT);
        return $record !== null && $this->userRepository->findById($record->user_id) !== null;
    }

    public function resetPassword(string $token, string $password): bool
    {
        if (strlen($password) < 8 || strlen($password) > 72) {
            throw new \InvalidArgumentException('Password must contain between 8 and 72 bytes.');
        }

        $this->userRepository->beginTransaction();
        try {
            $userId = $this->tokens->consumeToken($token, self::SUBJECT);
            if ($userId === null || $this->userRepository->findById($userId) === null) {
                $this->userRepository->rollback();
                return false;
            }
            $this->userRepository->update($userId, ['password_hash' => $password]);
            $this->tokens->revokeUserTokens($userId, self::SUBJECT);
            $this->userRepository->commit();
        } catch (\Throwable $exception) {
            $this->userRepository->rollback();
            throw $exception;
        }

        return true;
    }

    private function sendResetEmail(string $email, string $url): void
    {
        try {
            $sent = $this->mailer->sendMail($email, 'auth/password/reset', ['url' => $url]);
        } catch (MailException $exception) {
            throw new PasswordResetMailException('Password reset email could not be sent.', 0, $exception);
        }
        if (!$sent) {
            throw new PasswordResetMailException('Password reset email could not be sent.');
        }
    }
}
