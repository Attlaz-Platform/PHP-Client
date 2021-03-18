<?php
declare(strict_types=1);

namespace Attlaz\Helper;

use League\OAuth2\Client\Token\AccessToken;

class TokenStorage
{
    public static function loadAccessToken(string $clientId, string $clientSecret): ?AccessToken
    {
        $data = self::getData($clientId, $clientSecret);

        $encrypt_method = $data['encrypt_method'];
        $key = $data['key'];
        $iv = $data['iv'];
        $file = $data['file'];

        if (file_exists($file) && is_readable($file)) {
            $content = openssl_decrypt(base64_decode(\file_get_contents($file)), $encrypt_method, $key, 0, $iv);
            $accessToken = unserialize($content);

            if (!is_null($accessToken) && is_a($accessToken, AccessToken::class) && !$accessToken->hasExpired()) {
                return $accessToken;
            }
        }

        return null;
    }

    private static function getData(string $clientId, string $clientSecret): array
    {
        return [
            'encrypt_method' => "AES-256-CBC",
            'key'            => hash('sha256', $clientId . '-' . $clientSecret),
            'iv'             => substr(hash('sha256', $clientSecret), 0, 16),
            'file'           => '_token_' . \hash('sha512', $clientId . $clientSecret),
        ];
    }

    public static function saveAccessToken(AccessToken $accessToken, string $clientId, string $clientSecret)
    {
        $data = self::getData($clientId, $clientSecret);

        $encrypt_method = $data['encrypt_method'];
        $key = $data['key'];
        $iv = $data['iv'];
        $file = $data['file'];

        $content = base64_encode(openssl_encrypt(\serialize($accessToken), $encrypt_method, $key, 0, $iv));

        file_put_contents($file, $content);
    }
}
