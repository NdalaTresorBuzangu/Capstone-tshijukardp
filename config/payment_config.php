<?php
/**
 * Payment Gateway Configuration
 * Supports multiple payment providers for cross-border payments
 */

// Payment Gateway Types
define('PAYMENT_GATEWAY_PAYSTACK', 'paystack');
define('PAYMENT_GATEWAY_ECOBANK', 'ecobank');

// Supported Currencies (African countries)
$supportedCurrencies = [
    'GHS' => ['name' => 'Ghana Cedi', 'country' => 'Ghana', 'symbol' => '₵'],
    'NGN' => ['name' => 'Nigerian Naira', 'country' => 'Nigeria', 'symbol' => '₦'],
    'KES' => ['name' => 'Kenyan Shilling', 'country' => 'Kenya', 'symbol' => 'KSh'],
    'UGX' => ['name' => 'Ugandan Shilling', 'country' => 'Uganda', 'symbol' => 'USh'],
    'TZS' => ['name' => 'Tanzanian Shilling', 'country' => 'Tanzania', 'symbol' => 'TSh'],
    'RWF' => ['name' => 'Rwandan Franc', 'country' => 'Rwanda', 'symbol' => 'RF'],
    'ZAR' => ['name' => 'South African Rand', 'country' => 'South Africa', 'symbol' => 'R'],
    'XOF' => ['name' => 'West African CFA Franc', 'country' => 'West Africa', 'symbol' => 'CFA'],
    'XAF' => ['name' => 'Central African CFA Franc', 'country' => 'Central Africa', 'symbol' => 'CFA'],
    'USD' => ['name' => 'US Dollar', 'country' => 'International', 'symbol' => '$']
];

// Mobile Money Providers by Country
$mobileMoneyProviders = [
    'GHS' => ['MTN Mobile Money', 'Vodafone Cash', 'AirtelTigo Money'],
    'NGN' => ['MTN Mobile Money', 'Airtel Money', '9Mobile Money'],
    'KES' => ['M-Pesa', 'Airtel Money', 'Equitel'],
    'UGX' => ['MTN Mobile Money', 'Airtel Money', 'Africell Money'],
    'TZS' => ['M-Pesa', 'Tigo Pesa', 'Airtel Money'],
    'RWF' => ['MTN Mobile Money', 'Airtel Money', 'Tigo Cash'],
    'ZAR' => ['MTN Mobile Money', 'Vodacom M-Pesa'],
    'XOF' => ['Orange Money', 'MTN Mobile Money', 'Moov Money'],
    'XAF' => ['Orange Money', 'MTN Mobile Money', 'Moov Money']
];

// Paystack Configuration
define('PAYSTACK_SECRET_KEY', 'sk_test_5e1b52bbca0b63f4e196f08d6eabd880a66b8a03');
define('PAYSTACK_PUBLIC_KEY', 'pk_test_11c4dffd1bfb8c9efb25eceb0b6132aa85761747');

// Ecobank API Configuration
// Note: Replace with your actual Ecobank API credentials
define('ECOBANK_API_KEY', 'your_ecobank_api_key_here');
define('ECOBANK_API_SECRET', 'your_ecobank_api_secret_here');
define('ECOBANK_MERCHANT_ID', 'your_ecobank_merchant_id_here');
define('ECOBANK_API_BASE_URL', 'https://api.ecobank.com/v1'); // Update with actual Ecobank API URL

// Currency Exchange Rates (Base: USD)
// Note: In production, fetch these from a real-time API like exchangerate-api.com or fixer.io
$exchangeRates = [
    'USD' => 1.0,
    'GHS' => 12.5,  // 1 USD = 12.5 GHS
    'NGN' => 1500.0, // 1 USD = 1500 NGN
    'KES' => 130.0,  // 1 USD = 130 KES
    'UGX' => 3700.0, // 1 USD = 3700 UGX
    'TZS' => 2300.0, // 1 USD = 2300 TZS
    'RWF' => 1200.0, // 1 USD = 1200 RWF
    'ZAR' => 18.0,   // 1 USD = 18 ZAR
    'XOF' => 600.0,  // 1 USD = 600 XOF
    'XAF' => 600.0   // 1 USD = 600 XAF
];

/**
 * Get exchange rate between two currencies
 */
function getExchangeRate($fromCurrency, $toCurrency) {
    global $exchangeRates;
    
    if ($fromCurrency === $toCurrency) {
        return 1.0;
    }
    
    if (!isset($exchangeRates[$fromCurrency]) || !isset($exchangeRates[$toCurrency])) {
        return null;
    }
    
    // Convert to USD first, then to target currency
    $fromToUSD = 1 / $exchangeRates[$fromCurrency];
    $usdToTarget = $exchangeRates[$toCurrency];
    
    return $fromToUSD * $usdToTarget;
}

/**
 * Convert amount from one currency to another
 */
function convertCurrency($amount, $fromCurrency, $toCurrency) {
    $rate = getExchangeRate($fromCurrency, $toCurrency);
    if ($rate === null) {
        return null;
    }
    return round($amount * $rate, 2);
}

/**
 * Get mobile money providers for a currency
 */
function getMobileMoneyProviders($currency) {
    global $mobileMoneyProviders;
    return $mobileMoneyProviders[$currency] ?? [];
}

/**
 * Get supported currencies (country/currency list for dropdowns).
 * Returns a list that works regardless of include scope (e.g. when view is rendered via MVC).
 */
function getSupportedCurrencies() {
    return [
        'GHS' => ['name' => 'Ghana Cedi', 'country' => 'Ghana', 'symbol' => '₵'],
        'NGN' => ['name' => 'Nigerian Naira', 'country' => 'Nigeria', 'symbol' => '₦'],
        'KES' => ['name' => 'Kenyan Shilling', 'country' => 'Kenya', 'symbol' => 'KSh'],
        'UGX' => ['name' => 'Ugandan Shilling', 'country' => 'Uganda', 'symbol' => 'USh'],
        'TZS' => ['name' => 'Tanzanian Shilling', 'country' => 'Tanzania', 'symbol' => 'TSh'],
        'RWF' => ['name' => 'Rwandan Franc', 'country' => 'Rwanda', 'symbol' => 'RF'],
        'ZAR' => ['name' => 'South African Rand', 'country' => 'South Africa', 'symbol' => 'R'],
        'XOF' => ['name' => 'West African CFA Franc', 'country' => 'West Africa', 'symbol' => 'CFA'],
        'XAF' => ['name' => 'Central African CFA Franc', 'country' => 'Central Africa', 'symbol' => 'CFA'],
        'USD' => ['name' => 'US Dollar', 'country' => 'International', 'symbol' => '$']
    ];
}
