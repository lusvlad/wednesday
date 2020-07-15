<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Limiters\DurationLimiter;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\InteractsWithTime;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ThrottleEmailRequests
{
    use InteractsWithTime;

    /**
     * @var RedisManager|PhpRedisConnection
     */
    private RedisManager $redis;

    public int $decaysAt;
    public int $remaining;

    public function __construct(RedisManager $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request  $request
     * @param Closure $next
     * @param int|string  $maxAttempts
     * @param float|int  $decayMinutes
     * @param string  $prefix
     *
     * @return mixed
     *
     * @throws HttpException
     */
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        $key = $prefix.$this->resolveRequestSignature($request);

        $maxAttempts = $this->resolveMaxAttempts($request, $maxAttempts);

        if ($this->tooManyAttempts($key, $maxAttempts, $decayMinutes)) {
            throw $this->buildException($key, $maxAttempts);
        }

        $response = $next($request);

        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Resolve request signature.
     *
     * @param Request $request
     *
     * @return string
     *
     * @throws RuntimeException
     */
    protected function resolveRequestSignature($request): string
    {
        if (!$email = $request->post('email')) {
            throw new RuntimeException('email_required');
        }

        return sha1($email);
    }

    /**
     * Resolve the number of attempts if the user is authenticated or not.
     *
     * @param Request $request
     * @param  int|string  $maxAttempts
     *
     * @return int
     */
    protected function resolveMaxAttempts($request, $maxAttempts): int
    {
        return (int) $maxAttempts;
    }

    /**
     * Determine if the given key has been "accessed" too many times.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  int  $decayMinutes
     * @return mixed
     */
    protected function tooManyAttempts($key, $maxAttempts, $decayMinutes)
    {
        $limiter = new DurationLimiter(
            $this->redis, $key, $maxAttempts, $decayMinutes * 60
        );

        return tap(! $limiter->acquire(), function () use ($limiter) {
            [$this->decaysAt, $this->remaining] = [
                $limiter->decaysAt, $limiter->remaining,
            ];
        });
    }

    /**
     * Create a 'too many attempts' exception.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     *
     * @return ThrottleRequestsException
     */
    protected function buildException($key, $maxAttempts): ThrottleRequestsException
    {
        $retryAfter = $this->getTimeUntilNextRetry($key);

        $headers = $this->getHeaders(
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts, $retryAfter),
            $retryAfter
        );

        return new ThrottleRequestsException(
            'Too Many Attempts.', null, $headers
        );
    }

    /**
     * Get the limit headers information.
     *
     * @param  int  $maxAttempts
     * @param  int  $remainingAttempts
     * @param  int|null  $retryAfter
     *
     * @return array
     */
    protected function getHeaders($maxAttempts, $remainingAttempts, $retryAfter = null): array
    {
        $headers = [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ];

        if (! is_null($retryAfter)) {
            $headers['Retry-After'] = $retryAfter;
            $headers['X-RateLimit-Reset'] = $this->availableAt($retryAfter);
        }

        return $headers;
    }

    /**
     * Calculate the number of remaining attempts.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  int|null  $retryAfter
     * @return int
     */
    protected function calculateRemainingAttempts($key, $maxAttempts, $retryAfter = null)
    {
        if (is_null($retryAfter)) {
            return $this->remaining;
        }

        return 0;
    }

    /**
     * Get the number of seconds until the lock is released.
     *
     * @param  string  $key
     *
     * @return int
     */
    protected function getTimeUntilNextRetry($key)
    {
        return $this->decaysAt - $this->currentTime();
    }
    /**
     * Add the limit header information to the given response.
     *
     * @param Response $response
     * @param  int  $maxAttempts
     * @param  int  $remainingAttempts
     * @param  int|null  $retryAfter
     *
     * @return Response
     */
    protected function addHeaders(Response $response, $maxAttempts, $remainingAttempts, $retryAfter = null): Response
    {
        $response->headers->add(
            $this->getHeaders($maxAttempts, $remainingAttempts, $retryAfter)
        );

        return $response;
    }
}
