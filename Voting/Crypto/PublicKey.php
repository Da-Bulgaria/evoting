<?php
namespace DaBulgaria\DApp\Voting\Crypto;

class PublicKey extends Key
{
    public function __construct($rawKey)
    {
        if (strpos($rawKey, '-----BEGIN PUBLIC KEY-----') === false) {
            $this->rawKey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($rawKey, 64, "\n", true)
                . "\n-----END PUBLIC KEY-----\n";
        } else {
            $this->rawKey = $rawKey;
        }
    }
}
