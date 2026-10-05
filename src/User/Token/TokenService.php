<?php

namespace App\User\Token;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\Db\Db;

class TokenService
{
    private EntityRepositoryInterface $userTokenRepository;
    private Db $db;

    public function __construct(EntityManager $entityManager)
    {
        $this->userTokenRepository = $entityManager->getRepository('user-token');
        $this->db = $entityManager->getDb();
    }

    public function generateToken(string $userId, string $subject, int $expiry = 0): string
    {
        if ($expiry < 0) {
            throw new \InvalidArgumentException('Token expiry must not be negative.');
        }
        $token = bin2hex(random_bytes(32));
        $data = [
            'id' => bin2hex(random_bytes(16)),
            'user_id' => $userId,
            'token_type' => $subject,
            'token' => hash('sha256', $token),
            'expires_at' => $expiry === 0 ? null : time() + $expiry,
        ];
        $this->userTokenRepository->insert($data);
        return $token;
    }

    public function validateToken(string $token, string $subject): bool
    {
        return $this->findValidToken($token, $subject) !== null;
    }

    public function findValidToken(string $token, string $subject): ?\stdClass
    {
        if (!preg_match('/\A[0-9a-f]{64}\z/', $token)) {
            return null;
        }
        $result = $this->userTokenRepository->findOne([
            'token' => ['eq' => hash('sha256', $token)],
            'token_type' => ['eq' => $subject],
        ]);
        if (!$result || ($result->expires_at !== null && $result->expires_at <= time())) {
            return null;
        }
        return $result;
    }

    public function consumeToken(string $token, string $subject): ?string
    {
        $record = $this->findValidToken($token, $subject);
        if (!$record) {
            return null;
        }
        // The conditional delete allows only one concurrent reset to consume the token.
        $where = $this->db->expr()->eq('id', $record->id)->build()
            . ' AND (expires_at IS NULL OR expires_at > '
            . $this->db->escape(date('Y-m-d H:i:s')) . ')';
        $deleted = $this->db->delete($this->userTokenRepository->getDefinition()->getTable(), $where);
        return $deleted === 1 ? $record->user_id : null;
    }

    public function revokeToken(string $token): bool
    {
        return $this->db->delete(
            $this->userTokenRepository->getDefinition()->getTable(),
            $this->db->expr()->eq('token', hash('sha256', $token)),
        ) > 0;
    }

    public function revokeUserTokens(string $userId, string $subject): void
    {
        $where = $this->db->expr()->eq('user_id', $userId)->build()
            . ' AND ' . $this->db->expr()->eq('token_type', $subject)->build();
        $this->db->delete($this->userTokenRepository->getDefinition()->getTable(), $where);
    }
}