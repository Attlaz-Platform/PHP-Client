<?php
declare(strict_types=1);

namespace Attlaz;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

class CollectionsTest extends TestCase
{

    private array $endpoints = ['https://gateway.api.attlaz.com',
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


    public function testWriteItem()
    {
        $client = new Client();
        $client->authWithClient($_ENV['api_client_id'], $_ENV['api_client_secret']);
        $client->setDebug(1);


        foreach ($this->endpoints as $endpoint) {
            $client->setEndPoint($endpoint);


//            $data = [
//                'Id' => 'test',
//                'Vendor' => 'vendor',
//                'Price' => 12.0,
//                'Shipping Cost' => 0.5,
//                'In Stock' => true,
//                'Updated' => (new \DateTime('now'))->format(\DateTime::ATOM),
//            ];
//            $v = $client->getCollectionsEndpoint()->addRecord('2qOE5Feg23q3Du52Drs0lF7Vhbh', $data);


            $data = [
                'Id' => '2q1OcaCAmExDtm2f4O0VLpHElI9',
                'Name' => 'Verlichting.be',
                'Domain' => 'www.verlichting.be',
                'Index' => 'https://www.verlichting.be',
            ];
            $v = $client->getCollectionsEndpoint()->addRecord('2qOE58zYFPjKvgamYNzXfpL16Fq', $data);


        }

    }

}
