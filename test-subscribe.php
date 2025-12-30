<?php
// Test the subscribe API

$_POST['email'] = 'test@example.com';
$_SERVER['REQUEST_METHOD'] = 'POST';

// Suppress headers to see output
ob_start();

include 'api/subscribe-email.php';

$output = ob_get_clean();
echo $output;
?>
