<?php
declare(strict_types=1);

namespace Attlaz\Model;

/**
 * A personal access token belonging to a user. Mirrors the JavaScript client's UserAccessToken.
 *
 * Distinct from {@see AccessToken}, which is the token this client authenticates its own requests
 * with: this is a record *about* a token, returned by the API.
 *
 * See `$token` for the one field that needs care.
 */
class UserAccessToken
{
    public string $id;
    public string $userId;
    public string $name;

    /** @var array<int,string> */
    public array $ipAllowList = [];
    /** @var array<int,string> */
    public array $scopes = [];

    /**
     * The real secret **only** in the response to {@see \Attlaz\Endpoint\AccessTokenEndpoint::create()}
     * — store it then, because it is never returned again.
     *
     * On any read it is a *masked* display value (the server applies StringHelper.maskSecret), so it
     * is safe to show but will not authenticate. Verified against the live API: a value taken from a
     * list response is rejected as a credential.
     */
    public string|null $token = null;

    public \DateTime $createdAt;
    public \DateTime|null $lastUsedAt = null;
    public \DateTime|null $expiresAt = null;

    public State $state = State::Active;
}
