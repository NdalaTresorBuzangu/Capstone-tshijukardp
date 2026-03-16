<?php
/**
 * Cross-border payment flow: Admin receives payment -> notify agent -> agent pays institution via Momo -> Admin compensates agent (bank).
 * Uses global $conn from config.
 */

if (!isset($conn)) {
    include_once __DIR__ . '/../config/config.php';
}

// ------------------ COUNTRY AGENTS ------------------

function getCountryAgents($activeOnly = false) {
    global $conn;
    $sql = "SELECT * FROM CountryAgents";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY country_code, agent_name";
    $result = @$conn->query($sql);
    if (!$result) return [];
    $list = [];
    while ($row = $result->fetch_assoc()) $list[] = $row;
    return $list;
}

function getCountryAgentsByCountry($countryCode) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM CountryAgents WHERE country_code = ? AND is_active = 1 ORDER BY agent_name");
    $stmt->bind_param("s", $countryCode);
    $stmt->execute();
    $res = $stmt->get_result();
    $list = [];
    while ($row = $res->fetch_assoc()) $list[] = $row;
    $stmt->close();
    return $list;
}

function addCountryAgent($countryCode, $agentName, $contactPhone, $contactEmail, $momoNumber, $momoProvider, $bankName, $bankAccountNumber, $bankAccountName) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO CountryAgents (country_code, agent_name, contact_phone, contact_email, momo_number, momo_provider, bank_name, bank_account_number, bank_account_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $countryCode, $agentName, $contactPhone, $contactEmail, $momoNumber, $momoProvider, $bankName, $bankAccountNumber, $bankAccountName);
    $stmt->execute();
    $id = $conn->insert_id;
    $stmt->close();
    return $id;
}

function updateCountryAgent($agentId, $countryCode, $agentName, $contactPhone, $contactEmail, $momoNumber, $momoProvider, $bankName, $bankAccountNumber, $bankAccountName, $isActive) {
    global $conn;
    $stmt = $conn->prepare("UPDATE CountryAgents SET country_code=?, agent_name=?, contact_phone=?, contact_email=?, momo_number=?, momo_provider=?, bank_name=?, bank_account_number=?, bank_account_name=?, is_active=? WHERE agent_id=?");
    $stmt->bind_param("sssssssssii", $countryCode, $agentName, $contactPhone, $contactEmail, $momoNumber, $momoProvider, $bankName, $bankAccountNumber, $bankAccountName, $isActive, $agentId);
    $stmt->execute();
    $stmt->close();
}

function deleteCountryAgent($agentId) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM CountryAgents WHERE agent_id = ?");
    $stmt->bind_param("i", $agentId);
    $stmt->execute();
    $stmt->close();
}

function getAgentById($agentId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM CountryAgents WHERE agent_id = ?");
    $stmt->bind_param("i", $agentId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

// ------------------ PAYMENTS RECEIVED (for admin) ------------------

/**
 * List successful Paystack payments with optional document/issuer info and current flow status.
 */
function getPaymentsReceivedForAdmin() {
    global $conn;
    $sql = "SELECT p.payment_id, p.user_id, p.amount, p.currency, p.reference, p.description, p.document_id, p.verified_at,
            u.userName AS seeker_name, u.userEmail AS seeker_email,
            d.documentIssuerID, issuer.userName AS issuer_name
            FROM PaystackPayments p
            LEFT JOIN User u ON p.user_id = u.userID
            LEFT JOIN Document d ON p.document_id = d.documentID
            LEFT JOIN User issuer ON d.documentIssuerID = issuer.userID
            WHERE p.status = 'success'
            ORDER BY p.verified_at DESC";
    $result = @$conn->query($sql);
    if (!$result) return [];
    $list = [];
    while ($row = $result->fetch_assoc()) {
        $row['flow'] = getFlowByPaymentId($row['payment_id']);
        $list[] = $row;
    }
    return $list;
}

function getFlowByPaymentId($paymentId) {
    global $conn;
    $stmt = $conn->prepare("SELECT f.*, a.agent_name, a.country_code, a.momo_number, a.bank_name, a.bank_account_number, a.bank_account_name FROM PaymentAgentFlow f JOIN CountryAgents a ON f.agent_id = a.agent_id WHERE f.payment_id = ? ORDER BY f.id DESC LIMIT 1");
    $stmt->bind_param("i", $paymentId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

function assignAgentToPayment($paymentId, $agentId, $institutionCountry = null, $amountLocal = null, $notes = null) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO PaymentAgentFlow (payment_id, agent_id, institution_country, status, amount_local, notes, assigned_at) VALUES (?, ?, ?, 'agent_notified', ?, ?, NOW())");
    $stmt->bind_param("iisss", $paymentId, $agentId, $institutionCountry, $amountLocal, $notes);
    $stmt->execute();
    $stmt->close();
}

function markAgentPaidMomo($flowId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE PaymentAgentFlow SET status = 'agent_paid_momo', agent_paid_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $flowId);
    $stmt->execute();
    $stmt->close();
    // Mark linked document as "payment confirmed" so the institution sees "ready to send document"
    $stmt = $conn->prepare("SELECT payment_id FROM PaymentAgentFlow WHERE id = ?");
    $stmt->bind_param("i", $flowId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row && !empty($row['payment_id'])) {
        $pstmt = $conn->prepare("SELECT document_id FROM PaystackPayments WHERE payment_id = ? AND document_id IS NOT NULL AND document_id != ''");
        $pstmt->bind_param("i", $row['payment_id']);
        $pstmt->execute();
        $prow = $pstmt->get_result()->fetch_assoc();
        $pstmt->close();
        if ($prow && !empty($prow['document_id'])) {
            $docId = $prow['document_id'];
            $alterStmt = @$conn->prepare("UPDATE Document SET payment_confirmed_at = NOW() WHERE documentID = ?");
            if ($alterStmt) {
                $alterStmt->bind_param("s", $docId);
                $alterStmt->execute();
                $alterStmt->close();
            }
        }
    }
}

function markCompensationSent($flowId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE PaymentAgentFlow SET status = 'compensation_sent', compensation_sent_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $flowId);
    $stmt->execute();
    $stmt->close();
}

function getPaymentById($paymentId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM PaystackPayments WHERE payment_id = ?");
    $stmt->bind_param("i", $paymentId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}
