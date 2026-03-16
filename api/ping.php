<?php
/**
 * Health check – no auth. Use to verify API is reachable.
 * GET api/ping.php
 */

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'message' => 'Tshijuka RDP API is available.',
    'timestamp' => date('c')
]);
