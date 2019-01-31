<?php
namespace DaBulgaria\DApp\Voting;

use DaBulgaria\DApp\Voting\Crypto\Crypto;
use DaBulgaria\DApp\Voting\Crypto\PublicKey;
use DaBulgaria\DApp\Voting\Crypto\PrivateKey;

class VoteManager
{
    /** @var \wpdb */
    private $db;

    /** @var Crypto */
    private $crypto;

    public function __construct(\wpdb $db, Crypto $crypto)
    {
        $this->db = $db;
        $this->crypto = $crypto;
    }

    /**
     * Appends a new user vote to the votes table for the currently running election.
     * The vote signature is verified using the voter public key.
     *
     * @return string A receipt identifying the vote
     */
    public function castVote(Vote $vote, PublicKey $voterPublicKey)
    {
        // First try to verify the signature. The signature is placed using the private key of the voter
        // and validated with their public key, which is stored in the database after the key is generated
        if (!$this->crypto->verify($vote->encryptedVote, $vote->signature, $voterPublicKey)) {
            throw new \Exception('Vote signature could not be verified.');
        }

        // add election ID to vote
        $election = $this->getCurrentElection();
        $vote->electionId = $election->ID;

        // Generate a random receipt
        $receipt = substr($vote->encryptedVote, 0, 3) . $this->generateRandomString(3);

        // Always insert a new vote. When counting, only the last one will be counted.
        // Do not store the exact time of the vote as it is additional identifying information.
        // The autoincrement ID will be used to determine whic is the last vote
        $this->db->query($this->db->prepare(
            "INSERT INTO {$this->db->base_prefix}dapp_votes (voter_id, election_id, encrypted_vote, signature, receipt) VALUES(%d, %s, %s, %s, %s)",
            $vote->voterId,
            $vote->electionId,
            $vote->encryptedVote,
            $vote->signature,
            $receipt
        ));

        return $receipt;
    }

    /**
     * Loads all votes for the currently running election and removes user identifying data.
     * The user ID is moved to another table to keep a record of election participation.
     *
     * @bool Whether the operation was successful
     */
    public function anonymizeVotes()
    {
        $election = $this->getCurrentElection();

        // Before anonymization, verify the signature of each vote. If the signature doesn't match, it might mean the vote was tampered with
        $electionID = $election->ID;
        $votes = $this->db->get_results("SELECT * FROM {$this->db->base_prefix}dapp_votes WHERE election_id = $electionID;", ARRAY_A);
        foreach ($votes as $voteData) {
            $vote = new Vote($voteData);

            $voterPublicKey = $this->getVoterPublicKey($vote->voterId);
            if (!$this->crypto->verify($vote->encryptedVote, $vote->signature, $voterPublicKey)) {
                throw new \Exception('Vote signature could not be verified.');
            }
        }

        // Transfer voter and election IDs to other table
        $this->db->query("INSERT INTO {$this->db->base_prefix}dapp_past_voters (voter_id, election_id) SELECT voter_id, election_id FROM {$this->db->base_prefix}dapp_votes WHERE election_id = {$election->ID};");

        // Anonymize all votes by removing any identifying information
        $this->db->query("UPDATE {$this->db->base_prefix}dapp_votes SET voter_id = null, signature = null, receipt = null");

        return true;
    }

    /**
     * Loads all votes for the currently running election, decrypts them and moves them to another table
     *
     * @return bool Whether the operation was successful
     */
    public function decryptVotes(PrivateKey $key)
    {
        $election = $this->getCurrentElection();
        $electionID = $election->ID;

        $votes = $this->db->get_results("SELECT * FROM {$this->db->base_prefix}dapp_votes WHERE election_id = $electionID;", ARRAY_A);

        // shuffling the votes so that voter identity cannot be guessed from the order
        shuffle($votes);

        foreach ($votes as $voteData) {
            $vote = new Vote($voteData);
            if (!$vote->isAnonymized()) {
                throw new \Exception('Cannot decrypt votes that have not been anonymized');
            }

            $vote->decryptedVote = $this->crypto->decrypt($vote->encryptedVote, $key);

            // we need to use prepared statement to protect from fake votes that contain SQL injection attacks
            $this->db->query($this->db->prepare(
                "INSERT INTO {$this->db->base_prefix}dapp_decrypted_votes (vote, encrypted_vote, election_id) VALUES (%s, %s, %d);",
                $vote->decryptedVote,
                $vote->encryptedVote,
                $electionID
            ));
        }

        // delete all votes from the original table so that the order of insertion cannot be used to reveal voter identity
        $this->db->query("DELETE FROM {$this->db->base_prefix}dapp_votes WHERE election_id = $electionID");

        return true;
    }

    /**
     * Finds a vote by voter ID
     *
     * @return Vote|null
     */
    public function findVoteByUserId($id)
    {
        $election = $this->getCurrentElection();
        $electionID = $election->ID;

        $existingVote = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->base_prefix}dapp_votes WHERE election_id = $electionID AND voter_id = '%d';",
                $id
            ),
            ARRAY_A
        );

        if (!empty($existingVote)) {
            $existingVote = new Vote($existingVote);
        }

        return $existingVote;
    }

    /**
     * Finds a vote by receipt
     *
     * @return Vote|null
     */
    public function findVoteByReceipt($receipt)
    {
        $election = $this->getCurrentElection();
        $electionID = $election->ID;

        $existingVote = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->base_prefix}dapp_votes WHERE election_id = $electionID AND receipt = %s;",
                $receipt
            ),
            ARRAY_A
        );

        if (!empty($existingVote)) {
            $existingVote = new Vote($existingVote);
        }

        return $existingVote;
    }

    /**
     * Gets the currently running election post
     *
     * @throws \Exception if no current election post found
     * @return \WP_Post
     */
    private function getCurrentElection()
    {
        $electionID = (int) \get_option('dapp_current_elections');
        $election = \get_post($electionID);

        if ($electionID === 0 || !is_a($election, '\WP_Post')) {
            throw new \Exception('No current election found.');
        }

        return $election;
    }

    /*
     * Finds the public key of a voter
     * @return PublicKey|null
     */
    public function getVoterPublicKey($voterId)
    {
        $voterPublicKeyRaw = \get_user_meta($voterId, 'public_key', true);
        if (empty($voterPublicKeyRaw)) {
            return null;
        } else {
            return new PublicKey($voterPublicKeyRaw);
        }
    }

    /**
     * Helper method to generate alphanumeric pseudorandom strings
     *
     * @return string
     */
    private function generateRandomString($length = 10)
    {
        $characters = '23456789abcdefghjkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
