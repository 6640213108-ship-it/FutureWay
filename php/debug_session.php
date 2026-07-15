<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'session'    => $_SESSION,
    'session_id' => session_id(),
    'cookies'    => $_COOKIE,
]);
?>
