<?php
declare(strict_types=1);

namespace Attlaz;


use Attlaz\Model\StorageItem;
use PHPUnit\Framework\TestCase;

class StorageTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $dotenv = new \Dotenv();
        $dotenv->load(\dirname(__DIR__));
    }

    public function testWriteItem()
    {
        $client = new Client(\getenv('api_client_id'), \getenv('api_client_secret'));
//$client->setEndPoint('https://api2.attlaz.com');
        $client->setEndPoint('https://api.attlaz.com/beta/');
//$client = new \Attlaz\Client('http://10.0.75.1:8080/', '6as&01LW!iVe!wO7Guv%5#MlfZ2SJgSG', '#zqtn*4IKcx7iNM4bNvc$XU@H27prch8');

        $values = ['randomvalue', ['a' => 'yeah', 'x' => 12.3], null, true, 12.6, ['a', 'b', 1, 10]];
        foreach ($values as $value) {
            $client->enableDebug();

            $item = new StorageItem();
            $item->key = 'randomkey-' . \rand();
            $item->value = $value;
            $set = $client->getStorageEndpoint()->setItem('61', 'cache', $item);
            $this->assertTrue($set);

            $v = $client->getStorageEndpoint()->getItem('61', 'cache', $item->key);


            $this->assertEquals($item->value, $v->value);
        }


    }
}
