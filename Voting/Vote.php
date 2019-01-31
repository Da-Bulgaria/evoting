<?php
namespace DaBulgaria\DApp\Voting;

class Vote
{
    public $id;
    public $electionId;
    public $receipt;
    public $voterId;
    public $encryptedVote;
    public $decryptedVote;
    public $signature;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->voterId = $data['voter_id'] ?? null;
        $this->electionId = $data['election_id'] ?? null;
        $this->encryptedVote = $data['encrypted_vote'] ?? null;
        $this->signature = $data['signature'] ?? null;
        $this->receipt = $data['receipt'] ?? null;
    }

    /**
     * Checks if the vote was decrypted
     * @return bool
     */
    public function isEncrypted()
    {
        return empty($this->decryptedVote);
    }

    /**
     * Checks if user identifying information is removed from the vote
     * @bool
     */
    public function isAnonymized()
    {
        return empty($this->voterId) && empty($this->receipt) && empty($this->signature);
    }
}
