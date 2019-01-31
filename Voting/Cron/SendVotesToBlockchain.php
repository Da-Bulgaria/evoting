<?php
// Schedule as a cron job using:
// (crontab -l 2>/dev/null; echo "*/5 * * * * php SendVotesToBlockchain.php <appId> <orgId> <secret> <dbHost> <dbUser> <dbPass> <dbName> <dbTable>") | crontab -

$appId = $argv[1];
$orgId = $argv[2];
$secret = $argv[3];
$host = $argv[4];
$user = $argv[5];
$pass = $argv[6];
$db = $argv[7];
$table = $argv[8];

$link = mysqli_connect($host, $user, $pass, $db);
$lastId = file_get_contents("last_id");
if (!$lastId) {
    $lastId = 0;
}
$query = "SELECT id, encrypted_vote FROM " . $table . " WHERE id > " . $lastId;
$result = mysqli_query($link, $query);

// wait for at least 3 votes to have accumulated in order to store them
if ($result->num_rows >= 3) {
    $auth = base64_encode($orgId . ":" . $secret);
    $url = 'https://api.logsentinel.com/api/log/ANONYMOUS/VOTE';
    // output data of each row
    while ($row = $result->fetch_assoc()) {
        if ($row) {
            $data = '{"encrypted_vote": "' . $row['encrypted_vote'] . '"}';
            $options = array(
                'http' => array(
                    'header'  => array("Content-type: application/json", "Authorization: Basic " . $auth , "Application-Id: " . $appId),
                    'method'  => 'POST',
                    'content' => $data
                )
            );
            $context  = stream_context_create($options);
            file_get_contents($url, false, $context);
            $lastId = $row['id'];
            file_put_contents("last_id", $lastId);
        }
    }
}
mysqli_close($link);
