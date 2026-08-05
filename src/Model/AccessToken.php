<?php
declare(strict_types=1);

namespace Attlaz\Model;

/**
 * An API access token.
 *
 * This replaces `League\OAuth2\Client\Token\AccessToken` on the Client's public surface. The client
 * uses one grant (client credentials) and needs three things from a token — the string, when it
 * expires, and any refresh token — so exposing a third-party type bought nothing and tied our public
 * API to a dependency we would otherwise be free to drop.
 *
 * The method names mirror league's so that consumer code calling `getToken()` / `getExpires()`
 * keeps working; only the type hint changes.
 */
final class AccessToken
{
    /**
     * @param string $token the bearer token itself
     * @param int|null $expires unix timestamp, or null when the lifetime is unknown — as it is for a
     *                          token handed to {@see \Attlaz\Client::authWithToken()}, where only the
     *                          caller knows how long it lasts
     */
    public function __construct(
        private readonly string $token,
        private readonly int|null $expires = null,
        private readonly string|null $refreshToken = null,
    ) {
        if ($token === '') {
            throw new \InvalidArgumentException('Access token cannot be empty');
        }
    }

    public function getToken(): string
    {
        return $this->token;
    }

    /** Unix timestamp, or null when the lifetime is unknown. */
    public function getExpires(): int|null
    {
        return $this->expires;
    }

    public function getRefreshToken(): string|null
    {
        return $this->refreshToken;
    }

    /**
     * Unlike league's version this does not throw when the lifetime is unknown: a token we cannot
     * date is not evidence of expiry, so it reports false and the caller decides what to do.
     *
     * @param int $marginSeconds treat the token as expired this many seconds early, so one with a few
     *                           hundred milliseconds left is renewed rather than dying in flight
     */
    public function hasExpired(int $marginSeconds = 0): bool
    {
        if ($this->expires === null) {
            return false;
        }

        return $this->expires <= (\time() + $marginSeconds);
    }
}
