<?php
header('Content-Type: application/json');
file_put_contents(
    '../logs/errors.log', 
    date('Y-m-d H:i:s') . "\n" . 
    json_encode($_POST, JSON_PRETTY_PRINT) . "\n\n",
    FILE_APPEND
);
echo json_encode(['success' => true]);