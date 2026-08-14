// Dashboard functionality for Real Estate Listing System

document.addEventListener('DOMContentLoaded', function() {
    // Handle property delete
    const deletePropertyBtns = document.querySelectorAll('.delete-property-btn');

    if (deletePropertyBtns.length) {
        deletePropertyBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();

                const propertyId = this.getAttribute('data-property-id');
                const propertyTitle = this.getAttribute('data-property-title');

                if (confirm(`Are you sure you want to delete "${propertyTitle}"? An administrator will review the request before it is permanently deleted.`)) {
                    // Send delete request
                    fetch('api/properties.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            property_id: propertyId,
                            action: 'request_delete'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert(data.message);
                            window.location.reload();
                        } else {
                            alert(data.message || 'An error occurred');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the property.');
                    });
                }
            });
        });
    }

    // Inquiry status/reply controls use event delegation so they remain responsive
    // even after a dashboard section is refreshed or updated dynamically.
    document.addEventListener('click', function(e) {
        const statusBtn = e.target.closest('.update-inquiry-status');
        if (statusBtn) {
            e.preventDefault();
            if (statusBtn.disabled) return;
            const inquiryId = statusBtn.getAttribute('data-inquiry-id');
            const status = statusBtn.getAttribute('data-status');
            statusBtn.disabled = true;
            const originalText = statusBtn.textContent;
            statusBtn.textContent = 'Updating...';

            fetch('api/inquiries.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'update_status', inquiry_id: inquiryId, status: status})
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') window.location.reload();
                else throw new Error(data.message || 'Error updating inquiry status');
            })
            .catch(error => {
                console.error(error);
                statusBtn.disabled = false;
                statusBtn.textContent = originalText;
                alert(error.message || 'An error occurred while updating the inquiry.');
            });
            return;
        }

        const replyBtn = e.target.closest('.show-reply-form');
        if (replyBtn) {
            e.preventDefault();
            const inquiryId = replyBtn.getAttribute('data-inquiry-id');
            const replyForm = document.getElementById(`reply-form-${inquiryId}`);
            if (!replyForm) return;
            const hidden = replyForm.style.display === 'none' || !replyForm.style.display;
            replyForm.style.display = hidden ? 'block' : 'none';
            replyBtn.textContent = hidden ? 'Cancel Reply' : (replyBtn.dataset.defaultText || 'Reply');
            if (hidden) {
                const textarea = replyForm.querySelector('textarea[name="reply_message"]');
                if (textarea) textarea.focus();
            }
        }
    });

    document.addEventListener('submit', function(e) {
        const form = e.target.closest('.reply-inquiry-form');
        if (!form) return;
        e.preventDefault();
        if (form.dataset.submitting === '1') return;

        const inquiryId = form.querySelector('[name="inquiry_id"]')?.value;
        const textarea = form.querySelector('[name="reply_message"]');
        const message = textarea ? textarea.value.trim() : '';
        if (!inquiryId || !message) {
            alert('Please enter a reply message.');
            return;
        }

        form.dataset.submitting = '1';
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.textContent : 'Send Reply';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';
        }

        fetch('api/inquiries.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'reply', inquiry_id: inquiryId, message: message})
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.reload();
                return;
            }
            throw new Error(data.message || 'An error occurred while sending your reply.');
        })
        .catch(error => {
            console.error(error);
            form.dataset.submitting = '0';
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
            alert(error.message || 'An error occurred while sending your reply.');
        });
    });

    // Toggle featured property status
    const toggleFeaturedBtns = document.querySelectorAll('.toggle-featured-btn');

    if (toggleFeaturedBtns.length) {
        toggleFeaturedBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();

                const propertyId = this.getAttribute('data-property-id');
                const featured = this.getAttribute('data-featured') === '1';

                // Send toggle request
                fetch('api/properties.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        property_id: propertyId,
                        featured: !featured,
                        action: 'toggle_featured'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Update button
                        this.setAttribute('data-featured', featured ? '0' : '1');

                        // Update button text and icon
                        const icon = this.querySelector('i');
                        if (featured) {
                            this.innerHTML = '<i class="far fa-star"></i> Set Featured';
                        } else {
                            this.innerHTML = '<i class="fas fa-star"></i> Remove Featured';
                        }
                    } else {
                        alert(data.message || 'An error occurred');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the property.');
                });
            });
        });
    }
});