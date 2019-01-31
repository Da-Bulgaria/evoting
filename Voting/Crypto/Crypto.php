<?php
namespace DaBulgaria\DApp\Voting\Crypto;

class Crypto
{
    public function encrypt($data, PublicKey $key)
    {
        $success = openssl_public_encrypt($data, $encrypted, $key->getKey());
        if (!$success) {
            throw new \Exception('Failed to encrypt data.');
        }

        return base64_encode($encrypted);
    }

    public function decrypt($data, PrivateKey $key)
    {
        $success = openssl_private_decrypt(base64_decode($data), $decrypted, $key->getKey());
        if (!$success) {
            throw new \Exception('Failed to decrypt data.');
        }

        return $decrypted;
    }

    public function sign($data, PrivateKey $key)
    {
        $success = openssl_sign($data, $signature, $key->getKey(), OPENSSL_ALGO_SHA256);
        if (!$success) {
            throw new \Exception('Failed to sign data.');
        }

        return base64_encode($signature);
    }

    public function verify($data, $signature, PublicKey $key)
    {
        return openssl_verify($data, base64_decode($signature), $key->getKey(), OPENSSL_ALGO_SHA256) === 1;
    }
}
