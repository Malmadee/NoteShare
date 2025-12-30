/**
 * Newsletter Subscription Handler
 * Handles the subscribe form in the footer
 */

document.addEventListener('DOMContentLoaded', function() {
    const subscribeForm = document.getElementById('mc-form');
    
    if (subscribeForm) {
        subscribeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const emailInput = document.getElementById('mce-EMAIL');
            const email = emailInput.value.trim();
            const statusDiv = document.querySelector('.mc-status');
            
            // Reset status message
            if (statusDiv) {
                statusDiv.innerHTML = '';
                statusDiv.className = 'mc-status';
            }
            
            // Validate email
            if (!email) {
                showSubscribeStatus('Please enter your email address', 'error', statusDiv);
                return;
            }
            
            if (!isValidEmail(email)) {
                showSubscribeStatus('Please enter a valid email address', 'error', statusDiv);
                return;
            }
            
            // Disable submit button
            const submitBtn = subscribeForm.querySelector('input[type="submit"]');
            const originalText = submitBtn.value;
            submitBtn.disabled = true;
            submitBtn.value = 'Sending...';
            
            // Send subscription request
            const formData = new FormData();
            formData.append('email', email);
            
            // Use absolute path to API endpoint
            const apiUrl = '/NoteShare/api/subscribe-email.php';
            
            fetch(apiUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showSubscribeStatus('✓ ' + data.message, 'success', statusDiv);
                    emailInput.value = '';
                    
                    // Reset button after success
                    submitBtn.disabled = false;
                    submitBtn.value = originalText;
                } else {
                    showSubscribeStatus('✗ ' + (data.message || 'Subscription failed'), 'error', statusDiv);
                    submitBtn.disabled = false;
                    submitBtn.value = originalText;
                }
            })
            .catch(error => {
                console.error('Subscription error:', error);
                showSubscribeStatus('✗ ' + error.message, 'error', statusDiv);
                submitBtn.disabled = false;
                submitBtn.value = originalText;
            });
        });
    }
});

/**
 * Display status message for subscription
 */
function showSubscribeStatus(message, type, statusDiv) {
    if (!statusDiv) return;
    
    // Remove the checkmark or cross symbol if present
    const cleanMessage = message.replace(/^[✓✗]\s+/, '');
    
    statusDiv.innerHTML = cleanMessage;
    statusDiv.className = 'mc-status mc-status-' + type;
    
    // Auto-hide success messages after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            statusDiv.innerHTML = '';
            statusDiv.className = 'mc-status';
        }, 5000);
    }
}

/**
 * Validate email format
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}
