<?php
declare(strict_types=1);

namespace Attlaz\Model\ProviderToken;

class ProviderTokenAccessToken
{
    public string $accessToken;

    public function __construct(array $data)
    {
        $this->accessToken = $data['access_token'];
    }
}
