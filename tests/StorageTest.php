<?php

namespace Attlaz;

use Attlaz\Adapter\Magento2\Helper\Magento2AttributeValueHelper;
use Attlaz\Adapter\Magento2\Model\Magento2Attribute;
use Attlaz\Adapter\Magento2\Model\Magento2AttributeType;
use Attlaz\Model\StorageItem;
use PHPUnit\Framework\TestCase;

class StorageTest extends TestCase
{
    public function testWriteItem()
    {
        $client = new \Attlaz\Client('6as&01LW!iVe!wO7Guv%5#MlfZ2SJgSG', '#zqtn*4IKcx7iNM4bNvc$XU@H27prch8');
//$client->setEndPoint('https://api2.attlaz.com');
        $client->setEndPoint('https://api.attlaz.com/beta/');
//$client = new \Attlaz\Client('http://10.0.75.1:8080/', '6as&01LW!iVe!wO7Guv%5#MlfZ2SJgSG', '#zqtn*4IKcx7iNM4bNvc$XU@H27prch8');
        $client->enableDebug();

        $item = new StorageItem();
        $item->key = 'randomkey';
        $item->value = 'randomvalue';
        $set = $client->getStorageEndpoint()->setItem(61, 'cache', $item);
        $this->assertTrue($set);

        $v = $client->getStorageEndpoint()->getItem(61, 'cache', $item->key);

        \var_dump($v->value);

        $this->assertEquals($item->value, $v->value);

    }
}
