// assets/js/support.js

function supportInit() {
    console.debug('[support.js] supportInit');
    document.body.style.border = '';
    const form = document.getElementById('support-form');
    if (!form) {
        console.debug('[support.js] support form not found');
        return;
    }
    console.debug('[support.js] support form found, attaching submit handler');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const name = document.getElementById('support-name').value.trim();
        const email = document.getElementById('support-email').value.trim();
        const message = document.getElementById('support-message').value.trim();
        const submitBtn = document.getElementById('support-submit');

        if (!name || !email || !message) {
            showToast('Please fill all fields', 'error');
            return;
        }

        submitBtn.disabled = true;
        const originalText = submitBtn.innerText;
        submitBtn.innerText = 'Sending...';

        const fd = new FormData();
        fd.append('name', name);
        fd.append('email', email);
        fd.append('message', message);

        fetch('/NoteShare/api/send-support-message.php', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.success) {
                showToast('The message has been sent.', 'success');
                form.reset();
            } else {
                showToast((data && data.message) ? data.message : 'Failed to send message', 'error');
            }
        })
        .catch(err => {
            console.error('Support submit error', err);
            showToast('An error occurred. Please try again.', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        });
    });
}

function showToast(message, type = 'info') {
    console.debug('[support.js] showToast()', message, type);
    let container = document.querySelector('.upload-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'upload-toast-container';
        // append container and ensure basic positioning/styles even if CSS not loaded
        container.style.position = 'fixed';
        container.style.left = '16px';
        container.style.bottom = '16px';
        container.style.zIndex = '99999';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '8px';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'upload-toast toast-small ' + (type === 'error' ? 'error' : (type === 'success' ? 'success' : 'info'));
    toast.textContent = message;
    // Ensure toast has visible fallback styles if uploads.css isn't loaded
    toast.style.background = '#6b6b6b';
    toast.style.color = '#ffffff';
    toast.style.padding = '12px 18px';
    toast.style.borderRadius = '8px';
    toast.style.boxShadow = '0 6px 18px rgba(0,0,0,0.25)';
    toast.style.minWidth = '160px';
    toast.style.maxWidth = '320px';
    toast.style.fontFamily = "'Open Sans', sans-serif";
    toast.style.fontSize = '0.95rem';
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(8px)';
    toast.style.transition = 'opacity 0.22s ease, transform 0.22s ease';
    container.appendChild(toast);
    console.debug('[support.js] toast element appended');

    // show
    setTimeout(() => toast.classList.add('show'), 10);

    // remove after 5s
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => container.removeChild(toast), 300);
    }, 5000);
}

// Expose a helper so you can run `testSupportToast()` in the browser console to verify the toast.
window.testSupportToast = function(msg) {
    try {
        showToast(msg || 'The message has been sent.', 'success');
        console.debug('[support.js] testSupportToast invoked');
    } catch (e) {
        console.error('[support.js] testSupportToast error', e);
    }
};

// Always initialize, even if DOMContentLoaded already fired
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', supportInit);
} else {
    supportInit();
}
