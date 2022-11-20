<?php


namespace ActivityPubLite\Util;

class HttpSignature
{


    private static function plaintext($path, $host, $date, $digest): string
    {
        return sprintf(
            "(request-target): post %s\nhost: %s\ndate: %s\ndigest: %s",
            $path,
            $host,
            $date,
            $digest
        );
    }

    public static function digest(string $data): string
    {
        return sprintf('SHA-256=%s', base64_encode(hash('sha256', $data, true)));
    }

    public static function sign($privkey, $path, $host, $date, $digest): string
    {
        openssl_sign(self::plaintext($path, $host, $date, $digest), $signature, openssl_get_privatekey($privkey), OPENSSL_ALGO_SHA256);

        return $signature;
    }

    public static function verify(string $signature, string $pubkey, string $plaintext): bool
    {
        return openssl_verify($plaintext, base64_decode($signature), $pubkey, OPENSSL_ALGO_SHA256);
    }
}