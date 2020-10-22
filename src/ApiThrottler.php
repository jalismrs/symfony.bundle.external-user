<?php
declare(strict_types = 1);

namespace Jalismrs\ApiThrottlerBundle;

use Maba\GentleForce\Exception\RateLimitReachedException;
use Maba\GentleForce\RateLimitProvider;
use Maba\GentleForce\ThrottlerInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use function random_int;
use function usleep;

/**
 * Class ApiThrottler
 *
 * @package Jalismrs\ApiThrottlerBundle
 */
class ApiThrottler
{
    /**
     * cap
     *
     * @var int
     */
    private int $cap = -1;
    
    /**
     * rateLimitProvider
     *
     * @var \Maba\GentleForce\RateLimitProvider
     */
    private RateLimitProvider $rateLimitProvider;
    /**
     * throttler
     *
     * @var \Maba\GentleForce\Throttler|\Maba\GentleForce\ThrottlerInterface
     */
    private ThrottlerInterface $throttler;
    
    /**
     * ApiThrottler constructor.
     *
     * @param \Maba\GentleForce\RateLimitProvider  $rateLimitProvider
     * @param \Maba\GentleForce\ThrottlerInterface $throttler
     */
    public function __construct(
        RateLimitProvider $rateLimitProvider,
        ThrottlerInterface $throttler
    ) {
        $this->rateLimitProvider = $rateLimitProvider;
        $this->throttler         = $throttler;
    }
    
    /**
     * setCap
     *
     * @param int $cap
     *
     * @return void
     */
    public function setCap(
        int $cap
    ) : void {
        $this->cap = $cap;
    }
    
    /**
     * registerRateLimits
     *
     * @param string $useCaseKey
     * @param array  $rateLimits
     *
     * @return void
     */
    public function registerRateLimits(
        string $useCaseKey,
        array $rateLimits
    ) : void {
        $this->rateLimitProvider->registerRateLimits(
            $useCaseKey,
            $rateLimits
        );
    }
    
    /**
     * waitAndIncrease
     *
     * @param string $useCaseKey
     * @param string $identifier
     *
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException
     */
    public function waitAndIncrease(
        string $useCaseKey,
        string $identifier
    ) : void {
        $loop = 0;
        while ($loop !== $this->cap) {
            try {
                $this->throttler->checkAndIncrease(
                    $useCaseKey,
                    $identifier
                );
                $loop = $this->cap;
            } catch (RateLimitReachedException $rateLimitReachedException) {
                /** @noinspection PhpUnhandledExceptionInspection */
                $epsilon = random_int(
                    100,
                    1000
                );
                
                $waitInSeconds = (int)$rateLimitReachedException->getWaitForInSeconds();
                
                ++$loop;
                if ($loop === $this->cap) {
                    throw new TooManyRequestsHttpException(
                        $waitInSeconds,
                        'Loop limit was reached',
                        $rateLimitReachedException
                    );
                }
                usleep(1000000 * $waitInSeconds + $epsilon);
            }
        }
    }
    
    /**
     * decrease
     *
     * @param string $useCaseKey
     * @param string $identifier
     *
     * @return void
     */
    public function decrease(
        string $useCaseKey,
        string $identifier
    ) : void {
        $this->throttler->decrease(
            $useCaseKey,
            $identifier
        );
    }
    
    /**
     * reset
     *
     * @param string $useCaseKey
     * @param string $identifier
     *
     * @return void
     */
    public function reset(
        string $useCaseKey,
        string $identifier
    ) : void {
        $this->throttler->reset(
            $useCaseKey,
            $identifier
        );
    }
}
