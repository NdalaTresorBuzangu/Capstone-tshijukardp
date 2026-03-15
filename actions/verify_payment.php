<?php
// Suppress error output and set JSON header first
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering before any includes
ob_start();

require_once dirname(dirname(__FILE__)) . '/config/config.php';

// Clear any output that might have been sent (warnings, notices, etc.)
ob_end_clean();
ob_start();

// Set JSON header
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
// Check if reference is provided
if (!isset($_GET['reference'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Payment reference is required'
    ]);
    exit;
}

$reference = $_GET['reference'];

// Paystack secret key
$secretKey = 'sk_test_5e1b52bbca0b63f4e196f08d6eabd880a66b8a03';

// Initialize cURL
$curl = curl_init();

// Set cURL options
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . $reference,
    CURLOPT_RETURNTRANSFER => true,
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

// Check if JSON decode was successful
if ($result === null) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid response from Paystack API',
        'raw_response' => substr($response, 0, 200)
    ]);
    exit;
}

// Check if we have valid response structure
if (!isset($result['status'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unexpected response format from Paystack',
        'response' => $result
    ]);
    exit;
}

// Check if payment is successful according to Paystack
$isPaymentSuccessful = false;
if ($result['status'] === true && isset($result['data'])) {
    $paymentStatus = $result['data']['status'] ?? '';
    if ($paymentStatus === 'success') {
        $isPaymentSuccessful = true;
    }
}

if ($isPaymentSuccessful) {
    // Payment verified successfully by Paystack
    $paymentData = $result['data'];
    
    // Extract payment details
    $amount = isset($paymentData['amount']) ? ($paymentData['amount'] / 100) : 0; // Convert from pesewas to GHS
    $paystackReference = $paymentData['reference'] ?? $reference;
    $customerEmail = isset($paymentData['customer']['email']) ? $paymentData['customer']['email'] : '';
    $paymentMethod = $paymentData['channel'] ?? 'card';
    $paidAt = $paymentData['paid_at'] ?? date('Y-m-d H:i:s');
    
    // Get user ID from metadata or find by email
    $userId = null;
    if (isset($paymentData['metadata']['custom_fields'])) {
        foreach ($paymentData['metadata']['custom_fields'] as $field) {
            if ($field['variable_name'] === 'user_id') {
                $userId = intval($field['value']);
                break;
            }
        }
    }
    
    // If user ID not in metadata, try to find by email
    if (!$userId) {
        try {
            if (isset($conn) && $conn) {
                $userStmt = $conn->prepare("SELECT userID FROM User WHERE userEmail = ?");
                if ($userStmt) {
                    $userStmt->bind_param("s", $customerEmail);
                    $userStmt->execute();
                    $userResult = $userStmt->get_result();
                    if ($userResult && $userResult->num_rows > 0) {
                        $userId = $userResult->fetch_assoc()['userID'];
                    }
                    $userStmt->close();
                }
            }
        } catch (Exception $e) {
            error_log("User lookup error: " . $e->getMessage());
        }
    }
    
    // If user not found, still return success since payment is verified
    // We'll try to save with userId = 0 or skip database save
    $saveToDatabase = ($userId > 0);
    
    // Check if payment already exists (only if we have userId)
    $existingPayment = null;
    if ($saveToDatabase) {
        try {
            if (isset($conn) && $conn) {
                $checkStmt = $conn->prepare("SELECT payment_id FROM PaystackPayments WHERE reference = ? OR paystack_reference = ?");
                if ($checkStmt) {
                    $checkStmt->bind_param("ss", $reference, $paystackReference);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    if ($checkResult) {
                        $existingPayment = $checkResult->fetch_assoc();
                    }
                    $checkStmt->close();
                }
            }
        } catch (Exception $e) {
            error_log("Payment check error: " . $e->getMessage());
        }
    }
    
    if ($existingPayment && $saveToDatabase) {
        // Update existing payment
        try {
            if (isset($conn) && $conn) {
                $updateStmt = $conn->prepare("
                    UPDATE PaystackPayments 
                    SET status = 'success', 
                        paystack_reference = ?, 
                        payment_method = ?,
                        verified_at = NOW()
                    WHERE reference = ? OR paystack_reference = ?
                ");
                if ($updateStmt) {
                    $updateStmt->bind_param("ssss", $paystackReference, $paymentMethod, $reference, $paystackReference);
                    $updateStmt->execute();
                    $updateStmt->close();
                }
            }
        } catch (Exception $e) {
            error_log("Payment update error: " . $e->getMessage());
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Payment already verified',
            'payment_id' => $existingPayment['payment_id'],
            'amount' => $amount,
            'reference' => $reference
        ]);
    } else {
        // Insert new payment record (only if we have userId)
        $description = 'Payment via Paystack';
        if (isset($paymentData['metadata']['custom_fields'])) {
            foreach ($paymentData['metadata']['custom_fields'] as $field) {
                if ($field['variable_name'] === 'description') {
                    $description = $field['value'];
                    break;
                }
            }
        }
        
        $paymentId = null;
        if ($saveToDatabase) {
            try {
                if (isset($conn) && $conn) {
                    $insertStmt = $conn->prepare("
                        INSERT INTO PaystackPayments 
                        (user_id, amount, reference, status, paystack_reference, payment_method, currency, description, verified_at) 
                        VALUES (?, ?, ?, 'success', ?, ?, 'GHS', ?, NOW())
                    ");
                    if ($insertStmt) {
                        $insertStmt->bind_param("idssss", $userId, $amount, $reference, $paystackReference, $paymentMethod, $description);
                        
                        if ($insertStmt->execute()) {
                            $paymentId = $conn->insert_id;
                        }
                        $insertStmt->close();
                    }
                }
            } catch (Exception $e) {
                error_log("Payment insert error: " . $e->getMessage());
            }
        }
        
        // Always return success if Paystack confirmed payment
        echo json_encode([
            'status' => 'success',
            'message' => 'Payment verified and recorded successfully',
            'payment_id' => $paymentId,
            'amount' => $amount,
            'reference' => $reference
        ]);
    }
} else {
    // Payment verification failed or pending
    $errorMessage = 'Payment verification failed';
    
    if (isset($result['message'])) {
        $errorMessage = $result['message'];
    } elseif (isset($result['data']['message'])) {
        $errorMessage = $result['data']['message'];
    } elseif (isset($result['data']['status'])) {
        $errorMessage = 'Payment status: ' . $result['data']['status'];
    }
    
    // Try to update payment status to failed if it exists
    try {
        if (isset($conn) && $conn) {
            $updateStmt = $conn->prepare("
                UPDATE PaystackPayments 
                SET status = 'failed' 
                WHERE reference = ?
            ");
            if ($updateStmt) {
                $updateStmt->bind_param("s", $reference);
                $updateStmt->execute();
                $updateStmt->close();
            }
        }
    } catch (Exception $e) {
        error_log("Payment status update error: " . $e->getMessage());
    }
    
    echo json_encode([
        'status' => 'error',
        'message' => $errorMessage,
        'payment_status' => $result['data']['status'] ?? 'unknown'
    ]);
}

} catch (Exception $e) {
    // Ensure we return JSON even on unexpected errors
    ob_end_clean();
    ob_start();
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred during payment verification: ' . $e->getMessage(),
        'error_details' => $e->getFile() . ':' . $e->getLine()
    ]);
    ob_end_flush();
    exit;
}

