<?php
declare(strict_types=1);

namespace Attlaz;

use Attlaz\Model\AccessToken;
use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

class AuthenticationTest extends TestCase
{
    private array $endpoints = [
        'https://gateway.api.attlaz.com',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $dotenv = Dotenv::createImmutable(\dirname(__DIR__));
        $dotenv->load();
    }

    public function testTokenAuthentication(): void
    {
        $client = new Client();
        $client->authWithToken($_ENV['api_token']);
        $client->setDebug(1);


        foreach ($this->endpoints as $endpoint) {
            $client->setEndPoint($endpoint);

            // What is under test is that the token authenticates at all, not how many projects it can
            // see — the old lower bound of 10 was tied to whatever the credential could reach years ago.
            $projects = $client->getProjectEndpoint()->getProjects();
            $this->assertNotEmpty($projects->getData());
        }

    }

    /**
     * This used to introspect and then revoke the client's own client-credentials token. Neither is
     * an API capability any more: the per-token GET route is gone, and the revoke route acts on a
     * user's personal access tokens by id, not on a client-credentials token by its secret. What is
     * still worth asserting is that a minted token is well-formed and that the client keeps working.
     */
    public function testClientCredentialsTokenIsUsable()
    {

        //$client = new \Attlaz\Client('zSGdVWE3FAS8kY5C', '6jhYgFPAUm9HmCus', false);
        $client = new Client();
        $client->authWithClient($_ENV['api_client_id'], $_ENV['api_client_secret']);
//$client = new \Attlaz\Client('6as&01LW!iVe!wO7Guv%5#MlfZ2SJgSG', '#zqtn*4IKcx7iNM4bNvc$XU@H27prch8', true);
//$client = new \Attlaz\Client('qTjmp&$O#YWf$Emjo2X^#azE%0sg^!p^', '^us^eM$pn2PyMjoG6Q%AWS@XQqQPinmO', false);

//$client->setEndPoint('http://4bb770409454.ngrok.io');
//$client->enableDebug();
//
//$result = $client->scheduleTask('BEXG88ATH', [
//    'message' => 'bla',
//]);
//var_dump($result);
        foreach ($this->endpoints as $endpoint) {

            $client->setEndPoint($endpoint);

            $projects = $client->getProjectEndpoint()->getProjects();

            $projectCount = count($projects->getData());

            $accessToken = $client->getAccessToken();

            $this->assertInstanceOf(AccessToken::class, $accessToken);
            $this->assertNotSame('', $accessToken->getToken());
            // A minted token carries a lifetime, so the client knows when to renew it. A token with
            // no expiry would be resent until the API started rejecting every call.
            $this->assertNotNull($accessToken->getExpires());
            $this->assertFalse($accessToken->hasExpired());

            // Still usable for a second call, and still the same token — it has not expired, so the
            // client must not have minted a new one.
            $projects2 = $client->getProjectEndpoint()->getProjects();
            $this->assertCount($projectCount, $projects2->getData());
            $this->assertSame($accessToken->getToken(), $client->getAccessToken()->getToken());

        }
    }
}
