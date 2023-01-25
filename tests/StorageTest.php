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

    public function testSpecialKeys()
    {
        $client = new Client(\getenv('api_client_id'), \getenv('api_client_secret'));
//        $client->enableDebug();

        $endpoints = [
//            'https://api.attlaz.com',
//            'https://api.attlaz.com/1.6',
//            'https://api.attlaz.com/1.7',
//            'https://api.attlaz.com/1.8',
//'https://api.attlaz.com/1.9'
'https://55af-188-211-160-246.ngrok.io'
        ];

        $keys = [
//            'testkey',
'87707-RAX SOFT 200 BLACK TECHNISCHE FICHE - DATASHEET - FICHE SIGNALÉTIQUE.PDF'
        ];

        foreach ($endpoints as $endpoint) {
            $client->setEndPoint($endpoint);


            foreach ($keys as $key) {


                $randomValue = 'randomvalueforkey-' . \mt_rand();
                $item = new StorageItem();
                $item->key = $key;
                $item->value = $randomValue;

                $set = $client->getStorageEndpoint()->setItem('0liREOSzIzzqY2tIiSA71ZWKArz', 'cache', $item);
                $this->assertTrue($set);

                $responseItem = $client->getStorageEndpoint()->getItem('0liREOSzIzzqY2tIiSA71ZWKArz', 'cache', $item->key);

                $this->assertEquals($item->key, $responseItem->key);
                $this->assertEquals($randomValue, $responseItem->value);
                $this->assertEquals($item->value, $responseItem->value);
            }
        }
    }

    public function testHasItem()
    {
        $client = new Client(\getenv('api_client_id'), \getenv('api_client_secret'));
        $client->enableDebug();

        $endpoints = [
//            'https://api.attlaz.com',
//            'https://api.attlaz.com/1.6',
//            'https://api.attlaz.com/1.7',
//            'https://api.attlaz.com/1.8',
//'https://api.attlaz.com/1.9',
//            'https://api.attlaz.com/beta',
'https://55af-188-211-160-246.ngrok.io'
        ];
        foreach ($endpoints as $endpoint) {
            $client->setEndPoint($endpoint);


            $hasItem = $client->getStorageEndpoint()->hasItem('0liREOSzIzzqY2tIiSA71ZWKArz', 'cache', 'nonexisting-' . rand());
            $this->assertFalse($hasItem);

            $nonExistingItem = $client->getStorageEndpoint()->getItem('0liREOSzIzzqY2tIiSA71ZWKArz', 'cache', 'nonexisting-' . rand());

            $this->assertNull($nonExistingItem);


        }

    }

    public function testWriteItem()
    {
        $client = new Client(\getenv('api_client_id'), \getenv('api_client_secret'));
        $client->enableDebug();

        $endpoints = [
//            'https://api.attlaz.com',
//            'https://api.attlaz.com/1.6',
//            'https://api.attlaz.com/1.7',
//            'https://api.attlaz.com/1.8',
//'https://api.attlaz.com/1.9',
//            'https://api.attlaz.com/beta',
'https://55af-188-211-160-246.ngrok.io'
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


                $item = new StorageItem();
                $item->key = 'randomkey-' . \rand();
                $item->value = $value;
                $set = $client->getStorageEndpoint()->setItem('0liREOSzIzzqY2tIiSA71ZWKArz', 'cache', $item);
                $this->assertTrue($set);

                $v = $client->getStorageEndpoint()->getItem('0liREOSzIzzqY2tIiSA71ZWKArz', 'cache', $item->key);

                $this->assertEquals($item->key, $v->key);
                $this->assertEquals($item->value, $v->value);
            }
        }

    }
}
