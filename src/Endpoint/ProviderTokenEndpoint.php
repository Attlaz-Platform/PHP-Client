<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Model\Exception\RequestException;
use Attlaz\Model\ProviderToken\ProviderToken;
use Attlaz\Model\ProviderToken\ProviderTokenAccessToken;

class ProviderTokenEndpoint extends Endpoint
{
    /**
     * @param string $providerTokenId
     * @return ProviderToken|null
     * @throws RequestException
     */
    public function getProviderToken(string $providerTokenId): ProviderToken|null
    {
        $uri = '/provider-tokens/' . $providerTokenId;
        $response = $this->requestObject($uri);
        if ($response === null) {
            return null;
        }
        return new ProviderToken($response);
    }

    /**
     * @param string $providerTokenId
     * @return ProviderTokenAccessToken|null
     * @throws RequestException
     */
    public function getProviderTokenAccessToken(string $providerTokenId): ProviderTokenAccessToken|null
    {
        $uri = '/provider-tokens/' . $providerTokenId . '/access-token';
        $response = $this->requestObject($uri);
        if ($response === null) {
            return null;
        }
        return new ProviderTokenAccessToken($response);
    }

    /**
     * @param string $providerTokenId
     * @return bool
     * @throws RequestException
     */
    public function revokeProviderToken(string $providerTokenId): bool
    {
        $uri = '/provider-tokens/' . $providerTokenId;
        $response = $this->requestObject($uri, null, 'DELETE');
        if ($response === null) {
            return false;
        }
        return (bool)($response['deleted'] ?? false);
    }
}
