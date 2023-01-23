<?php
declare(strict_types=1);

namespace Attlaz;

use Attlaz\Model\StorageItem;
use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

class StorageTest extends TestCase
{

    private array $endpoints = ['https://api.attlaz.com/1.9',
                                //            'https://api.attlaz.com/1.6',
                                //            'https://api.attlaz.com/1.7',
                                //            'https://api.attlaz.com/1.8',
                                //            'https://api.attlaz.com/beta',
                                //'https://24c4-188-211-160-246.ngrok.io/'
    ];

    public function setUp(): void
    {
        parent::setUp();
        $dotenv = Dotenv::createImmutable(\dirname(__DIR__));
        $dotenv->load();
    }

    public function testSpecialKeys()
    {
        $client = new Client($_ENV['api_client_id'], $_ENV['api_client_secret']);
//        $client->enableDebug();


        $keys = [
//            'testkey',
'87707-RAX SOFT 200 BLACK TECHNISCHE FICHE - DATASHEET - FICHE SIGNALÉTIQUE.PDF'
        ];

        foreach ($this->endpoints as $endpoint) {
            $client->setEndPoint($endpoint);


            foreach ($keys as $key) {


                $randomValue = 'randomkey-' . \rand();
                $item = new StorageItem();
                $item->key = $key;
                $item->value = $randomValue;
                $set = $client->getStorageEndpoint()->setItem('61', 'cache', $item);
                $this->assertTrue($set);

                $v = $client->getStorageEndpoint()->getItem('61', 'cache', $item->key);

                $this->assertEquals($randomValue, $v->value);
                $this->assertEquals($item->value, $v->value);
            }
        }
    }

    public function testWriteItem()
    {
        $client = new Client($_ENV['api_client_id'], $_ENV['api_client_secret']);
        $client->enableDebug();


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

        foreach ($this->endpoints as $endpoint) {
            $client->setEndPoint($endpoint);


            foreach ($values as $value) {


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
