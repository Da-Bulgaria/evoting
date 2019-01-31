<?php
namespace DaBulgaria\DApp\Voting\Crypto;

abstract class Key
{
    protected $rawKey = '';

    public function getKey()
    {
        return $this->rawKey;
    }

    public function __toString()
    {
        return $this->getKey();
    }
}
