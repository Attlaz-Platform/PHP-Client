<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Http\Path;
use Attlaz\Model\CollectionResult;
use Attlaz\Model\CursorPagination;
use Attlaz\Model\Exception\RequestException;
use Attlaz\Model\State;
use Attlaz\Model\UserAccessToken;
use Attlaz\Model\UserAccessTokenStatus;

/**
 * Personal access tokens — the API keys a user creates for themselves.
 *
 * **These routes act on the authenticated user's own tokens.** They need a request authenticated as
 * a user, so they work with {@see \Attlaz\Client::authWithToken()} carrying a personal access token,
 * not with the client-credentials grant, which has no user behind it.
 *
 * Mirrors the JavaScript client's AccessTokenEndpoint. This class previously targeted
 * `/access_tokens/…`, passed the secret token where the API expects the token's id, and offered a
 * per-token `get()` for a route that no longer exists — none of it had worked for some time.
 */
class AccessTokenEndpoint extends Endpoint
{
    /**
     * The authenticated user's tokens.
     *
     * @return CollectionResult<UserAccessToken>
     * @throws RequestException
     */
    public function getByUser(CursorPagination|null $pagination = null, UserAccessTokenStatus|null $status = null): CollectionResult
    {
        $uri = '/access-tokens';
        if ($status !== null) {
            $uri .= '?status=' . \rawurlencode($status->value);
        }

        $parser = fn(array $raw): UserAccessToken => self::parseUserAccessToken($raw);

        return $this->requestCollection($uri, $pagination, $parser);
    }

    /**
     * Create a token. The secret is on the returned object's `token` and is **never returned again** —
     * store it now or it is lost.
     *
     * @param array<int,string> $scopes
     */
    public function create(string $name, array $scopes, \DateTimeInterface $expiresAt): UserAccessToken
    {
        $response = $this->requestObject('/access-tokens', [
            'name' => $name,
            'scopes' => $scopes,
            'expires_at' => $expiresAt->format(\DateTimeInterface::RFC3339_EXTENDED),
        ], 'POST');

        if ($response === null) {
            throw new \Exception('Unable to create access token: no response from the API');
        }

        return self::parseUserAccessToken($response);
    }

    /**
     * Rename a token. Only the name is editable — the API deliberately refuses to change scopes, the
     * IP allow-list or the expiry after creation, so a token's security boundary cannot be widened
     * by an update.
     */
    public function update(string $accessTokenId, string $name): UserAccessToken
    {
        $uri = Path::build('/access-tokens/:accessTokenId', ['accessTokenId' => $accessTokenId]);

        $response = $this->requestObject($uri, ['name' => $name], 'POST');
        if ($response === null) {
            throw new \Exception('Unable to update access token: no token with id "' . $accessTokenId . '" found');
        }

        return self::parseUserAccessToken($response);
    }

    /**
     * Revoke a token by its **id** — not by the token string itself.
     */
    public function revoke(string $accessTokenId): bool
    {
        $uri = Path::build('/access-tokens/:accessTokenId', ['accessTokenId' => $accessTokenId]);

        $result = $this->requestObject($uri, null, 'DELETE');
        if ($result === null) {
            throw new \Exception('Unable to revoke access token: no token with id "' . $accessTokenId . '" found');
        }
        if (!\array_key_exists('revoked', $result)) {
            throw new \Exception('Unable to revoke access token: response from `' . $uri . '` has no `revoked` field');
        }

        return (bool)$result['revoked'];
    }

    /**
     * @param array<string,mixed> $raw
     */
    private static function parseUserAccessToken(array $raw): UserAccessToken
    {
        $token = new UserAccessToken();
        $token->id = (string)$raw['id'];
        $token->userId = (string)$raw['user'];
        $token->name = (string)($raw['name'] ?? '');
        $token->ipAllowList = $raw['ip_allow_list'] ?? [];
        $token->scopes = $raw['scopes'] ?? [];
        // The real secret from create(), a masked display value on any read — see UserAccessToken::$token.
        $token->token = isset($raw['token']) ? (string)$raw['token'] : null;
        $token->createdAt = self::parseDate($raw['created_at']) ?? new \DateTime();
        $token->lastUsedAt = self::parseDate($raw['last_used_at'] ?? null);
        $token->expiresAt = self::parseDate($raw['expires_at'] ?? null);
        $token->state = State::from((string)$raw['state']);

        return $token;
    }

    /**
     * Built with the constructor rather than createFromFormat(RFC3339_EXTENDED): that format demands
     * fractional seconds and returns false — not an error — for a timestamp without them.
     */
    private static function parseDate(mixed $value): \DateTime|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        return new \DateTime((string)$value);
    }
}
