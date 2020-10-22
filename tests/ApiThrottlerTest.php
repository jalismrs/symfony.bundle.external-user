<?php
declare(strict_types = 1);

namespace Tests;

use Jalismrs\ApiThrottlerBundle\ApiThrottler;
use Maba\GentleForce\Exception\RateLimitReachedException;
use Maba\GentleForce\RateLimitProvider;
use Maba\GentleForce\ThrottlerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * Class ApiThrottlerTest
 *
 * @package Tests
 *
 * @covers  \Jalismrs\ApiThrottlerBundle\ApiThrottler
 */
final class ApiThrottlerTest extends
    TestCase
{
    /**
     * mockRateLimitProvider
     *
     * @var \Maba\GentleForce\RateLimitProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private MockObject $mockRateLimitProvider;
    /**
     * mockThrottler
     *
     * @var \Maba\GentleForce\ThrottlerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private MockObject $mockThrottler;
    
    /**
     * testRegisterRateLimits
     *
     * @return void
     *
     * @throws \PHPUnit\Framework\MockObject\RuntimeException
     */
    public function testRegisterRateLimits() : void
    {
        // arrange
        $systemUnderTest = $this->createSUT();
        
        $rateLimits = [];
        
        // expect
        $this->mockRateLimitProvider
            ->expects(self::once())
            ->method('registerRateLimits')
            ->with(
                self::equalTo(ApiThrottlerProvider::USE_CASE_KEY),
                self::equalTo($rateLimits)
            );
        
        // act
        $systemUnderTest->registerRateLimits(
            ApiThrottlerProvider::USE_CASE_KEY,
            $rateLimits
        );
    }
    
    /**
     * createSUT
     *
     * @return \Jalismrs\ApiThrottlerBundle\ApiThrottler
     */
    private function createSUT() : ApiThrottler
    {
        return new ApiThrottler(
            $this->mockRateLimitProvider,
            $this->mockThrottler,
        );
    }
    
    /**
     * testWaitAndIncrease
     *
     * @return void
     *
     * @throws \PHPUnit\Framework\MockObject\RuntimeException
     * @throws \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException
     */
    public function testWaitAndIncrease() : void
    {
        // arrange
        $systemUnderTest = $this->createSUT();
        
        // expect
        $this->mockThrottler
            ->expects(self::exactly(2))
            ->method('checkAndIncrease')
            ->with(
                self::equalTo(ApiThrottlerProvider::USE_CASE_KEY),
                self::equalTo(ApiThrottlerProvider::IDENTIFIER)
            )
            ->willReturnOnConsecutiveCalls(
                self::throwException(
                    new RateLimitReachedException(
                        42,
                        'Rate limit was reached'
                    )
                ),
                null
            );
        
        // act
        $systemUnderTest->waitAndIncrease(
            ApiThrottlerProvider::USE_CASE_KEY,
            ApiThrottlerProvider::IDENTIFIER
        );
    }
    
    /**
     * testWaitAndIncreaseThrowsRateLimitReachedException
     *
     * @return void
     *
     * @throws \PHPUnit\Framework\MockObject\RuntimeException
     * @throws \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException
     */
    public function testWaitAndIncreaseThrowsRateLimitReachedException() : void
    {
        // arrange
        $systemUnderTest = $this->createSUT();
        
        // expect
        $this->expectException(TooManyRequestsHttpException::class);
        $this->expectExceptionMessage('Loop limit was reached');
        $this->mockThrottler
            ->expects(self::once())
            ->method('checkAndIncrease')
            ->with(
                self::equalTo(ApiThrottlerProvider::USE_CASE_KEY),
                self::equalTo(ApiThrottlerProvider::IDENTIFIER)
            )
            ->willThrowException(
                new RateLimitReachedException(
                    42,
                    'Rate limit was reached'
                )
            );
        
        // act
        $systemUnderTest->setCap(1);
        $systemUnderTest->waitAndIncrease(
            ApiThrottlerProvider::USE_CASE_KEY,
            ApiThrottlerProvider::IDENTIFIER
        );
    }
    
    /**
     * testDecrease
     *
     * @return void
     *
     * @throws \PHPUnit\Framework\MockObject\RuntimeException
     */
    public function testDecrease() : void
    {
        // arrange
        $systemUnderTest = $this->createSUT();
        
        // expect
        $this->mockThrottler
            ->expects(self::once())
            ->method('decrease')
            ->with(
                self::equalTo(ApiThrottlerProvider::USE_CASE_KEY),
                self::equalTo(ApiThrottlerProvider::IDENTIFIER)
            );
        
        // act
        $systemUnderTest->decrease(
            ApiThrottlerProvider::USE_CASE_KEY,
            ApiThrottlerProvider::IDENTIFIER
        );
    }
    
    /**
     * testReset
     *
     * @return void
     *
     * @throws \PHPUnit\Framework\MockObject\RuntimeException
     */
    public function testReset() : void
    {
        // arrange
        $systemUnderTest = $this->createSUT();
        
        // expect
        $this->mockThrottler
            ->expects(self::once())
            ->method('reset')
            ->with(
                self::equalTo(ApiThrottlerProvider::USE_CASE_KEY),
                self::equalTo(ApiThrottlerProvider::IDENTIFIER)
            );
        
        // act
        $systemUnderTest->reset(
            ApiThrottlerProvider::USE_CASE_KEY,
            ApiThrottlerProvider::IDENTIFIER
        );
    }
    
    /**
     * setUp
     *
     * @return void
     */
    protected function setUp() : void
    {
        parent::setUp();
        
        $this->mockRateLimitProvider = $this->createMock(RateLimitProvider::class);
        $this->mockThrottler         = $this->createMock(ThrottlerInterface::class);
    }
}
