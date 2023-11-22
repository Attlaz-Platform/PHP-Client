<?php
declare(strict_types=1);

namespace Attlaz;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

class AuthenticationTest extends TestCase
{
    private array $endpoints = [
//        'https://api.attlaz.com',
//        'https://api.attlaz.com/1.6',
//        'https://api.attlaz.com/1.7',
//        'https://api.attlaz.com/1.8',
        'https://api.attlaz.com/1.9',
//        'https://api.attlaz.com/beta'
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
            echo 'Get projects' . PHP_EOL;
            $projects = $client->getProjectEndpoint()->getProjects();
            foreach ($projects as $project) {
                echo '- ' . $project->name . PHP_EOL;
            }
        }

    }

    public function testWriteItem()
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
            echo 'Get projects' . PHP_EOL;
            $projects = $client->getProjectEndpoint()->getProjects();
            foreach ($projects as $project) {
                echo '- ' . $project->name . PHP_EOL;
            }
//if (!is_array($arguments)) {
//    var_dump($arguments);
//}
            $accessToken = $client->getAccessToken();
            $secondsToExpireToken = ($accessToken->getExpires() - time());

            $this->assertGreaterThanOrEqual(5, $secondsToExpireToken);
            echo 'Token expires in ' . $secondsToExpireToken . ' seconds' . PHP_EOL;
            $secondsToSleep = $secondsToExpireToken + 5;
            echo 'Sleep ' . $secondsToSleep . ' seconds so the token expires' . PHP_EOL;

            if ($secondsToSleep > 60) {
                throw new \Exception('Unable to perform test, make sure this token expires sooner');
            }
            sleep($secondsToSleep);

// TODO: how to make sure we have a token that expires in 30 seconds?
            echo 'Get projects' . PHP_EOL;
            $projects = $client->getProjectEndpoint()->getProjects();
            foreach ($projects as $project) {
                echo '- ' . $project->name . PHP_EOL;
            }
        }
    }
}
