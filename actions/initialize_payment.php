<?php
// Suppress error output and set JSON header first
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

require_once dirname(dirname(__FILE__)) . '/config/config.php';

// Clear any output that might have been sent
ob_clean();
header('Content-Type: application/json');

try {
// Check if required parameters are provided
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit;
}

$amount = floatval($_POST['amount'] ?? 0);
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$description = htmlspecialchars($_POST['description'] ?? 'Document Retrieval Fee');
$userID = intval($_POST['userID'] ?? 0);

if ($amount <= 0 || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid payment parameters'
    ]);
    exit;
}

// If userID not provided, try to find by email
if ($userID <= 0) {
    try {
        if (isset($conn) && $conn) {
            $userStmt = $conn->prepare("SELECT userID FROM User WHERE userEmail = ?");
            if ($userStmt) {
                $userStmt->bind_param("s", $email);
                $userStmt->execute();
                $userResult = $userStmt->get_result();
                if ($userResult && $userResult->num_rows > 0) {
                    $userID = $userResult->fetch_assoc()['userID'];
                }
                $userStmt->close();
            }
        }
    } catch (Exception $e) {
        error_log("User lookup error: " . $e->getMessage());
    }
}

if ($userID <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'User not found. Please ensure you are logged in.'
    ]);
    exit;
}

// Paystack secret key
$secretKey = 'sk_test_5e1b52bbca0b63f4e196f08d6eabd880a66b8a03';

// Generate unique reference
$reference = 'TDRP_' . time() . '_' . uniqid();

// Convert amount to pesewas (Paystack uses smallest currency unit)
$amountInPesewas = intval($amount * 100);

// Prepare metadata
$metadata = [
    'custom_fields' => [
        [
            'display_name' => 'User ID',
            'variable_name' => 'user_id',
            'value' => $userID
        ],
        [
            'display_name' => 'Description',
            'variable_name' => 'description',
            'value' => $description
        ]
    ]
];

// Initialize cURL
$curl = curl_init();

// Build callback URL using application base URL (works in subfolder like /aa/)
$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
// getBaseUrl() returns something like /aa/
$basePath = function_exists('getBaseUrl') ? getBaseUrl() : (rtrim(dirname(dirname($_SERVER['PHP_SELF'] ?? '')), '/') . '/');
// Ensure basePath starts with a slash and has no double slashes when concatenated
if ($basePath === '/' || $basePath === '') {
    $basePath = '/';
}
$callbackUrl = $scheme . $host . rtrim($basePath, '/') . '/actions/verify_payment.php?reference=' . urlencode($reference);

// Set cURL options
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.paystack.co/transaction/initialize",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'email' => $email,
        'amount' => $amountInPesewas,
        'reference' => $reference,
        'metadata' => $metadata,
        'callback_url' => $callbackUrl
    ]),
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . $secretKey,
        "Content-Type: application/json"
    ]
]);

// Execute request
$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    echo json_encode([
        'status' => 'error',
        'message' => 'cURL Error: ' . $err
    ]);
    exit;
}

// Decode response
$result = json_decode($response, true);

if ($result['status'] === true && isset($result['data'])) {
    // Get Paystack's reference (they may have modified it)
    $paystackReference = $result['data']['reference'] ?? $reference;
    
    // Save payment record with pending status
    try {
        if (isset($conn) && $conn) {
            $insertStmt = $conn->prepare("
                INSERT INTO PaystackPayments 
                (user_id, amount, reference, status, currency, description, created_at) 
                VALUES (?, ?, ?, 'pending', 'GHS', ?, NOW())
            ");
            if ($insertStmt) {
                $insertStmt->bind_param("idss", $userID, $amount, $paystackReference, $description);
                $insertStmt->execute();
                $insertStmt->close();
            }
        }
    } catch (Exception $e) {
        // Log error but continue with payment initialization
        error_log("Payment record save error: " . $e->getMessage());
    }
    
    echo json_encode([
        'status' => 'success',
        'authorization_url' => $result['data']['authorization_url'] ?? '',
        'access_code' => $result['data']['access_code'] ?? '',
        'reference' => $paystackReference
    ]);
} else {
    $errorMsg = $result['message'] ?? 'Failed to initialize payment';
    if (isset($result['data']['message'])) {
        $errorMsg = $result['data']['message'];
    }
    echo json_encode([
        'status' => 'error',
        'message' => $errorMsg,
        'debug' => $result // Include full response for debugging
    ]);
}

} catch (Exception $e) {
    // Ensure we return JSON even on unexpected errors
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
    exit;
}
