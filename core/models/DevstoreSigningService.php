<?php
namespace Core\Models;

class DevstoreSigningService
{
    public static function signPayload(string $packageType, string $key, string $version, string $sha256): ?string
    {
        $payload = $packageType . '|' . $key . '|' . $version . '|' . $sha256;
        $privateKey = self::loadPrivateKey();
        if ($privateKey === null) {
            return null;
        }
        $signature = '';
        $ok = openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$ok) {
            return null;
        }
        return base64_encode($signature);
    }

    private static function loadPrivateKey()
    {
        $path = __DIR__ . '/../keys/freeflow_private.pem';
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }
        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }
        return openssl_pkey_get_private($content);
    }
}
