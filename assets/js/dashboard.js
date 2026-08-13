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

                if (confirm(`Are you sure you want to delete "${propertyTitle}"? This action cannot be undone.`)) {
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

    // Handle inquiry status update
    const updateInquiryStatusBtns = document.querySelectorAll('.update-inquiry-status');

    if (updateInquiryStatusBtns.length) {
        updateInquiryStatusBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();

                const inquiryId = this.getAttribute('data-inquiry-id');
                const status = this.getAttribute('data-status');

                // Send update request
                fetch('api/inquiries.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'update_status',
                        inquiry_id: inquiryId,
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error updating inquiry status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the inquiry.');
                });
            });
        });
    }

    // Reply to inquiry
    const replyInquiryForms = document.querySelectorAll('.reply-inquiry-form');

    if (replyInquiryForms.length) {
        replyInquiryForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const inquiryId = this.querySelector('[name="inquiry_id"]').value;
                const message = this.querySelector('[name="reply_message"]').value;

                // Validate message
                if (!message.trim()) {
                    alert('Please enter a reply message.');
                    return;
                }

                // Send reply
                fetch('api/inquiries.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        inquiry_id: inquiryId,
                        message: message,
                        action: 'reply'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Show success message and reset form
                        alert(data.message || 'Reply sent successfully.');
                        form.reset();

                        // Close reply form if modal
                        const modal = form.closest('.modal');
                        if (modal) {
                            modal.style.display = 'none';
                        }

                        // Update inquiry status if available
                        const statusBadge = document.querySelector(`.inquiry-status[data-inquiry-id="${inquiryId}"]`);
                        if (statusBadge) {
                            statusBadge.textContent = 'Replied';
                            statusBadge.className = 'inquiry-status badge badge-success';
                        }
                    } else {
                        alert(data.message || 'An error occurred');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while sending your reply.');
                });
            });
        });
    }

    // Display reply form when clicking reply button
    const replyButtons = document.querySelectorAll('.show-reply-form');

    if (replyButtons.length) {
        replyButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();

                const inquiryId = this.getAttribute('data-inquiry-id');
                const replyForm = document.getElementById(`reply-form-${inquiryId}`);

                if (replyForm) {
                    // Toggle the form visibility
                    if (replyForm.style.display === 'none' || !replyForm.style.display) {
                        replyForm.style.display = 'block';
                        this.textContent = 'Cancel Reply';
                    } else {
                        replyForm.style.display = 'none';
                        this.textContent = 'Reply';
                    }
                }
            });
        });
    }

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