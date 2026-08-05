<?php
declare(strict_types=1);

namespace Attlaz;

use Attlaz\Model\Exception\RequestException;
use PHPUnit\Framework\TestCase;

/**
 * Needs no credentials: authWithToken() means no token request, so the only network traffic is the
 * one request that is supposed to time out.
 *
 * 10.255.255.1 is non-routable, so the connection never completes and the call ends only when the
 * timeout fires. These used to be hardcoded to 0 — "wait forever" in Guzzle — so a request against
 * an unreachable API blocked the process indefinitely and setTimeout() had no effect at all.
 */
class TimeoutTest extends TestCase
{
    private const BLACK_HOLE = 'http://10.255.255.1:8080';

    public function testConnectTimeoutEndsTheRequest(): void
    {
        $client = new Client();
        $client->authWithToken('dummy-token-not-used');
        $client->setEndPoint(self::BLACK_HOLE);
        $client->setConnectTimeout(1);
        $client->setTimeout(5);

        $started = \microtime(true);
        try {
            $client->getStorageEndpoint()->getItem('1F6GQAEc8GYLZ5ohnaTudLOL3OG', 'cache', 'any-key');
            $this->fail('Expected the request to time out');
        } catch (RequestException $exception) {
            $elapsed = \microtime(true) - $started;
            // Generous upper bound: the point is that it gives up in about the budget, not that it
            // hangs until something else kills the process.
            $this->assertLessThan(15, $elapsed, 'Request should give up close to the connect timeout');
        }
    }

    public function testTimeoutIsConfigurable(): void
    {
        $client = new Client();
        $client->authWithToken('dummy-token-not-used');
        $client->setEndPoint(self::BLACK_HOLE);
        $client->setConnectTimeout(2);
        $client->setTimeout(30);

        $started = \microtime(true);
        try {
            $client->getStorageEndpoint()->getItem('1F6GQAEc8GYLZ5ohnaTudLOL3OG', 'cache', 'any-key');
            $this->fail('Expected the request to time out');
        } catch (RequestException $exception) {
            $elapsed = \microtime(true) - $started;
            // Bounded by connect_timeout (2s), not by the much larger overall budget.
            $this->assertLessThan(15, $elapsed);
        }
    }
}
