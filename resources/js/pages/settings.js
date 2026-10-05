// Settings specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action*="settings"]');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            // Form validation is handled by Laravel
            // Additional client-side validation can be added here if needed
            console.log('Settings form submitted');
        });
    }
    
    console.log('Settings page loaded');
});

