<?php
/**
 * Payment page template
 */
$pageTitle = '3DS Payment Example';
$currentPage = 'home';

// Extra head content
$extraHeadContent = '';

// Define the page-specific scripts
$scripts = '
<script src="assets/js/input-validation.js"></script>
<script src="assets/js/3ds.js" defer type="module"></script>
';
?>

<div class="card-container">
    <h1 class="text-center mb-4">3DS Payment Example</h1>

    <!-- Payment Form -->
    <div id="paymentCard" class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Payment Details</h5>
        </div>
        <div class="card-body">
            <form id="paymentForm">
                <div class="mb-3">
                    <label for="cardNumber" class="form-label">Card Number</label>
                    <input type="text" class="form-control" id="cardNumber"
                           value="<?php echo $config['3ds']['test_card']['number']; ?>" required>
                    <div class="form-text">Use test card: <?php echo $config['3ds']['test_card']['number']; ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col">
                        <label for="expiryDate" class="form-label">Expiry Date (MM/YY)</label>
                        <input type="text" class="form-control" id="expiryDate" value="12/25" required>
                    </div>
                    <div class="col">
                        <label for="cvv" class="form-label">CVV</label>
                        <input type="text" class="form-control" id="cvv" value="123" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="amount" class="form-label">Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="amount" value="10.00" required>
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary" id="payButton">Pay Now</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Processing Spinner -->
    <div class="spinner-container hidden" id="processingSpinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Processing...</span>
        </div>
        <span class="ms-2">Processing payment...</span>
    </div>

    <!-- Results Container -->
    <div class="result-container hidden" id="resultContainer">
        <h5>Transaction Result</h5>
        <div class="result-content" id="resultContent"></div>
    </div>

    <!-- Challenge Container -->
    <div class="hidden" id="challengeContainer">
        <h5>3DS Challenge</h5>
        <p>Please complete the authentication challenge below:</p>
        <div id="challengeFrameContainer"></div>
    </div>

    <!-- Hidden IFrames Container -->
    <div class="iframe-container" id="iframeContainer"></div>
</div> 