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

        $endpoints = [
            'https://api.attlaz.com',
            'https://api.attlaz.com/1.6',
            'https://api.attlaz.com/1.7',
            'https://api.attlaz.com/1.8',
            'https://api.attlaz.com/beta'
        ];

        $values = [
            'randomvalue',
            [
                'a' => 'yeah',
                'x' => 12.3
            ],
            null,
            true,
            12.6,
            ['a', 'b', 1, 10]
        ];

        foreach ($endpoints as $endpoint) {
            $client->setEndPoint($endpoint);


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
}
