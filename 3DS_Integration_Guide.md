# 3DS Integration Documentation

## Overview

This document provides instructions for integrating our 3D Secure (3DS) authentication system with an existing website that uses Authorize.net for payment processing.

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Integration Steps](#integration-steps)
3. [File Structure](#file-structure)
4. [HTML Implementation](#html-implementation)
5. [JavaScript Integration](#javascript-integration)
6. [Backend Integration](#backend-integration)
7. [Authorize.net Integration](#authorizenet-integration)
8. [Configuration](#configuration)
9. [Testing](#testing)
10. [Troubleshooting](#troubleshooting)

## System Requirements

- PHP 7.4 or higher
- SSL certificate (HTTPS is required for 3DS)
- Access to modify site JavaScript and PHP files
- Existing Authorize.net merchant account

## Integration Steps

### 1. Copy Required Files

Copy these files to your website:

- `/public/assets/js/3ds.js` → Your site's JavaScript directory
- `/src/Services/ThreeDSService.php` → Your PHP services directory
- `/src/Exceptions/ThreeDSException.php` → Your exceptions directory
- `/src/Helpers/LogHelper.php` → Your helpers directory

### 2. Update Your Payment Form

Modify your existing payment form to integrate with the 3DS system.

### 3. Create API Endpoints

Create backend API endpoints to handle 3DS authentication.

### 4. Connect with Authorize.net

Integrate the 3DS authentication flow with your Authorize.net payment processing.

## File Structure

```
your-site/
├── assets/
│   └── js/
│       └── 3ds.js              # Main 3DS JavaScript handling
├── includes/
│   ├── services/
│   │   └── ThreeDSService.php  # 3DS processing service
│   ├── exceptions/
│   │   └── ThreeDSException.php # 3DS exception handling
│   └── helpers/
│       └── LogHelper.php       # Logging utilities
└── api/
    └── 3ds-endpoint.php        # API endpoint for 3DS requests
```

## HTML Implementation

Add these elements to your payment form page:

```html
<!-- Payment form with required fields -->
<div id="paymentCard" class="card">
  <div class="card-body">
    <form id="paymentForm">
      <div class="mb-3">
        <label for="cardNumber" class="form-label">Card Number</label>
        <input
          type="text"
          class="form-control"
          id="cardNumber"
          placeholder="1234 5678 9012 3456"
          required
        />
      </div>
      <div class="mb-3">
        <label for="expiryDate" class="form-label">Expiry Date</label>
        <input
          type="text"
          class="form-control"
          id="expiryDate"
          placeholder="MM/YY"
          required
        />
      </div>
      <div class="mb-3">
        <label for="cvv" class="form-label">CVV</label>
        <input
          type="text"
          class="form-control"
          id="cvv"
          placeholder="123"
          required
        />
      </div>
      <div class="mb-3">
        <label for="amount" class="form-label">Amount</label>
        <input
          type="text"
          class="form-control"
          id="amount"
          placeholder="10.00"
          required
        />
      </div>
      <button type="submit" class="btn btn-primary">Pay Now</button>
    </form>
  </div>
</div>

<!-- Processing spinner -->
<div id="processingSpinner" class="text-center p-5 hidden">
  <div class="spinner-border" role="status">
    <span class="visually-hidden">Processing...</span>
  </div>
  <p class="mt-2">Processing your payment...</p>
</div>

<!-- Result container -->
<div id="resultContainer" class="card mt-4 hidden">
  <div class="card-body">
    <h5 class="card-title">Payment Result</h5>
    <pre id="resultContent"></pre>
    <button class="btn btn-secondary mt-3" onclick="window.location.reload()">
      Start Over
    </button>
  </div>
</div>

<!-- Challenge container -->
<div id="challengeContainer" class="hidden">
  <div class="card">
    <div class="card-header">
      <h5>Authentication Required</h5>
    </div>
    <div class="card-body">
      <div id="challengeFrameContainer"></div>
    </div>
  </div>
</div>

<!-- Hidden iframe container -->
<div id="iframeContainer" style="display:none;"></div>
```

Add required CSS:

```html
<style>
  .hidden {
    display: none !important;
  }
  .challenge-iframe {
    width: 100%;
    min-height: 420px;
    border: none;
  }
  pre {
    white-space: pre-wrap;
  }
</style>
```

## JavaScript Integration

### 1. Include the 3DS JavaScript

```html
<script src="/assets/js/3ds.js"></script>
<script src="/assets/js/card-validation.js"></script>
<!-- Create this for card validation -->
```

### 2. Create card-validation.js for Basic Card Validation

```javascript
// card-validation.js
window.validateCardNumber = function (cardNumber) {
  // Implement Luhn algorithm for card validation
  const digits = cardNumber.replace(/\D/g, "");
  let sum = 0;
  let shouldDouble = false;

  for (let i = digits.length - 1; i >= 0; i--) {
    let digit = parseInt(digits.charAt(i));
    if (shouldDouble) {
      digit *= 2;
      if (digit > 9) digit -= 9;
    }
    sum += digit;
    shouldDouble = !shouldDouble;
  }

  return sum % 10 === 0 && digits.length >= 13 && digits.length <= 19;
};

window.validateExpiryDate = function (expiryDate) {
  const regex = /^(0[1-9]|1[0-2])\/([0-9]{2})$/;
  if (!regex.test(expiryDate)) return false;

  const parts = expiryDate.split("/");
  const month = parseInt(parts[0]);
  const year = parseInt("20" + parts[1]);

  const now = new Date();
  const currentMonth = now.getMonth() + 1;
  const currentYear = now.getFullYear();

  return year > currentYear || (year === currentYear && month >= currentMonth);
};
```

### 3. Configure API Endpoint

In your main JavaScript file or within a script tag:

```javascript
// Configure the API endpoint for 3DS
window.threeDSConfig = {
  apiEndpoint: "/api/3ds-endpoint.php",
  merchantId: "your-merchant-id",
};
```

## Backend Integration

### 1. Create 3DS API Endpoint (3ds-endpoint.php)

```php
<?php
require_once '../includes/services/ThreeDSService.php';
require_once '../includes/exceptions/ThreeDSException.php';
require_once '../includes/helpers/LogHelper.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set headers
header('Content-Type: application/json');

// Get JSON request body
$requestBody = file_get_contents('php://input');
$requestData = json_decode($requestBody, true);

// Validate request data
if (!$requestData || !isset($requestData['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request format']);
    exit;
}

// Get 3DS Configuration
$config = [
    'merchant' => [
        'id' => 'your-merchant-id',
        'name' => 'Your Company Name'
    ],
    'server' => [
        'url' => 'https://your-3ds-provider.com'
    ],
    'api' => [
        'init_endpoint' => '/api/v1/auth/init',
        'auth_result_endpoint' => '/api/v1/auth/result',
        'challenge_status_endpoint' => '/api/v1/auth/challenge-status'
    ],
    'notification_url' => 'https://your-website.com/3ds-callback',
    'ssl' => [
        'cert_file' => '/path/to/your/certificate.pem',
        'key_file' => '/path/to/your/private-key.pem',
        'ca_file' => '/path/to/your/ca-bundle.pem'
    ],
    'test_card' => [
        'expiry' => '2412' // YY MM format
    ],
    'transaction' => [
        'currency' => 'USD'
    ]
];

try {
    $threeDSService = new ThreeDSService($config);
    $action = $requestData['action'];
    $response = [];

    switch ($action) {
        case 'init':
            $cardNumber = $requestData['authData']['acctNumber'] ?? '';
            $response = $threeDSService->initialize($cardNumber);
            break;

        case 'auth':
            $threeDSServerTransID = $requestData['threeDSServerTransID'] ?? '';
            $threeDSRequestorTransID = $requestData['threeDSRequestorTransID'] ?? '';
            $browserInfo = $requestData['browserInfo'] ?? '';
            $cardNumber = $requestData['acctNumber'] ?? '';

            $additionalData = [
                'cardExpiryDate' => $requestData['cardExpiryDate'] ?? '',
                'purchaseAmount' => $requestData['purchaseAmount'] ?? ''
            ];

            $response = $threeDSService->authenticate(
                $threeDSServerTransID,
                $threeDSRequestorTransID,
                $browserInfo,
                $cardNumber,
                $additionalData
            );
            break;

        case 'getAuthResult':
            $threeDSServerTransID = $requestData['threeDSServerTransID'] ?? '';
            $response = $threeDSService->getAuthResult($threeDSServerTransID);
            break;

        case 'updateChallengeStatus':
            $threeDSServerTransID = $requestData['threeDSServerTransID'] ?? '';
            $status = $requestData['status'] ?? '01';
            $response = $threeDSService->updateChallengeStatus($threeDSServerTransID, $status);
            break;

        default:
            throw new ThreeDSException("Unknown action: $action");
    }

    echo json_encode($response);

} catch (ThreeDSException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
```

## Authorize.net Integration

Integrate 3DS with Authorize.net by modifying your payment processing code:

```php
<?php
// Process payment after successful 3DS authentication
function processPaymentWithAuthorize($paymentData, $threeDSResult) {
    // Only proceed if 3DS authentication was successful
    if ($threeDSResult['transStatus'] !== 'Y' && $threeDSResult['transStatus'] !== 'A') {
        return [
            'success' => false,
            'message' => 'Authentication failed: ' . ($threeDSResult['message'] ?? 'Unknown error')
        ];
    }

    // Include Authorize.net SDK
    require_once 'vendor/autoload.php';

    // Authorize.net credentials
    $merchantAuthentication = new net\authorize\api\contract\v1\MerchantAuthenticationType();
    $merchantAuthentication->setName('YOUR_API_LOGIN_ID');
    $merchantAuthentication->setTransactionKey('YOUR_TRANSACTION_KEY');

    // Create payment object
    $creditCard = new net\authorize\api\contract\v1\CreditCardType();
    $creditCard->setCardNumber($paymentData['cardNumber']);
    $creditCard->setExpirationDate($paymentData['expiryDate']);
    $creditCard->setCardCode($paymentData['cvv']);

    // Create payment type
    $paymentType = new net\authorize\api\contract\v1\PaymentType();
    $paymentType->setCreditCard($creditCard);

    // Create transaction request
    $transactionRequestType = new net\authorize\api\contract\v1\TransactionRequestType();
    $transactionRequestType->setTransactionType('authCaptureTransaction');
    $transactionRequestType->setAmount($paymentData['amount']);
    $transactionRequestType->setPayment($paymentType);

    // Add 3DS verification data
    $threeDSData = new net\authorize\api\contract\v1\ExtendedInformationType();

    // Set parameters and flags for Authorize.net to know this transaction was 3DS verified
    $threeDSData->setCavv($threeDSResult['cavv'] ?? '');
    $threeDSData->setEci($threeDSResult['eci'] ?? '');
    $threeDSData->setXid($threeDSResult['xid'] ?? '');

    $transactionRequestType->setAuthorizationIndicatorType($threeDSData);

    // Assemble the complete transaction request
    $request = new net\authorize\api\contract\v1\CreateTransactionRequest();
    $request->setMerchantAuthentication($merchantAuthentication);
    $request->setTransactionRequest($transactionRequestType);

    // Create the controller and execute the request
    $controller = new net\authorize\api\controller\CreateTransactionController($request);
    $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

    // Process response
    $transactionResponse = $response->getTransactionResponse();

    if ($response->getMessages()->getResultCode() === 'Ok' && $transactionResponse != null) {
        return [
            'success' => true,
            'transactionId' => $transactionResponse->getTransId(),
            'authCode' => $transactionResponse->getAuthCode(),
            'message' => 'Payment processed successfully'
        ];
    } else {
        $errorMessages = $response->getMessages()->getMessage();
        return [
            'success' => false,
            'message' => $errorMessages[0]->getText()
        ];
    }
}
```

### JavaScript Integration with Authorize.net

```javascript
// After successful 3DS authentication, process payment with Authorize.net
async function processPaymentAfter3DS(threeDSResult) {
  if (threeDSResult.transStatus === "Y" || threeDSResult.transStatus === "A") {
    try {
      // Get payment data
      const cardNumber = document.getElementById("cardNumber").value;
      const expiryDate = document.getElementById("expiryDate").value;
      const cvv = document.getElementById("cvv").value;
      const amount = document.getElementById("amount").value;

      // Send to your backend for Authorize.net processing
      const response = await fetch("/api/process-payment.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          cardNumber: cardNumber,
          expiryDate: expiryDate,
          cvv: cvv,
          amount: amount,
          threeDSResult: threeDSResult,
        }),
      });

      const result = await response.json();

      if (result.success) {
        showPaymentSuccess(result);
      } else {
        showPaymentError(result.message);
      }
    } catch (error) {
      showPaymentError("Error processing payment: " + error.message);
    }
  } else {
    showPaymentError("3DS authentication was not successful");
  }
}

function showPaymentSuccess(result) {
  // Display success message to user
  const successElement = document.createElement("div");
  successElement.className = "alert alert-success";
  successElement.textContent =
    "Payment successful! Transaction ID: " + result.transactionId;
  document.querySelector("#resultContainer").appendChild(successElement);
}

function showPaymentError(message) {
  // Display error message to user
  const errorElement = document.createElement("div");
  errorElement.className = "alert alert-danger";
  errorElement.textContent = message;
  document.querySelector("#resultContainer").appendChild(errorElement);
}
```

## Configuration

### SSL Certificates

You'll need the following certificates for secure communication with your 3DS provider:

1. **Client Certificate** (`cert_file`): Authenticates your website to the 3DS server
2. **Private Key** (`key_file`): Associated private key for your client certificate
3. **CA Bundle** (`ca_file`): Certificate Authority certificates to verify the 3DS server

### 3DS Provider Configuration

Contact your 3DS provider to obtain the following information:

1. API endpoints
2. Merchant credentials
3. Test card data for testing
4. Test environment access

## Testing

### Test Cards

Use these test cards to verify your integration:

| Card Number      | Behavior                           |
| ---------------- | ---------------------------------- |
| 4111111111111111 | Returns Y (Authenticated)          |
| 4000000000000002 | Returns N (Not Authenticated)      |
| 4000000000000010 | Returns C (Challenge Required)     |
| 4000000000000028 | Returns A (Attempt)                |
| 4000000000000036 | Returns U (Unable to authenticate) |
| 4000000000000044 | Returns R (Rejected)               |

### Debugging

Enable debug mode by adding:

```php
// In your 3DS configuration
$config['debug'] = true;
```

## Troubleshooting

### Common Issues

1. **Headers already sent error**

   - Make sure no output is sent before session_start()
   - Check for whitespace before <?php or after ?>

2. **CORS Issues**

   - Add appropriate CORS headers to your API endpoint

3. **SSL Certificate Problems**

   - Verify certificate paths are correct
   - Ensure certificates haven't expired

4. **Browser Information Collection Fails**

   - Check that iframes are loading correctly
   - Verify that your site is using HTTPS

5. **Challenge Window Doesn't Appear**

   - Check browser console for errors
   - Ensure the challengeUrl is being set correctly

6. **Authorize.net Authentication Failed**
   - Verify that 3DS parameters are being passed correctly to Authorize.net
   - Check that your merchant credentials are correct

For additional help, contact your 3DS provider's support or Authorize.net support team.

## Reference

- [3DS 2.0 Specification](https://www.emvco.com/emv-technologies/3d-secure/)
- [Authorize.net 3DS Integration Guide](https://developer.authorize.net/api/reference/features/3d-secure.html)
