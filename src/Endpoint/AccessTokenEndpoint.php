<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;


class AccessTokenEndpoint extends Endpoint
{
    public function get(string $accessToken): array|null
    {
        $uri = '/access_tokens/' . $accessToken;


        $result = $this->requestObject($uri, null, 'GET');

        if ($result === null) {
            return null;
        }

//        array(8) {
//        ["id"]=>
//  int(37091358)
//  ["created"]=>
//  string(24) "2023-11-29T12:11:29.000Z"
//        ["expires"]=>
//  string(24) "2023-11-29T16:11:29.000Z"
//        ["scope"]=>
//  array(1) {
//            [0]=>
//    string(3) "all"
//  }
//  ["client"]=>
//  string(27) "2QtScvpdO88qS0KlSgBwHXqHxmP"
//        ["user"]=>
//  string(2) "25"
//        ["ip"]=>
//  string(12) "93.96.54.241"
//        ["access_token"]=>
//  string(64) "97799b5ce9c6159660e7d1fb5700215a45668fc623d951b4f4d7e6a8ceb3f7cd"
//}
        return [
            'access_token' => $result['access_token'],
            'created' => $result['created'],
            'expires' => $result['expires'],
            'name' => isset($result['name']) ? $result['name'] : '',
        ];
    }


    public function revoke(string $accessToken): bool
    {
        $uri = '/access_tokens/' . $accessToken;


        $result = $this->requestObject($uri, null, 'DELETE');

        if ($result === null) {
            throw new \Exception('Invalid result');
        }
        return $result['deleted'];
    }

}
