<?php
declare(strict_types=1);

namespace Attlaz;

use Attlaz\Endpoint\ConnectionEndpoint;
use Attlaz\Endpoint\FlowEndpoint;
use Attlaz\Endpoint\LogEndpoint;
use Attlaz\Endpoint\ProjectEndpoint;
use Attlaz\Endpoint\ProjectEnvironmentEndpoint;
use Attlaz\Endpoint\StorageEndpoint;
use Attlaz\Helper\TokenStorage;
use Attlaz\Model\Exception\RequestException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\RequestInterface;

class Client
{
    private string $endPoint = 'https://api.attlaz.com';
    private string $clientId;
    private string $clientSecret;
    private bool $storeToken = false;
    private int $timeout = 20;

    private bool $debug = false;

    private ?GenericProvider $provider = null;
    private ?AccessToken $accessToken = null;

    private StorageEndpoint $storageEndpoint;
    private LogEndpoint $logEndpoint;
    private ConnectionEndpoint $connectionEndpoint;
    private ProjectEndpoint $projectEndpoint;
    private ProjectEnvironmentEndpoint $projectEnvironmentEndpoint;
    private FlowEndpoint $flowEndpoint;

    private bool $profileRequests = false;

    private array $profiles = [];

    public function __construct(string $clientId, string $clientSecret, bool $storeToken = false)
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

        $this->storageEndpoint = new StorageEndpoint($this);
        $this->logEndpoint = new LogEndpoint($this);
        $this->connectionEndpoint = new ConnectionEndpoint($this);
        $this->projectEndpoint = new ProjectEndpoint($this);
        $this->projectEnvironmentEndpoint = new ProjectEnvironmentEndpoint($this);
        $this->flowEndpoint = new FlowEndpoint($this);
    }

    public function setEndPoint(string $endPoint): void
    {
        if ($endPoint === '') {
            throw new \InvalidArgumentException('Endpoint cannot be empty');
        }
        $this->endPoint = rtrim($endPoint, "/");
    }

    private function authenticate(): void
    {


        try {
            if (\is_null($this->accessToken) || $this->accessToken->hasExpired()) {
                $this->provider = new GenericProvider([
                    'clientId'                => $this->clientId,
                    'clientSecret'            => $this->clientSecret,
                    'redirectUri'             => 'https://attlaz.com/',
                    'urlAuthorize'            => $this->endPoint . '/oauth/authorize',
                    'urlAccessToken'          => $this->endPoint . '/oauth/token',
                    'urlResourceOwnerDetails' => $this->endPoint . '/oauth/resource',
                    'base_uri'                => $this->endPoint,
                    'timeout'                 => $this->timeout,
                ]);

                $accessToken = null;
                if ($this->storeToken) {
                    $accessToken = TokenStorage::loadAccessToken($this->clientId, $this->clientSecret);
                }

                if (!\is_null($accessToken)) {
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
            if ($this->debug) {
                \var_dump($ex);
            }
//            \var_dump($ex);
            throw new \Exception('Unable to authenticate');
        }
    }

    public function getAccessToken(): ?AccessToken
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
        if (\is_null($this->provider) || \is_null($this->accessToken)) {
            throw new \Exception('Unable to create request: not authenticated');
        }
        $options = [];
        if ($body !== null) {
            $options['body'] = \json_encode($body);
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

            $response = $this->provider->getHttpClient()
                ->send($request, ['debug' => $this->debug]);


            $jsonResponse = \json_decode($response->getBody()
                ->getContents(), true);

        } catch (\Throwable $ex) {
            throw new RequestException($ex->getMessage());
        } finally {
            if ($this->profileRequests) {
                $seconds = \microtime(true) - $startTime;

                $this->profiles[] = [
                    'Uri'           => $request->getUri(),
                    'Method'        => $request->getMethod(),
                    'Response code' => $response === null ? '' : $response->getStatusCode(),
                    'Duration'      => $seconds
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

    public function enableDebug(): void
    {
        $this->debug = true;
    }

    public function disableDebug(): void
    {
        $this->debug = false;
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
        return $this->storageEndpoint;
    }

    public function getLogEndpoint(): LogEndpoint
    {
        return $this->logEndpoint;
    }

    public function getConnectionEndpoint(): ConnectionEndpoint
    {
        return $this->connectionEndpoint;
    }

    public function getProjectEndpoint(): ProjectEndpoint
    {
        return $this->projectEndpoint;
    }

    public function getProjectEnvironmentEndpoint(): ProjectEnvironmentEndpoint
    {
        return $this->projectEnvironmentEndpoint;
    }

    public function getFlowEndpoint(): FlowEndpoint
    {
        return $this->flowEndpoint;
    }
}
