<?php
declare(strict_types=1);

namespace Attlaz;

use Attlaz\Endpoint\AccessTokenEndpoint;
use Attlaz\Endpoint\ConfigEndpoint;
use Attlaz\Endpoint\ConnectionEndpoint;
use Attlaz\Endpoint\DeployEndpoint;
use Attlaz\Endpoint\Endpoint;
use Attlaz\Endpoint\FlowEndpoint;
use Attlaz\Endpoint\LogEndpoint;
use Attlaz\Endpoint\ProjectEndpoint;
use Attlaz\Endpoint\ProjectEnvironmentEndpoint;
use Attlaz\Endpoint\StorageEndpoint;
use Attlaz\Helper\TokenStorage;
use Attlaz\Model\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\RequestInterface;

class Client
{
    private string $endPoint = 'https://api.attlaz.com';
    private string|null $clientId = null;
    private string|null $clientSecret = null;
    private bool $storeToken = false;
    private int $timeout = 20;
    private int $debugLevel = 0;
    private bool $profileRequests = false;
    private array $profiles = [];
    private GenericProvider $provider;
    private AccessToken|null $accessToken = null;
    private array $endpoints = [];

    public function __construct()
    {
        $this->provider = new GenericProvider([
            'redirectUri' => 'https://attlaz.com/',
            'urlAuthorize' => $this->endPoint . '/oauth/authorize',
            'urlAccessToken' => $this->endPoint . '/oauth/token',
            'urlResourceOwnerDetails' => $this->endPoint . '/oauth/resource',
            'base_uri' => $this->endPoint,
            'timeout' => $this->timeout,
        ]);
    }

    public function setEndPoint(string $endPoint): void
    {
        if ($endPoint === '') {
            throw new \InvalidArgumentException('Endpoint cannot be empty');
        }
        $this->endPoint = rtrim($endPoint, "/");
        // TODO: update provider with endpoint
    }

    public function authWithClient(string $clientId, string $clientSecret, bool $storeToken = false): void
    {
        if (empty($clientId)) {
            throw new \InvalidArgumentException('ClientId cannot be empty');
        }
        $this->clientId = $clientId;

        if (empty($clientSecret)) {
            throw new \InvalidArgumentException('ClientSecret secret cannot be empty');
        }
        $this->clientSecret = $clientSecret;
        $this->storeToken = $storeToken;
    }

    public function authWithToken(string $token): void
    {
        $accessToken = new AccessToken([
            'access_token' => $token,
            'expires_in' => null,
        ]);
        $this->accessToken = $accessToken;
    }

    private function isAuthenticated(): bool
    {
        if ($this->accessToken === null) {
            return false;
        }
        if (empty($this->accessToken->getExpires())) {
            return true;
        }
        return $this->accessToken->hasExpired();
    }

    private function authenticate(): void
    {


        try {
            if (!$this->isAuthenticated()) {
                if ($this->clientId === null || $this->clientSecret === null) {
                    throw new \Exception('Token is expired and no client details are defined');
                }

                $this->provider = new GenericProvider([
                    'clientId' => $this->clientId,
                    'clientSecret' => $this->clientSecret,
                    'redirectUri' => 'https://attlaz.com/',
                    'urlAuthorize' => $this->endPoint . '/oauth/authorize',
                    'urlAccessToken' => $this->endPoint . '/oauth/token',
                    'urlResourceOwnerDetails' => $this->endPoint . '/oauth/resource',
                    'base_uri' => $this->endPoint,
                    'timeout' => $this->timeout,
                ]);

                $accessToken = null;
                if ($this->storeToken) {
                    $accessToken = TokenStorage::loadAccessToken($this->clientId, $this->clientSecret);
                }

                if ($accessToken !== null) {
                    $this->accessToken = $accessToken;
                } else {
                    $this->accessToken = $this->provider->getAccessToken('client_credentials', [
                        'scope' => 'all',
                    ]);
                    if ($this->storeToken) {
                        TokenStorage::saveAccessToken($this->accessToken, $this->clientId, $this->clientSecret);
                    }
                }
            }
        } catch (IdentityProviderException $ex) {
            throw new \Exception('Unable to authenticate: ' . $ex->getMessage());
        } catch (\Throwable $ex) {
//            if ($this->d) {
//                \var_dump($ex);
//            }
//            \var_dump($ex);
            throw new \Exception('Unable to authenticate: ' . $ex->getMessage());
        }
    }

    public function getAccessToken(): AccessToken|null
    {
        return $this->accessToken;
    }

    public function setAccessToken(AccessToken $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    public function createRequest(string $method, string $uri, array|object|null $body = null): RequestInterface
    {
        $this->authenticate();
        if ($this->accessToken === null) {
            throw new \Exception('Unable to create request: not authenticated');
        }
        $options = [];
        if ($body !== null) {
            $options['body'] = \json_encode($body, JSON_THROW_ON_ERROR);
        }

        $options['headers'] = ['Content-Type' => 'application/json'];

        $url = $this->endPoint . $uri;

        return $this->provider->getAuthenticatedRequest($method, $url, $this->accessToken, $options);
    }


    public function sendRequest(RequestInterface $request): array
    {
        $response = null;
        try {

            $startTime = \microtime(true);

            $options = [
                'debug' => ($this->debugLevel === 2),
            ];
            $response = $this->provider->getHttpClient()
                ->send($request, $options);


            $jsonResponse = \json_decode($response->getBody()
                ->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (ClientException $ex) {

            $exception = new RequestException($ex->getMessage());
            $exception->httpCode = $ex->getCode();
            throw $exception;
        } catch (\Throwable $ex) {
            throw new RequestException($ex->getMessage());
        } finally {
            if ($this->profileRequests) {
                $seconds = \microtime(true) - $startTime;

                $this->profiles[] = [
                    'Uri' => $request->getUri()->__toString(),
                    'Method' => $request->getMethod(),
                    'Response code' => $response === null ? '' : $response->getStatusCode(),
                    'Duration' => $seconds,
                ];
            }
        }

        return $jsonResponse;
    }

    //    public function scheduleTaskByCommand(string $branch, string $command, array $arguments = []): ScheduleTaskResult
    //    {
    //        $body = [
    //            'command'   => $command,
    //            'arguments' => $arguments,
    //        ];
    //
    //        $uri = '/branches/' . $branch . '/taskexecutionrequests';
    //
    //        $request = $this->createRequest('POST', $uri, $body);
    //
    //        $response = $this->sendRequest($request);
    //
    //        //TODO: validate response & handle issues
    //        $success = ($response['success'] === true || $response['success'] === 'true');
    //
    //        $data = null;
    //        if (isset($response['result']) && !empty($response['result'])) {
    //            $data = json_decode($response['result'], true);
    //            $data = $data['data'];
    //        }
    //
    //        $result = new ScheduleTaskResult($success, $response['taskExecutionRequest']);
    //        $result->result = $data;
    //
    //        return $result;
    //    }


    public function getApiVersion(): ?string
    {
        $uri = '/system/health';

        $request = $this->createRequest('GET', $uri);


        $rawResponse = $this->sendRequest($request);
        if (isset($rawResponse['version'])) {
            return $rawResponse['version'];
        }
        return null;
    }

    public function setDebug(int $debugLevel): void
    {
        $this->debugLevel = $debugLevel;
    }


    public function enableRequestProfiling(): void
    {
        $this->profileRequests = true;
    }

    public function disableRequestProfiling(): void
    {
        $this->profileRequests = false;
    }

    public function getProfiles(): array
    {
        return $this->profiles;
    }

    public function getStorageEndpoint(): StorageEndpoint
    {
        return $this->getEndPoint(StorageEndpoint::class);
    }

    public function getLogEndpoint(): LogEndpoint
    {
        return $this->getEndPoint(LogEndpoint::class);
    }

    public function getConnectionEndpoint(): ConnectionEndpoint
    {
        return $this->getEndPoint(ConnectionEndpoint::class);
    }

    public function getProjectEndpoint(): ProjectEndpoint
    {
        return $this->getEndPoint(ProjectEndpoint::class);
    }

    public function getProjectEnvironmentEndpoint(): ProjectEnvironmentEndpoint
    {
        return $this->getEndPoint(ProjectEnvironmentEndpoint::class);
    }

    public function getFlowEndpoint(): FlowEndpoint
    {
        return $this->getEndPoint(FlowEndpoint::class);
    }

    public function getConfigEndpoint(): ConfigEndpoint
    {
        return $this->getEndPoint(ConfigEndpoint::class);
    }

    public function getDeployEndpoint(): DeployEndpoint
    {
        return $this->getEndPoint(DeployEndpoint::class);
    }

    public function getAccessTokenEndpoint(): AccessTokenEndpoint
    {
        return $this->getEndPoint(AccessTokenEndpoint::class);
    }

    /**
     * @template T
     * @param class-string<T> $endpointClass
     * @return T
     * @throws \Exception
     */
    private function getEndPoint(string $endpointClass): Endpoint
    {
        if (!array_key_exists($endpointClass, $this->endpoints)) {
            $this->endpoints[$endpointClass] = new $endpointClass($this);
        }
        return $this->endpoints[$endpointClass];
    }
}
