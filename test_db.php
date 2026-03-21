<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = '127.0.0.1';
$port = 3307;
$user = 'root';
$pass = '';

echo "Attempting to connect to $host:$port as $user...\n";

$conn = new mysqli($host, $user, $pass, null, $port);

if ($conn->connect_error) {
    echo "CONNECTION FAILED: " . $conn->connect_error . " (" . $conn->connect_errno . ")\n";
} else {
    echo "SUCCESS: Connected to the database on port $port!\n";
    echo "Server info: " . $conn->server_info . "\n";
    $conn->close();
}
?>
