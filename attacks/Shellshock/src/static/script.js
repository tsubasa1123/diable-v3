// Copy to clipboard function
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Show success feedback
        showToast('✓ Copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
        showToast('✗ Failed to copy');
    });
}

// Toast notification
function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: linear-gradient(135deg, #00d9ff 0%, #db2777 100%);
        color: white;
        padding: 12px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        z-index: 1000;
        animation: slideInToast 0.3s ease-out;
        box-shadow: 0 8px 24px rgba(0, 217, 255, 0.3);
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutToast 0.3s ease-out forwards';
        setTimeout(() => toast.remove(), 300);
    }, 2000);
}

// Add toast animations if not already in CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInToast {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideOutToast {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100px);
        }
    }
`;
document.head.appendChild(style);

// Smooth scroll behavior
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});

// Add hover effects to file rows
document.querySelectorAll('.file-row').forEach(row => {
    row.addEventListener('mouseenter', () => {
        row.style.transform = 'translateX(4px)';
    });
    row.addEventListener('mouseleave', () => {
        row.style.transform = 'translateX(0)';
    });
});

// Add loading animation to form submission
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        const button = this.querySelector('.btn-primary');
        if (button) {
            button.disabled = true;
            button.style.opacity = '0.7';
        }
    });
});

// Add smooth animation on page load
window.addEventListener('load', () => {
    document.body.style.overflow = 'visible';
});

// Highlight code blocks on copy
document.querySelectorAll('.payload-code').forEach(code => {
    code.addEventListener('click', () => {
        const text = code.textContent;
        copyToClipboard(text);
    });
});
