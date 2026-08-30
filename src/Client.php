<?php
declare(strict_types=1);

namespace Attlaz;

use Attlaz\Http\Path;

use Attlaz\DataQuality\Endpoint\QualityEndpoint;
use Attlaz\Endpoint\AccessTokenEndpoint;
use Attlaz\Endpoint\CollectionsEndpoint;
use Attlaz\Endpoint\ConfigEndpoint;
use Attlaz\Endpoint\ConnectionEndpoint;
use Attlaz\Endpoint\DeployEndpoint;
use Attlaz\Endpoint\Endpoint;
use Attlaz\Endpoint\FlowEndpoint;
use Attlaz\Endpoint\LogEndpoint;
use Attlaz\Endpoint\ProjectEndpoint;
use Attlaz\Endpoint\ProjectEnvironmentEndpoint;
use Attlaz\Endpoint\ProviderTokenEndpoint;
use Attlaz\Endpoint\ServiceEndpoint;
use Attlaz\Endpoint\StorageEndpoint;
use Attlaz\Model\AccessToken;
use Attlaz\Model\Exception\RequestException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessTokenInterface as LeagueAccessToken;
use Psr\Http\Message\RequestInterface;

class Client
{
    /** Renew this many seconds before expiry, so a token cannot die mid-request. */
    private const TOKEN_EXPIRY_MARGIN_SECONDS = 60;

    /**
     * Overall request budget, and how long to wait for a connection. Matches the Stripe SDKs
     * (80s / 30s) — generous enough for a large report, bounded enough that an unreachable API fails
     * instead of hanging the process. Override the request budget with {@see setTimeout()}.
     */
    private const DEFAULT_TIMEOUT_SECONDS = 80;
    private const DEFAULT_CONNECT_TIMEOUT_SECONDS = 30;

    private string $endPoint = 'https://api.attlaz.com';
    private string|null $clientId = null;
    private string|null $clientSecret = null;
    private int $timeout = self::DEFAULT_TIMEOUT_SECONDS;
    private int $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT_SECONDS;
    private int $debugLevel = 0;
    private bool $profileRequests = false;
    private array $profiles = [];
    private GenericProvider $provider;
    private AccessToken|null $accessToken = null;
    /** True when the token came from authWithToken()/setAccessToken() rather than being minted here. */
    private bool $accessTokenIsCallerSupplied = false;

    /** @var array<string,Endpoint> */
    private array $endpoints = [];

    public function __construct()
    {
        $this->provider = $this->buildProvider();
    }

    /**
     * Build the OAuth provider against the current endpoint and timeouts.
     *
     * The HTTP client is supplied explicitly rather than left to league to build. Left to itself,
     * league forwards only a hardcoded allowlist of options to Guzzle —
     * `['timeout', 'proxy']`, see AbstractProvider::getAllowedClientOptions — so `connect_timeout`
     * was silently dropped and setConnectTimeout() had no effect on the token request. Passing the
     * client as a collaborator is the only way to give the token request the same connect budget
     * that {@see sendRequest()} applies to every other call.
     *
     * @param array<string,mixed> $extra
     */
    private function buildProvider(array $extra = []): GenericProvider
    {
        return new GenericProvider(\array_merge([
            'redirectUri' => 'https://attlaz.com/',
            'urlAuthorize' => $this->endPoint . '/oauth/authorize',
            'urlAccessToken' => $this->endPoint . '/oauth/token',
            'urlResourceOwnerDetails' => $this->endPoint . '/oauth/resource',
            'base_uri' => $this->endPoint,
            'timeout' => $this->timeout,
        ], $extra), [
            'httpClient' => new HttpClient([
                'timeout' => $this->timeout,
                'connect_timeout' => $this->connectTimeout,
                'read_timeout' => $this->timeout,
            ]),
        ]);
    }

    /**
     * Authenticate with client credentials. A token is minted on the first request and kept for the
     * life of this Client instance.
     *
     * To reuse a token across processes (PHP builds a fresh Client per request), store it yourself —
     * Redis, APCu, a session — and pass it to {@see authWithToken()}. The client used to cache tokens
     * to an encrypted file on disk; that was removed because the file landed in the working directory
     * with default permissions, and a caller-owned store is both safer and more flexible.
     */
    public function authWithClient(string $clientId, string $clientSecret): void
    {
        if (empty($clientId)) {
            throw new \InvalidArgumentException('ClientId cannot be empty');
        }
        $this->clientId = $clientId;

        if (empty($clientSecret)) {
            throw new \InvalidArgumentException('ClientSecret secret cannot be empty');
        }
        $this->clientSecret = $clientSecret;
    }

    public function authWithToken(string $token): void
    {
        // No expiry: only the caller knows how long a token they supply lasts.
        $this->accessToken = new AccessToken($token);
        $this->accessTokenIsCallerSupplied = true;
    }

    /** Overall budget for a single request, in seconds. */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    /** How long to wait for the connection itself, in seconds. */
    public function setConnectTimeout(int $connectTimeout): void
    {
        $this->connectTimeout = $connectTimeout;
    }

    public function getApiVersion(): ?string
    {
        $uri = '/system/info';

        $request = $this->createRequest('GET', $uri);


        $rawResponse = $this->sendRequest($request);
        if (isset($rawResponse['version'])) {
            return $rawResponse['version'];
        }
        return null;
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

        // Accept is stated explicitly, even though JSON is what the API returns by default today.
        // The server plans to make bytes the default once no request arrives without an Accept, and a
        // silent client is indistinguishable from one that did not care — which is what makes that
        // flip unsafe.
        $options['headers'] = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];

        return $this->buildAuthenticatedRequest($method, $uri, $options);
    }

    /**
     * A request whose body IS raw bytes. Metadata that would live in a JSON envelope travels as
     * headers instead, because there is no envelope left to put it in.
     *
     * @param array<string, string> $headers
     */
    public function createBinaryRequest(string $method, string $uri, string|null $body = null, array $headers = []): RequestInterface
    {
        $this->authenticate();
        if ($this->accessToken === null) {
            throw new \Exception('Unable to create request: not authenticated');
        }

        $options = [];
        if ($body !== null) {
            $options['body'] = $body;
        }
        $options['headers'] = \array_merge([
            'Content-Type' => 'application/octet-stream',
            'Accept' => 'application/octet-stream',
        ], $headers);

        return $this->buildAuthenticatedRequest($method, $uri, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function buildAuthenticatedRequest(string $method, string $uri, array $options): RequestInterface
    {
        if (!str_starts_with($uri, 'https://') && !str_starts_with($uri, 'http://')) {
            $uri = $this->endPoint . $uri;
        }

        // The raw string, not a token object: league's BearerAuthorizationTrait concatenates whatever
        // it is given onto "Bearer ", and it documents accepting a string. Passing it this way keeps
        // league's token type out of our storage entirely.
        return $this->provider->getAuthenticatedRequest($method, $uri, $this->accessToken->getToken(), $options);
    }

    public function getAccessToken(): AccessToken|null
    {
        return $this->accessToken;
    }

    public function setAccessToken(AccessToken $accessToken): void
    {
        $this->accessToken = $accessToken;
        $this->accessTokenIsCallerSupplied = true;
    }

    /**
     * Send a request whose response is raw bytes, returned undecoded. A JSON decode here would fail
     * on binary, and decoding to text would corrupt it — a UTF-8 round trip replaces every invalid
     * sequence, so the bytes that come back are not the bytes that were stored.
     */
    public function sendBinaryRequest(RequestInterface $request): string
    {
        try {
            $options = [
                'debug' => ($this->debugLevel === 2),
                'timeout' => $this->timeout,
                'connect_timeout' => $this->connectTimeout,
                'read_timeout' => $this->timeout,
            ];
            $response = $this->provider->getHttpClient()
                ->send($request, $options);

            return $response->getBody()
                ->getContents();
        } catch (ClientException $ex) {
            $exception = new RequestException($ex->getMessage());
            $exception->httpCode = $ex->getCode();
            throw $exception;
        } catch (\Throwable $ex) {
            throw new RequestException($ex->getMessage());
        }
    }

    public function sendRequest(RequestInterface $request): array
    {
        $response = null;
        $startTime = \microtime(true);
        try {

            // These used to be hardcoded to 0, which in Guzzle means "wait forever" — so setTimeout()
            // had no effect on API calls and an unreachable API hung the process indefinitely.
            $options = [
                'debug' => ($this->debugLevel === 2),
                'timeout' => $this->timeout,
                'connect_timeout' => $this->connectTimeout,
                'read_timeout' => $this->timeout,
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

    public function setDebug(int $debugLevel): void
    {
        $this->debugLevel = $debugLevel;
    }

    public function enableRequestProfiling(): void
    {
        $this->profileRequests = true;
    }

    //    public function scheduleTaskByCommand(string $branch, string $command, array $arguments = []): ScheduleTaskResult
    //    {
    //        $body = [
    //            'command'   => $command,
    //            'arguments' => $arguments,
    //        ];
    //
    //        $uri = Path::build('/branches/:branch/taskexecutionrequests', ['branch' => $branch]);
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

    public function getProviderTokenEndpoint(): ProviderTokenEndpoint
    {
        return $this->getEndPoint(ProviderTokenEndpoint::class);
    }

    public function getServiceEndpoint(): ServiceEndpoint
    {
        return $this->getEndPoint(ServiceEndpoint::class);
    }

    public function getCollectionsEndpoint(): CollectionsEndpoint
    {
        return $this->getEndPoint(CollectionsEndpoint::class);
    }

    public function getQualityEndpoint(): QualityEndpoint
    {
        return $this->getEndPoint(QualityEndpoint::class);
    }

    /**
     * @template T of Endpoint
     * @param class-string<T> $endpointClass
     * @return T
     * @throws \Exception
     */
    public function getEndPoint(string $endpointClass): Endpoint
    {
        if (!\array_key_exists($endpointClass, $this->endpoints)) {
            if (!\is_subclass_of($endpointClass, Endpoint::class)) {
                throw new \Exception('Endpoint must be subclass of Endpoint');
            }
            $this->endpoints[$endpointClass] = new $endpointClass($this);
        }
        return $this->endpoints[$endpointClass];
    }

    public function setEndPoint(string $endPoint): void
    {
        if ($endPoint === '') {
            throw new \InvalidArgumentException('Endpoint cannot be empty');
        }
        $this->endPoint = rtrim($endPoint, "/");
        // Rebuild so the provider's token urls follow the new endpoint. authenticate() rebuilds it
        // anyway, but a client using authWithToken() never gets there and kept the construction-time
        // endpoint.
        $this->provider = $this->buildProvider();
    }

    private function authenticate(): void
    {


        try {
            if (!$this->isAuthenticated()) {
                if ($this->clientId === null || $this->clientSecret === null) {
                    throw new \Exception('Token is expired and no client details are defined');
                }

                $this->provider = $this->buildProvider([
                    'clientId' => $this->clientId,
                    'clientSecret' => $this->clientSecret,
                ]);

                $leagueToken = $this->provider->getAccessToken('client_credentials', [
                    'scope' => 'all',
                ]);
                if (!$leagueToken instanceof LeagueAccessToken) {
                    throw new \Exception('Unexpected access token type');
                }
                // Converted here so league's token type never reaches our own storage or public API.
                $this->accessToken = self::toAccessToken($leagueToken);
                $this->accessTokenIsCallerSupplied = false;
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

    /**
     * The one place league's token type is translated into ours. Keeping the conversion here — rather
     * than a factory on AccessToken — means the model itself has no knowledge of league at all.
     */
    private static function toAccessToken(LeagueAccessToken $leagueToken): AccessToken
    {
        $expires = $leagueToken->getExpires();

        return new AccessToken(
            $leagueToken->getToken(),
            $expires === null ? null : (int)$expires,
            $leagueToken->getRefreshToken(),
        );
    }

    private function isAuthenticated(): bool
    {
        if ($this->accessToken === null) {
            return false;
        }

        if ($this->accessToken->getExpires() === null) {
            // A token the caller handed us: only they know its lifetime, so trust it and let a 401
            // surface if it is stale. A token we minted that came back without an expiry is a
            // different matter — treating it as valid forever means we would keep sending it until
            // the API starts rejecting every call, so mint a fresh one instead.
            return $this->accessTokenIsCallerSupplied;
        }

        // Renewed slightly early, so a token with a few hundred milliseconds left is replaced rather
        // than dying in flight.
        return !$this->accessToken->hasExpired(self::TOKEN_EXPIRY_MARGIN_SECONDS);
    }
}
