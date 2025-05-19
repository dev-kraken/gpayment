/**
 * Input Validation Module
 * Handles credit card number and expiry date masking and validation
 * 
 * @author DevKraken <soman@devkraken.com>
 * @copyright 2025 DevKraken
 * @license Proprietary
 */

/**
 * Validate credit card number using Luhn algorithm
 * @param {string} cardNumber - Card number without spaces
 * @returns {boolean} Is card number valid
 */
function validateCardNumber(cardNumber) {
    if (!cardNumber || cardNumber.length < 13) return false;
    
    // Luhn algorithm
    let sum = 0;
    let double = false;
    
    // Starting from the right
    for (let i = cardNumber.length - 1; i >= 0; i--) {
        let digit = parseInt(cardNumber[i]);
        
        if (double) {
            digit *= 2;
            if (digit > 9) {
                digit -= 9;
            }
        }
        
        sum += digit;
        double = !double;
    }
    
    return (sum % 10) === 0;
}

/**
 * Validate expiry date
 * @param {string} expiryDate - Expiry date in MM/YY format
 * @returns {boolean} Is expiry date valid and not expired
 */
function validateExpiryDate(expiryDate) {
    // Check format
    if (!expiryDate || !/^\d{2}\/\d{2}$/.test(expiryDate)) {
        return false;
    }
    
    const parts = expiryDate.split('/');
    let month = parseInt(parts[0]);
    let year = parseInt('20' + parts[1]); // Convert to 4-digit year
    
    // Get current date
    const now = new Date();
    const currentMonth = now.getMonth() + 1; // getMonth() returns 0-11
    const currentYear = now.getFullYear();
    
    // Validate month (1-12)
    if (month < 1 || month > 12) {
        return false;
    }
    
    // Check if card is expired
    if (year < currentYear || (year === currentYear && month < currentMonth)) {
        return false;
    }
    
    return true;
}

/**
 * Update input styling based on validation
 * @param {HTMLInputElement} input - The input element
 * @param {boolean} isValid - Whether the input value is valid
 */
function updateValidationStyle(input, isValid) {
    if (input.value.length === 0) {
        // Reset style when empty
        input.classList.remove('is-valid', 'is-invalid');
    } else if (isValid) {
        input.classList.add('is-valid');
        input.classList.remove('is-invalid');
    } else {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
    }
}

// Make validation functions globally available
window.validateCardNumber = validateCardNumber;
window.validateExpiryDate = validateExpiryDate;

document.addEventListener('DOMContentLoaded', () => {
    // Get form elements
    const cardNumberInput = document.getElementById('cardNumber');
    const expiryDateInput = document.getElementById('expiryDate');
    const cvvInput = document.getElementById('cvv');

    // Apply input masks and validation
    if (cardNumberInput) setupCardNumberInput(cardNumberInput);
    if (expiryDateInput) setupExpiryDateInput(expiryDateInput);
    if (cvvInput) setupCVVInput(cvvInput);
});

/**
 * Set up credit card number input with masking and validation
 * @param {HTMLInputElement} input - The credit card input element
 */
function setupCardNumberInput(input) {
    // Maximum length for card number (16 digits + 3 spaces)
    const maxLength = 19;
    
    input.addEventListener('input', (e) => {
        // Get input value and remove all non-digits
        let value = e.target.value.replace(/\D/g, '');
        
        // Add spaces after every 4 digits
        let formattedValue = '';
        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) {
                formattedValue += ' ';
            }
            formattedValue += value[i];
        }
        
        // Enforce max length
        if (formattedValue.length > maxLength) {
            formattedValue = formattedValue.substring(0, maxLength);
        }
        
        // Update the input value
        e.target.value = formattedValue;
        
        // Validate card number using Luhn algorithm
        const isValid = validateCardNumber(value);
        updateValidationStyle(input, isValid && value.length >= 13);
    });
}

/**
 * Set up expiry date input with MM/YY format and validation
 * @param {HTMLInputElement} input - The expiry date input element
 */
function setupExpiryDateInput(input) {
    input.addEventListener('input', (e) => {
        // Get input value and remove non-digits
        let value = e.target.value.replace(/\D/g, '');
        
        // Format as MM/YY
        if (value.length > 0) {
            // Extract month and year
            let month = value.substring(0, 2);
            let year = value.substring(2, 4);
            
            // Validate month (1-12)
            if (month.length === 1 && parseInt(month) > 1) {
                month = '0' + month;
            } else if (parseInt(month) > 12) {
                month = '12';
            }
            
            // Format the value
            if (value.length <= 2) {
                value = month;
            } else {
                value = month + '/' + year;
            }
        }
        
        // Update the input value
        e.target.value = value;
        
        // Validate expiry date
        const isValid = validateExpiryDate(value);
        updateValidationStyle(input, isValid);
    });
}

/**
 * Set up CVV input with validation
 * @param {HTMLInputElement} input - The CVV input element
 */
function setupCVVInput(input) {
    input.addEventListener('input', (e) => {
        // Get input value and remove non-digits
        let value = e.target.value.replace(/\D/g, '');
        
        // Enforce max length (usually 3 or 4 digits)
        if (value.length > 4) {
            value = value.substring(0, 4);
        }
        
        // Update the input value
        e.target.value = value;
        
        // Validate CVV (3-4 digits)
        const isValid = value.length >= 3;
        updateValidationStyle(input, isValid);
    });
} 