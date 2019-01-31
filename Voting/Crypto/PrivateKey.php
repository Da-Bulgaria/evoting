<?php
namespace DaBulgaria\DApp\Voting\Crypto;

class PrivateKey extends Key
{
    public function __construct($rawKey)
    {
        if (strpos($rawKey, '-----BEGIN RSA PRIVATE KEY-----') === false) {
            $this->rawKey = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($rawKey, 64, "\n", true)
                . "\n-----END RSA PRIVATE KEY-----\n";
        } else {
            $this->rawKey = $rawKey;
        }
    }
}
