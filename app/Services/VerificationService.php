<?php

namespace App\Services;

use App\Exceptions\AppException;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\RedisManager;

class VerificationService
{
    /**
     * @var RedisManager|PhpRedisConnection
     */
    private RedisManager $redis;
    private NotificationService $notificationService;

    /**
     * @param RedisManager $redis
     * @param NotificationService $notificationService
     */
    public function __construct(
        RedisManager $redis,
        NotificationService $notificationService
    ) {
        $this->redis = $redis;
        $this->notificationService = $notificationService;
    }

    /**
     * @param string $email
     *
     * @throws AppException
     */
    public function create(string $email): void
    {
        $this->flushCodes($email);
        $code = $this->createCode($email);
        $this->notificationService->code($email, $code);
    }

    /**
     * @param string $email
     * @param string $code
     *
     * @throws AppException
     */
    public function verify(string $email, string $code): void
    {
        $expectedToken = sprintf('%s:%s', $email, $code);
        $verification = $this
            ->redis
            ->get($expectedToken);

        if (!$verification) {
            $this->handleWrongVerify($email);
        }

        $this->flushAttemps($email);
        $this->flushCodes($email);
    }

    /**
     * @param string $email
     *
     * @return string
     *
     * @throws AppException
     */
    private function createCode(string $email): string
    {
        while (true) {
            $code = sprintf('%04d', random_int(0, 9999));
            $token = sprintf('%s:%s', $email, $code);
            if ($this->redis->set($token, $code, 'EX', 300, 'NX')) {
                return (string) $code;
            }
        }

        throw new AppException('can_not_generate_code');
    }

    /**
     * @param string $email
     *
     * @throws AppException
     */
    private function handleWrongVerify(string $email): void
    {
        $attemptsToken = sprintf('attempts:%s', $email);
        $this->redis->setnx($attemptsToken, 0);
        $counts = $this->redis->incr($attemptsToken);

        if ($counts > 2) {
            $this->flushAttemps($email);
            $this->flushCodes($email);

            throw new AppException('too_many_failures_code_was_flush');
        }

        throw new AppException('code_not_found');
    }

    /**
     * @param string $email
     */
    private function flushCodes(string $email): void
    {
        $codeTokens = $this->redis->keys($email . ':*');
        $this->redis->del($codeTokens);
    }

    /**
     * @param string $email
     */
    private function flushAttemps(string $email): void
    {
        $attemptsToken = sprintf('attempts:%s', $email);
        $this->redis->del($attemptsToken);
    }
}
