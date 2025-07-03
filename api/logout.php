<?php
header('Content-Type: application/json');
require_once '../config/cors.php';

session_start();
session_destroy();

echo json_encode(['message' => 'Logged out successfully']);