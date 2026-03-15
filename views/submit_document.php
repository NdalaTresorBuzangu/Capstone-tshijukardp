<?php
include __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'core.php';
include __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
$showLogout = true;
include __DIR__ . DIRECTORY_SEPARATOR . 'nav.php';
isLogin();

// Get current user ID from session
$currentUserID = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// Fetch subscribed document issuing institutions
$issuers = [];
$result = $conn->query("
    SELECT s.subscribeID, s.documentIssuerName, u.userID 
    FROM Subscribe s 
    JOIN User u ON s.userID = u.userID 
    WHERE u.userRole = 'Document Issuer'
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $issuers[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Document - Tshijuka RDP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/submit-document.css">
    <link rel="stylesheet" href="../assets/nav.css">
    <script src="https://js.paystack.co/v1/inline.js"></script>
</head>
<body>
<header class="container mt-3">
    <h1>Submit a Request to Retrieve Your Document</h1>
</header>

<main class="container mt-4">
    <form id="documentForm" enctype="multipart/form-data">
        <!-- Student Info -->
        <div class="mb-3">
            <label for="userName" class="form-label">Your Name:</label>
            <input type="text" id="userName" name="userName" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="userEmail" class="form-label">Your Email:</label>
            <input type="email" id="userEmail" name="userEmail" class="form-control" required>
        </div>

        <!-- Institution Select (same for all documents) -->
        <div class="mb-3">
            <label for="documentIssuerID" class="form-label">Select Document Issuing Institution:</label>
            <select id="documentIssuerID" name="documentIssuerID" class="form-select" required>
                <option value="">-- Select Institution --</option>
                <?php foreach ($issuers as $issuer): ?>
                    <option value="<?= htmlspecialchars($issuer['userID']) ?>">
                        <?= htmlspecialchars($issuer['documentIssuerName']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Documents to request (multiple) -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Documents to request</h5>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addDocumentRow">+ Add another document</button>
            </div>
            <p class="text-muted small mb-2">Add one or more documents. Each will get its own tracking ID.</p>
            <div id="documentRows">
                <div class="document-row card card-body mb-3 border">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-2">
                            <label class="form-label small">Document Type</label>
                            <select name="documentType[0]" class="form-select form-select-sm" required>
                                <option value="1">Identity</option>
                                <option value="2">Educational</option>
                                <option value="3">History</option>
                                <option value="4">Contract</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label small">Location</label>
                            <input type="text" name="location[0]" class="form-control form-control-sm" placeholder="Location" required>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label small">Description</label>
                            <input type="text" name="description[0]" class="form-control form-control-sm" placeholder="Description" required>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label small">File(s) (optional)</label>
                            <input type="file" name="image[0][]" class="form-control form-control-sm file-multi" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,image/*,application/pdf" multiple>
                            <small class="text-muted">Click to select one, then Shift+click to select a range (like Google Drive).</small>
                        </div>
                        <div class="col-md-1 mb-2">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-row" title="Remove this row" style="visibility: hidden;">×</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Saved Document IDs Section -->
        <div class="mb-4" id="savedIDsSection" style="display: none;">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <strong>📋 Your Saved Document IDs</strong>
                </div>
                <div class="card-body" id="savedIDsList">
                    <!-- Document IDs will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Optional Payment Section -->
        <div class="mb-4">
            <div class="card border-primary">
                <div class="card-header bg-light">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="enablePayment" name="enablePayment">
                        <label class="form-check-label" for="enablePayment">
                            <strong>Pay Retrieval Fee (Optional)</strong>
                        </label>
                    </div>
                </div>
                <div class="card-body" id="paymentSection" style="display: none;">
                    <div class="mb-3">
                        <label for="paymentAmount" class="form-label">Retrieval Fee Amount (GHS):</label>
                        <input type="number" id="paymentAmount" name="paymentAmount" class="form-control" 
                               min="1" step="0.01" value="50.00" placeholder="50.00">
                        <small class="form-text text-muted">You can pay the retrieval fee now to expedite your document request.</small>
                    </div>
                    <div class="text-center mb-3">
                        <button type="button" class="btn btn-success btn-lg" id="payNowBtn">
                            <i class="bi bi-credit-card"></i> Pay Now
                        </button>
                    </div>
                    <div id="paymentStatus" class="alert" style="display: none;"></div>
                    <input type="hidden" id="paymentReference" name="paymentReference" value="">
                </div>
            </div>
        </div>

        <div class="mb-4 consent-block">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="accept_terms_doc" name="accept_terms_doc" value="1" required>
                <label class="form-check-label" for="accept_terms_doc">
                    I have read and accept the <a href="../index.php?controller=Page&action=terms" target="_blank" rel="noopener">Terms of Service</a> and <a href="../index.php?controller=Page&action=privacy" target="_blank" rel="noopener">Privacy Policy</a>. I consent to the processing of my data for this document request in line with data protection standards (GDPR / Ghana Data Protection).
                </label>
            </div>
            <div id="consentDocError" class="invalid-feedback d-block" style="display: none;"></div>
        </div>
        <div class="text-center">
            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">Submit Document</button>
        </div>
    </form>
</main>

<script>
// Paystack public key
const PAYSTACK_PUBLIC_KEY = 'pk_test_11c4dffd1bfb8c9efb25eceb0b6132aa85761747';
const currentUserID = <?= $currentUserID ?>;

// Load and display saved document IDs
function loadSavedDocumentIDs() {
    const savedIDs = JSON.parse(localStorage.getItem('documentIDs') || '[]');
    const savedIDsSection = document.getElementById('savedIDsSection');
    const savedIDsList = document.getElementById('savedIDsList');
    
    if (savedIDs.length > 0) {
        savedIDsSection.style.display = 'block';
        savedIDsList.innerHTML = savedIDs.map((item, index) => {
            const date = new Date(item.date).toLocaleDateString();
            return `
                <div class="d-flex justify-content-between align-items-center mb-2 p-2" style="background: #f8f9fa; border-radius: 5px;">
                    <div>
                        <strong>${item.id}</strong>
                        <br><small class="text-muted">${date} - ${item.description || 'No description'}</small>
                    </div>
                    <div>
                        <button onclick="copyDocumentID('${item.id}')" class="btn btn-sm btn-primary me-1">Copy</button>
                        <a href="index.php?controller=Seeker&action=progress" class="btn btn-sm btn-success">Track</a>
                    </div>
                </div>
            `;
        }).join('');
    } else {
        savedIDsSection.style.display = 'none';
    }
}

// Load saved IDs on page load
loadSavedDocumentIDs();

// Add another document row
function reindexDocumentRows() {
    var rows = document.querySelectorAll('.document-row');
    rows.forEach(function(row, idx) {
        var fileInput = row.querySelector('input.file-multi');
        var sel = row.querySelector('select[name^="documentType"]');
        var loc = row.querySelector('input[name^="location"]');
        var desc = row.querySelector('input[name^="description"]');
        if (fileInput) fileInput.setAttribute('name', 'image[' + idx + '][]');
        if (sel) sel.setAttribute('name', 'documentType[' + idx + ']');
        if (loc) loc.setAttribute('name', 'location[' + idx + ']');
        if (desc) desc.setAttribute('name', 'description[' + idx + ']');
    });
}
document.getElementById('addDocumentRow').addEventListener('click', function() {
    var template = document.querySelector('.document-row').cloneNode(true);
    template.querySelectorAll('input[type="text"], input[type="file"], select').forEach(function(el) { el.value = ''; });
    template.querySelector('.remove-row').style.visibility = 'visible';
    document.getElementById('documentRows').appendChild(template);
    reindexDocumentRows();
});

// Remove document row (delegate)
document.getElementById('documentRows').addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-row')) {
        var rows = document.querySelectorAll('.document-row');
        if (rows.length > 1) {
            e.target.closest('.document-row').remove();
            reindexDocumentRows();
        }
    }
});

function clearForm() {
    document.getElementById('documentForm').reset();
    document.getElementById('paymentReference').value = '';
    document.getElementById('enablePayment').checked = false;
    document.getElementById('paymentSection').style.display = 'none';
    var rows = document.querySelectorAll('.document-row');
    for (var i = rows.length - 1; i > 0; i--) rows[i].remove();
    reindexDocumentRows();
    var sb = document.getElementById('submitBtn');
    sb.disabled = false;
    sb.textContent = 'Submit Document';
    sb.classList.remove('btn-success');
    sb.classList.add('btn-primary');
    document.getElementById('paymentStatus').style.display = 'none';
    loadSavedDocumentIDs();
}

// Toggle payment section visibility
document.getElementById('enablePayment').addEventListener('change', function() {
    const paymentSection = document.getElementById('paymentSection');
    paymentSection.style.display = this.checked ? 'block' : 'none';
    if (!this.checked) {
        document.getElementById('paymentStatus').style.display = 'none';
        document.getElementById('paymentReference').value = '';
    }
});

// Handle "Pay Now" button click
document.getElementById('payNowBtn').addEventListener('click', async function() {
    const paymentAmount = parseFloat(document.getElementById('paymentAmount').value);
    const userEmail = document.getElementById('userEmail').value;
    const paymentStatus = document.getElementById('paymentStatus');
    const payNowBtn = document.getElementById('payNowBtn');
    
    // Validate email and amount
    if (!userEmail || !userEmail.includes('@')) {
        paymentStatus.className = 'alert alert-danger';
        paymentStatus.textContent = 'Please enter a valid email address first.';
        paymentStatus.style.display = 'block';
        return;
    }
    
    if (!paymentAmount || paymentAmount <= 0) {
        paymentStatus.className = 'alert alert-danger';
        paymentStatus.textContent = 'Please enter a valid payment amount.';
        paymentStatus.style.display = 'block';
        return;
    }
    
    // Disable button
    payNowBtn.disabled = true;
    payNowBtn.textContent = 'Processing...';
    
    try {
        // Show payment status
        paymentStatus.className = 'alert alert-info';
        paymentStatus.textContent = 'Initializing payment...';
        paymentStatus.style.display = 'block';
        
        // Initialize payment
        const paymentResponse = await fetch('../actions/initialize_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                amount: paymentAmount,
                email: userEmail,
                description: 'Document Retrieval Fee',
                userID: currentUserID
            })
        });
        
        // Check if response is OK
        if (!paymentResponse.ok) {
            const errorText = await paymentResponse.text();
            throw new Error(`Server error (${paymentResponse.status}): ${errorText.substring(0, 100)}`);
        }
        
        // Try to parse JSON
        let paymentResult;
        try {
            const responseText = await paymentResponse.text();
            paymentResult = JSON.parse(responseText);
        } catch (parseError) {
            // If JSON parsing fails, show the actual response
            const responseText = await paymentResponse.text();
            throw new Error('Invalid response from server. Please check server logs. Response: ' + responseText.substring(0, 200));
        }
        
        if (paymentResult.status !== 'success') {
            const errorMsg = paymentResult.message || 'Payment initialization failed';
            if (paymentResult.debug) {
                console.error('Payment initialization error:', paymentResult.debug);
            }
            throw new Error(errorMsg);
        }
        
        // Verify we have the reference
        if (!paymentResult.reference) {
            throw new Error('Payment reference not received from server');
        }
        
        // Check if PaystackPop is loaded
        if (typeof PaystackPop === 'undefined') {
            throw new Error('Paystack payment library not loaded. Please refresh the page.');
        }
        
        // Open Paystack payment popup
        paymentStatus.className = 'alert alert-warning';
        paymentStatus.textContent = 'Opening payment gateway...';
        
        console.log('Initializing Paystack payment:', {
            key: PAYSTACK_PUBLIC_KEY.substring(0, 20) + '...',
            email: userEmail,
            amount: paymentAmount * 100,
            reference: paymentResult.reference
        });
        
        // Use Paystack's inline popup
        const handler = PaystackPop.setup({
            key: PAYSTACK_PUBLIC_KEY,
            email: userEmail,
            amount: paymentAmount * 100, // Convert to pesewas (GHS)
            ref: paymentResult.reference,
            currency: 'GHS', // Specify currency
            metadata: {
                custom_fields: [
                    {
                        display_name: "User ID",
                        variable_name: "user_id",
                        value: currentUserID
                    },
                    {
                        display_name: "Description",
                        variable_name: "description",
                        value: 'Document Retrieval Fee'
                    }
                ]
            },
            callback: function(response) {
                // Payment successful - verify it
                console.log('Payment callback received:', response);
                verifyPayment(response.reference);
            },
            onClose: function() {
                paymentStatus.className = 'alert alert-warning';
                paymentStatus.textContent = 'Payment window closed. You can try again or submit without payment.';
                payNowBtn.disabled = false;
                payNowBtn.textContent = 'Pay Now';
            }
        });
        
        handler.openIframe();
        
    } catch (error) {
        paymentStatus.className = 'alert alert-danger';
        paymentStatus.textContent = 'Error: ' + error.message;
        paymentStatus.style.display = 'block';
        payNowBtn.disabled = false;
        payNowBtn.textContent = 'Pay Now';
    }
});

// Verify payment (separate from form submission)
async function verifyPayment(paymentReference) {
    const paymentStatus = document.getElementById('paymentStatus');
    const payNowBtn = document.getElementById('payNowBtn');
    
    try {
        paymentStatus.className = 'alert alert-info';
        paymentStatus.textContent = 'Verifying payment...';
        
        // Verify payment
        const verifyResponse = await fetch(`../actions/verify_payment.php?reference=${paymentReference}`);
        
        // Read response text once
        const responseText = await verifyResponse.text();
        
        if (!verifyResponse.ok) {
            throw new Error(`Server error (${verifyResponse.status}): ${responseText.substring(0, 200)}`);
        }
        
        // Parse JSON response
        let verifyResult;
        try {
            verifyResult = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Response Text:', responseText);
            throw new Error('Invalid response from server. Response: ' + responseText.substring(0, 200));
        }
        
        if (verifyResult.status !== 'success') {
            const errorMsg = verifyResult.message || 'Payment verification failed';
            throw new Error(errorMsg);
        }
        
        // Store payment reference
        document.getElementById('paymentReference').value = paymentReference;
        
        // Show success
        paymentStatus.className = 'alert alert-success';
        paymentStatus.textContent = '✓ Payment verified successfully! You can now submit your document request.';
        paymentStatus.style.display = 'block';
        
        // Disable payment button and show success
        payNowBtn.disabled = true;
        payNowBtn.textContent = '✓ Payment Completed';
        payNowBtn.classList.remove('btn-success');
        payNowBtn.classList.add('btn-secondary');
        
    } catch (error) {
        paymentStatus.className = 'alert alert-danger';
        paymentStatus.textContent = 'Payment verification error: ' + error.message;
        paymentStatus.style.display = 'block';
        payNowBtn.disabled = false;
        payNowBtn.textContent = 'Pay Now';
    }
}

// Handle form submission (no payment processing here)
document.getElementById('documentForm').addEventListener('submit', async function (event) {
    event.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const paymentStatus = document.getElementById('paymentStatus');
    const consentCheck = document.getElementById('accept_terms_doc');
    const consentError = document.getElementById('consentDocError');
    
    if (!consentCheck || !consentCheck.checked) {
        if (consentError) {
            consentError.textContent = 'You must accept the Terms of Service and Privacy Policy to submit a document request.';
            consentError.style.display = 'block';
        }
        return;
    }
    if (consentError) consentError.style.display = 'none';
    
    // Disable submit button and show success message immediately
    submitBtn.disabled = true;
    submitBtn.textContent = '✓ Document request successfully submitted';
    submitBtn.classList.remove('btn-primary');
    submitBtn.classList.add('btn-success');
    
    try {
        // Submit document directly
        await submitDocument();
    } catch (error) {
        paymentStatus.className = 'alert alert-danger';
        paymentStatus.textContent = 'Error: ' + error.message;
        paymentStatus.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Document';
        submitBtn.classList.remove('btn-success');
        submitBtn.classList.add('btn-primary');
    }
});

// Submit document form
async function submitDocument() {
    const form = document.getElementById('documentForm');
    const formData = new FormData(form);
    const paymentStatus = document.getElementById('paymentStatus');
    const submitBtn = document.getElementById('submitBtn');
    
    // Include payment reference if payment was made
    const paymentReference = document.getElementById('paymentReference').value;
    if (paymentReference) {
        formData.append('paymentReference', paymentReference);
    }
    
    try {
        const response = await fetch('../actions/submitdocument_action.php', {
            method: 'POST',
            body: formData,
        });
        
        // Read response text once
        const responseText = await response.text();
        
        if (!response.ok) {
            throw new Error(`Server error (${response.status}): ${responseText.substring(0, 200)}`);
        }
        
        // Parse JSON response
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Response Text:', responseText);
            throw new Error('Invalid response from server. Please check server logs. Response: ' + responseText.substring(0, 300));
        }

        if (result.success) {
            const paymentMsg = paymentReference ? ' (Payment completed)' : '';
            const ids = result.documentIDs && result.documentIDs.length ? result.documentIDs : (result.documentID || result.documentId ? [result.documentID || result.documentId] : []);
            
            // Store all document IDs in localStorage
            if (ids.length > 0) {
                let savedIDs = JSON.parse(localStorage.getItem('documentIDs') || '[]');
                const descInputs = document.querySelectorAll('input[name^="description"]');
                ids.forEach(function(id, idx) {
                    const desc = (descInputs[idx] && descInputs[idx].value) ? descInputs[idx].value.substring(0, 50) : '';
                    if (!savedIDs.some(function(item) { return item.id === id; })) {
                        savedIDs.push({ id: id, date: new Date().toISOString(), description: desc });
                    }
                });
                localStorage.setItem('documentIDs', JSON.stringify(savedIDs));
            }
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.textContent = ids.length > 1 ? '✓ ' + ids.length + ' documents submitted' : '✓ Document request successfully submitted';
            submitBtn.disabled = true;
            
            const idBlock = ids.length > 0
                ? (ids.length === 1
                    ? `<div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin: 1rem 0; border: 2px solid #28a745;">
                        <strong style="display: block; margin-bottom: 0.5rem; color: #333;">Your Document ID:</strong>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <code style="font-size: 1.2rem; font-weight: bold; color: #dc3545; flex: 1; padding: 0.5rem; background: white; border-radius: 5px;">${ids[0]}</code>
                            <button onclick="copyDocumentID('${ids[0]}')" class="btn btn-sm btn-primary">📋 Copy</button>
                        </div>
                        <small style="display: block; margin-top: 0.5rem; color: #666;">Save this ID to track your document.</small>
                    </div>`
                    : `<div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin: 1rem 0; border: 2px solid #28a745;">
                        <strong style="display: block; margin-bottom: 0.5rem; color: #333;">Your Document IDs (${ids.length}):</strong>
                        <ul class="mb-0 ps-3">
                            ${ids.map(function(id) { return '<li><code>'+id+'</code> <button type="button" onclick="copyDocumentID(\''+id+'\')" class="btn btn-sm btn-outline-primary ms-1">Copy</button></li>'; }).join('')}
                        </ul>
                        <small style="display: block; margin-top: 0.5rem; color: #666;">Save these IDs to track your documents.</small>
                    </div>`)
                : `<div class="mb-2"><strong>Request submitted.</strong> Check your dashboard to track your request.</div>`;
            paymentStatus.className = 'alert alert-success';
            paymentStatus.innerHTML = `
                <h4>✓ ${ids.length > 1 ? ids.length + ' Documents' : 'Document'} Submitted Successfully!${paymentMsg}</h4>
                ${idBlock}
                <div style="margin-top: 1rem;">
                    <button onclick="window.location.href='index.php?controller=Seeker&action=progress'" class="btn btn-success me-2">Track Progress</button>
                    <button onclick="clearForm()" class="btn btn-secondary">Submit More</button>
                </div>
            `;
            paymentStatus.style.display = 'block';
            
            // Scroll to the success message
            paymentStatus.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Clear form
            document.getElementById('documentForm').reset();
            document.getElementById('paymentReference').value = '';
            document.getElementById('enablePayment').checked = false;
            document.getElementById('paymentSection').style.display = 'none';
            
            // Reload saved IDs to show the new one
            loadSavedDocumentIDs();
            
            // Don't redirect automatically - let user decide
        } else {
            throw new Error(result.message || 'Document submission failed');
        }
    } catch (error) {
        paymentStatus.className = 'alert alert-danger';
        paymentStatus.textContent = 'Error: ' + error.message;
        paymentStatus.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Document';
    }
}
</script>
</body>
</html>
