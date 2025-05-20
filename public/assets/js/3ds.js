/**
 * 3DS Payment Handling
 * This module handles 3D Secure payment process
 *
 * @typedef {Object} TransactionData
 * @property {string|null} threeDSServerTransID - Server transaction ID
 * @property {string|null} threeDSRequestorTransID - Requestor transaction ID
 * @property {string|null} threeDSServerCallbackUrl - Server callback URL
 * @property {string|null} monUrl - Monitoring URL
 * @property {string|null} authUrl - Authentication URL
 * @property {string|null} browserInfo - Browser information data
 * @property {boolean} eventReceived - Whether an event has been received
 * @property {boolean} challengeCompleted - Whether challenge completion was already processed
 */

class ThreeDSPayment {
    /**
     * Constructor
     */
    constructor() {
        /** @type {TransactionData} Transaction data store */
        this.transactionData = {
            threeDSServerTransID: null,
            threeDSRequestorTransID: null,
            threeDSServerCallbackUrl: null,
            monUrl: null,
            authUrl: null,
            browserInfo: null,
            eventReceived: false,
            challengeCompleted: false
        };

        // DOM Elements
        this.paymentCard = document.getElementById('paymentCard');
        this.paymentForm = document.getElementById('paymentForm');
        this.processingSpinner = document.getElementById('processingSpinner');
        this.resultContainer = document.getElementById('resultContainer');
        this.resultContent = document.getElementById('resultContent');
        this.challengeContainer = document.getElementById('challengeContainer');
        this.challengeFrameContainer = document.getElementById('challengeFrameContainer');
        this.iframeContainer = document.getElementById('iframeContainer');

        // Validate required elements
        if (!this.paymentCard || !this.paymentForm || !this.processingSpinner || !this.resultContainer ||
            !this.resultContent || !this.challengeContainer || !this.challengeFrameContainer ||
            !this.iframeContainer) {
            console.error('Required DOM elements not found');
            this.showError('Required page elements not found. Please reload the page.');
            return;
        }

        // Initialize event listeners
        this.initEventListeners();
    }

    /**
     * Initialize event listeners
     */
    initEventListeners() {
        // Handle form submission
        this.paymentForm.addEventListener('submit', this.handleFormSubmit.bind(this));

        // Set up event listener for iframe messages
        window.addEventListener('message', this.handleFrameEvent.bind(this), false);

        // Reset button
        document.addEventListener('keydown', (event) => {
            // Press Escape to reset form
            if (event.key === 'Escape') {
                this.showPaymentForm();
            }
        });
    }

    /**
     * Handle form submission
     * @param {Event} event - Form submit event
     */
    async handleFormSubmit(event) {
        event.preventDefault();

        const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
        const expiryDate = document.getElementById('expiryDate').value;
        const amount = document.getElementById('amount').value;

        // Check if card number passes Luhn algorithm
        if (!window.validateCardNumber || !window.validateCardNumber(cardNumber)) {
            this.showResult('Please enter a valid card number', true);
            return;
        }
        
        // Check if expiry date is valid and not expired
        if (!window.validateExpiryDate || !window.validateExpiryDate(expiryDate)) {
            this.showResult('Please enter a valid expiry date (MM/YY) that has not expired', true);
            return;
        }

        // Validate amount
        if (!amount || isNaN(parseFloat(amount)) || parseFloat(amount) <= 0) {
            this.showResult('Please enter a valid amount', true);
            return;
        }

        // Start 3DS process
        await this.initialize3DS(cardNumber.trim(), amount.trim());
    }

    /**
     * Generate UUID for transaction ID
     * @returns {string} UUID
     */
    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    /**
     * Format amount for API (convert dollars to cents)
     * @param {string} amount - Amount in dollars
     * @returns {string} Amount in cents
     */
    formatAmount(amount) {
        return Math.round(parseFloat(amount) * 100).toString();
    }

    /**
     * Format date for API (YYYYMMDDHHmmss)
     * @returns {string} Formatted date
     */
    formatDate() {
        const date = new Date();
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');

        return `${year}${month}${day}${hours}${minutes}${seconds}`;
    }

    /**
     * Format expiry date for API (YYMM)
     * @param {string} expiryDateMMYY - Expiry date in MM/YY format
     * @returns {string} Expiry date in YYMM format
     */
    formatExpiryDate(expiryDateMMYY) {
        const parts = expiryDateMMYY.split('/');
        if (parts.length !== 2) return '';

        const month = parts[0].trim();
        const year = parts[1].trim();

        return year + month;
    }

    /**
     * Show processing spinner
     */
    showProcessing() {
        this.paymentCard.classList.add('hidden');
        this.paymentForm.classList.add('hidden');
        this.processingSpinner.classList.remove('hidden');
        this.resultContainer.classList.add('hidden');
        this.challengeContainer.classList.add('hidden');
    }

    /**
     * Show result
     * @param {Object|string} result - Result to display
     * @param {boolean} isError - Whether result is an error
     */
    showResult(result, isError = false) {
        this.processingSpinner.classList.add('hidden');
        this.resultContainer.classList.remove('hidden');
        
        // Clear previous result content
        this.resultContent.innerHTML = '';
        
        // If it's an error message (string)
        if (typeof result === 'string') {
            this.resultContent.classList.add('text-danger');
            this.resultContent.textContent = result;
            return;
        }
        
        // Remove error styling if not an error
        if (!isError) {
            this.resultContent.classList.remove('text-danger');
        }
        
        // Format object results based on status
        if (typeof result === 'object') {
            // Add status header
            const statusHeader = document.createElement('h3');
            
            // Set status class and text based on status
            if (result.status === 'success') {
                statusHeader.className = 'text-success';
                statusHeader.textContent = '✓ ' + (result.message || 'Payment Authenticated');
            } else if (result.status === 'failed' || result.status === 'rejected' || result.status === 'error') {
                statusHeader.className = 'text-danger';
                statusHeader.textContent = '✗ ' + (result.message || 'Authentication Failed');
            } else if (result.status === 'challenge') {
                statusHeader.className = 'text-warning';
                statusHeader.textContent = '⚠ ' + (result.message || 'Challenge Required');
            } else if (result.status === 'decoupled') {
                statusHeader.className = 'text-info';
                statusHeader.textContent = '⟳ ' + (result.message || 'Decoupled Authentication Required');
            } else if (result.status === 'partial') {
                statusHeader.className = 'text-warning';
                statusHeader.textContent = '△ ' + (result.message || 'Authentication Attempted');
            } else {
                statusHeader.className = 'text-info';
                statusHeader.textContent = 'ⓘ ' + (result.message || 'Authentication Completed');
            }
            
            this.resultContent.appendChild(statusHeader);
            
            // Add transaction status
            if (result.transStatus) {
                const transStatusEl = document.createElement('p');
                transStatusEl.innerHTML = '<strong>Transaction Status:</strong> ' + this.getTransStatusDescription(result.transStatus);
                this.resultContent.appendChild(transStatusEl);
            }
            
            // Add raw details in collapsible section
            if (result.details) {
                const detailsContainer = document.createElement('div');
                detailsContainer.className = 'details-container mt-3';
                
                const detailsToggle = document.createElement('button');
                detailsToggle.className = 'btn btn-sm btn-outline-secondary';
                detailsToggle.textContent = 'Show Technical Details';
                detailsToggle.onclick = function() {
                    const detailsContent = document.getElementById('details-content');
                    if (detailsContent.style.display === 'none') {
                        detailsContent.style.display = 'block';
                        detailsToggle.textContent = 'Hide Technical Details';
                    } else {
                        detailsContent.style.display = 'none';
                        detailsToggle.textContent = 'Show Technical Details';
                    }
                };
                
                const detailsContent = document.createElement('pre');
                detailsContent.id = 'details-content';
                detailsContent.className = 'mt-2 p-2 bg-light';
                detailsContent.style.display = 'none';
                detailsContent.textContent = JSON.stringify(result.details, null, 2);
                
                detailsContainer.appendChild(detailsToggle);
                detailsContainer.appendChild(detailsContent);
                this.resultContent.appendChild(detailsContainer);
            }
        } else {
            // Fallback for unexpected formats
            this.resultContent.textContent = JSON.stringify(result, null, 2);
        }
    }
    
    /**
     * Get human-readable description for transaction status code
     * @param {string} transStatus - Transaction status code
     * @returns {string} Human-readable description
     */
    getTransStatusDescription(transStatus) {
        const statusDescriptions = {
            'Y': '<span class="badge bg-success">Authenticated (Y)</span> - Authentication verification successful',
            'N': '<span class="badge bg-danger">Not Authenticated (N)</span> - Not authenticated/account not verified',
            'U': '<span class="badge bg-secondary">Unauthenticated (U)</span> - Authentication could not be performed due to technical issue',
            'A': '<span class="badge bg-warning">Attempted (A)</span> - Attempt processing performed but not authenticated',
            'C': '<span class="badge bg-warning">Challenge Required (C)</span> - Additional authentication required',
            'D': '<span class="badge bg-info">Decoupled Authentication (D)</span> - Decoupled Authentication confirmed',
            'R': '<span class="badge bg-danger">Rejected (R)</span> - Authentication rejected by issuer'
        };
        
        return statusDescriptions[transStatus] || `<span class="badge bg-secondary">Unknown (${transStatus})</span>`;
    }

    /**
     * Show error message
     * @param {string} message - Error message
     */
    showError(message) {
        this.showResult(message, true);
    }

    /**
     * Show payment form
     */
    showPaymentForm() {
        this.paymentCard.classList.remove('hidden');
        this.paymentForm.classList.remove('hidden');
        this.processingSpinner.classList.add('hidden');
        this.resultContainer.classList.add('hidden');
        this.challengeContainer.classList.add('hidden');
    }

    /**
     * Show challenge iframe
     * @param {string} challengeUrl - URL for challenge iframe
     */
    showChallenge(challengeUrl) {
        this.processingSpinner.classList.add('hidden');
        this.challengeContainer.classList.remove('hidden');

        // Create challenge iframe
        const iframe = document.createElement('iframe');
        iframe.classList.add('challenge-iframe');
        iframe.src = challengeUrl;

        // Clear previous iframe if any
        this.challengeFrameContainer.innerHTML = '';
        this.challengeFrameContainer.appendChild(iframe);
    }

    /**
     * Handle events from iframes
     * @param {MessageEvent} event - Event from iframe
     */
    async handleFrameEvent(event) {
        console.log('Received event from iframe:', event.data);

        try {
            // Check if event is an object with event property
            if (typeof event.data === 'object' && event.data.event) {
                const eventType = event.data.event;

                // Store browser info if provided
                if (event.data.param) {
                    this.transactionData.browserInfo = event.data.param;
                    console.log('Received browser info from 3DS Server');
                }

                // Process events
                if (eventType === '3DSMethodSkipped' || eventType === '3DSMethodFinished') {
                    console.log('3DS Method completed, proceeding with authentication');
                    this.transactionData.eventReceived = true;
                    // Only proceed if we have browser info
                    if (this.transactionData.browserInfo) {
                        await this.processAuthentication();
                    } else {
                        console.error('Cannot proceed: No browser info received with 3DS Method event');
                        this.showResult('Authentication failed: No browser info received from 3DS server', true);
                    }
                } else if (eventType === 'InitAuthTimedOut') {
                    console.log('Init auth timed out');
                    this.transactionData.eventReceived = true;
                    this.showResult('3DS initialization timed out. Please try again.', true);
                } else if (eventType === 'Challenge:Completed' || eventType === 'AuthResultReady') {
                    console.log('Challenge or AuthResultReady event received, updating status and checking result');

                    // Prevent duplicate processing of the same challenge completion event
                    if (!this.transactionData.challengeCompleted) {
                        this.transactionData.challengeCompleted = true;
                        await this.updateChallengeStatusAndGetResult();
                    } else {
                        console.log('Challenge completion event already processed, ignoring duplicate');
                    }
                } else {
                    console.log('Received other event:', eventType);
                }
            }
            // Handle string events (fallback)
            else if (typeof event.data === 'string') {
                if (event.data === '3DSMethodSkipped' || event.data === '3DSMethodFinished') {
                    console.log('3DS Method completed (string event), proceeding with authentication');
                    this.transactionData.eventReceived = true;
                    // For string events, we need to get browser info from the session
                    if (this.transactionData.browserInfo) {
                        await this.processAuthentication();
                    } else {
                        console.error('Cannot proceed: No browser info available with string event');
                        this.showResult('Authentication failed: No browser info available', true);
                    }
                } else if (event.data === 'Challenge:Completed' || event.data === 'AuthResultReady') {
                    // Prevent duplicate processing for string events too
                    if (!this.transactionData.challengeCompleted) {
                        this.transactionData.challengeCompleted = true;
                        console.log('Challenge completed (string event), getting result');
                        await this.updateChallengeStatusAndGetResult();
                    } else {
                        console.log('Challenge completion event already processed, ignoring duplicate');
                    }
                }
            }
        } catch (error) {
            console.error('Error handling iframe event:', error);
            this.showResult('Error processing 3DS response: ' + error.message, true);
        }
    }

    /**
     * Initialize 3DS process
     * @param {string} cardNumber - Card number
     * @param {string} amount - Amount
     * @returns {Promise<boolean>} Success status
     */
    async initialize3DS(cardNumber, amount) {
        try {
            this.showProcessing();

            // Generate transaction ID
            this.transactionData.threeDSRequestorTransID = this.generateUUID();

            // Prepare initialization data
            const initData = {
                action: 'init',
                authData: {
                    acctNumber: cardNumber
                }
            };

            console.log('Sending init request:', initData);

            // Send initialization request
            const response = await fetch('api', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(initData)
            });

            // Check HTTP status
            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
            }

            // Parse response
            const data = await response.json();
            console.log('Init response:', data);

            // Check for error
            if (data.error) {
                throw new Error(data.error);
            }

            // Validate required fields
            if (!data.threeDSServerTransID || !data.threeDSServerCallbackUrl) {
                throw new Error('Missing required response fields');
            }

            // Store important response data
            this.transactionData.threeDSServerTransID = data.threeDSServerTransID;
            this.transactionData.threeDSServerCallbackUrl = data.threeDSServerCallbackUrl;
            this.transactionData.monUrl = data.monUrl;
            this.transactionData.authUrl = data.authUrl;

            // Create monitoring iframe
            const monIframe = document.createElement('iframe');
            monIframe.id = 'monitoringIframe';
            monIframe.src = this.transactionData.monUrl;
            this.iframeContainer.appendChild(monIframe);

            // Create callback iframe for browser info collection
            const callbackIframe = document.createElement('iframe');
            callbackIframe.id = 'callbackIframe';
            callbackIframe.src = this.transactionData.threeDSServerCallbackUrl;
            this.iframeContainer.appendChild(callbackIframe);

            // If no events received within 6 seconds, only proceed if we have browser info
            setTimeout(() => {
                if (!this.transactionData.eventReceived) {
                    if (this.transactionData.browserInfo) {
                        console.log('No events received after timeout, but we have browser info - proceeding with authentication');
                        this.processAuthentication();
                    } else {
                        console.error('No events received after timeout and no browser info available');
                        this.showResult('Authentication failed: No response from 3DS server', true);
                    }
                }
            }, 6000);

            return true;
        } catch (error) {
            console.error('Initialization error:', error);
            this.showResult('Error initializing 3DS: ' + error.message, true);
            return false;
        }
    }

    /**
     * Process authentication after browserInfo is collected
     * @returns {Promise<boolean>} Success status
     */
    async processAuthentication() {
        try {
            // For 3DS, we must use the browser info received from the 3DS server
            // Never use a default value as this will cause a "Browser info mismatched" error
            if (!this.transactionData.browserInfo) {
                console.error('No browser info received from 3DS server - cannot proceed with authentication');
                this.showResult('Authentication failed: No browser info received from 3DS server', true);
                return false;
            }

            const cardNumberElement = document.getElementById('cardNumber');
            const amountElement = document.getElementById('amount');
            const expiryDateElement = document.getElementById('expiryDate');

            if (!cardNumberElement || !amountElement || !expiryDateElement) {
                throw new Error('Required form fields not found');
            }

            const cardNumber = cardNumberElement.value;
            const amount = amountElement.value;
            const expiryDate = this.formatExpiryDate(expiryDateElement.value);

            // Prepare authentication data
            const authData = {
                action: 'auth',
                acctNumber: cardNumber,
                browserInfo: this.transactionData.browserInfo,
                cardExpiryDate: expiryDate,
                purchaseAmount: this.formatAmount(amount),
                threeDSServerTransID: this.transactionData.threeDSServerTransID,
                threeDSRequestorTransID: this.transactionData.threeDSRequestorTransID
            };

            console.log('Sending auth request:', authData);

            // Send authentication request
            const response = await fetch('api', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(authData)
            });

            // Check HTTP status
            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
            }

            // Parse response
            const data = await response.json();
            console.log('Auth response:', data);

            // Check for error
            if (data.error) {
                throw new Error(data.error);
            }

            // Check transaction status
            if (data.transStatus === 'C' && data.challengeUrl) {
                // Challenge required
                this.showChallenge(data.challengeUrl);
            } else if (data.transStatus === 'D') {
                // Decoupled Authentication required
                this.showResult({
                    status: 'decoupled',
                    message: 'Decoupled Authentication Required - Please verify on your device',
                    transStatus: data.transStatus,
                    details: data
                });
            } else if (data.transStatus === 'Y') {
                // Authentication successful
                this.showResult({
                    status: 'success',
                    message: 'Payment Authenticated Successfully',
                    transStatus: data.transStatus,
                    details: data
                });
            } else if (data.transStatus === 'N') {
                // Not authenticated
                this.showResult({
                    status: 'failed',
                    message: 'Authentication Failed - Not Authenticated',
                    transStatus: data.transStatus,
                    details: data
                });
            } else if (data.transStatus === 'U') {
                // Unauthenticated due to technical issue
                this.showResult({
                    status: 'error',
                    message: 'Authentication Error - Technical Issue',
                    transStatus: data.transStatus,
                    details: data
                });
            } else if (data.transStatus === 'A') {
                // Attempted but not verified
                this.showResult({
                    status: 'partial',
                    message: 'Authentication Attempted but Not Verified',
                    transStatus: data.transStatus,
                    details: data
                });
            } else if (data.transStatus === 'R') {
                // Rejected by issuer
                this.showResult({
                    status: 'rejected',
                    message: 'Authentication Rejected by Issuer',
                    transStatus: data.transStatus,
                    details: data
                });
            } else {
                // Other status
                this.showResult({
                    status: 'complete',
                    message: 'Authentication Completed',
                    transStatus: data.transStatus,
                    details: data
                });
            }

            return true;
        } catch (error) {
            console.error('Authentication error:', error);
            this.showResult('Error during authentication: ' + error.message, true);
            return false;
        }
    }

    /**
     * Update challenge status and get authentication result
     * @returns {Promise<boolean>} Success status
     */
    async updateChallengeStatusAndGetResult() {
        try {
            if (!this.transactionData.threeDSServerTransID) {
                throw new Error('Missing transaction ID');
            }

            // 1. Update challenge status
            const statusResponse = await fetch('api', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'updateChallengeStatus',
                    threeDSServerTransID: this.transactionData.threeDSServerTransID,
                    status: '01' // or 'CReqNotSent', as per your use case
                })
            });

            // Parse the status response
            let statusData;
            try {
                statusData = await statusResponse.json();
            } catch (e) {
                console.warn('Could not parse status response as JSON, continuing anyway');
            }

            // If we got a successful response or the transaction was already completed,
            // proceed to get the auth result
            if (statusResponse.ok ||
                (statusData && statusData.message === 'Transaction was already completed')) {
                // 2. Now get the result
                await this.getAuthenticationResult();
                return true;
            } else {
                // If there's an error in the status update that's not "already completed", 
                // try to get the result anyway
                console.warn(`Status update issue (${statusResponse.status}), trying to get auth result anyway`);

                try {
                    await this.getAuthenticationResult();
                    return true;
                } catch (resultError) {
                    throw new Error(`Status update failed and couldn't get result: ${resultError.message}`);
                }
            }
        } catch (error) {
            console.error('Error updating challenge status or getting result:', error);
            this.showResult('Error updating challenge status or getting result: ' + error.message, true);
            return false;
        }
    }

    /**
     * Get authentication result after challenge
     * @returns {Promise<boolean>} Success status
     */
    async getAuthenticationResult() {
        try {
            if (!this.transactionData.threeDSServerTransID) {
                throw new Error('Missing transaction ID');
            }

            // Prepare request data
            const resultData = {
                action: 'getAuthResult',
                threeDSServerTransID: this.transactionData.threeDSServerTransID
            };

            console.log('Getting auth result:', resultData);

            // Send request
            const response = await fetch('api', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(resultData)
            });

            // Check HTTP status
            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
            }

            // Parse response
            const data = await response.json();
            console.log('Auth result response:', data);

            // Check for error
            if (data.error) {
                throw new Error(data.error);
            }

            // Make sure to hide both processing spinner and challenge container
            this.processingSpinner.classList.add('hidden');
            this.challengeContainer.classList.add('hidden');

            // Get transaction status and map to appropriate messages
            const transStatus = data.transStatus || 'Unknown';
            
            // Map status to appropriate title
            let statusInfo = {
                status: data.status || 'completed',
                message: data.message || 'Authentication Completed',
                transStatus: transStatus,
                details: data
            };
            
            // Display the formatted result
            this.showResult(statusInfo);

            return true;
        } catch (error) {
            console.error('Get auth result error:', error);
            this.showResult('Error getting authentication result: ' + error.message, true);
            return false;
        }
    }
}

// Initialize when the DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new ThreeDSPayment();
}); 