<?php
declare(strict_types=1);

namespace Attlaz;

use Attlaz\Model\AccessToken;
use PHPUnit\Framework\TestCase;

/**
 * Needs no credentials and no network.
 */
class AccessTokenTest extends TestCase
{
    public function testCarriesTheToken(): void
    {
        $token = new AccessToken('abc123');

        $this->assertSame('abc123', $token->getToken());
        $this->assertNull($token->getExpires());
        $this->assertNull($token->getRefreshToken());
    }

    public function testRejectsAnEmptyToken(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AccessToken('');
    }

    public function testExpiryInThePastHasExpired(): void
    {
        $this->assertTrue((new AccessToken('t', \time() - 1))->hasExpired());
    }

    public function testExpiryInTheFutureHasNotExpired(): void
    {
        $this->assertFalse((new AccessToken('t', \time() + 3600))->hasExpired());
    }

    /**
     * The reason the margin exists: a token with seconds left passes an exact check and then dies
     * mid-request.
     */
    public function testMarginRenewsEarly(): void
    {
        $token = new AccessToken('t', \time() + 30);

        $this->assertFalse($token->hasExpired());
        $this->assertTrue($token->hasExpired(60));
    }

    /**
     * League's version throws here. A token we cannot date is not evidence of expiry — the Client
     * decides what to do with it, based on whether the caller supplied it.
     */
    public function testUnknownExpiryIsNotTreatedAsExpired(): void
    {
        $this->assertFalse((new AccessToken('t'))->hasExpired());
        $this->assertFalse((new AccessToken('t'))->hasExpired(3600));
    }

    public function testKeepsARefreshToken(): void
    {
        $token = new AccessToken('t', \time() + 60, 'refresh-me');

        $this->assertSame('refresh-me', $token->getRefreshToken());
    }
}
