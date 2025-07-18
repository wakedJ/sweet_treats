
        // Generate a unique token for this form submission
        const submissionToken = 'order_' + Math.random().toString(36).substr(2, 9) + '_' + new Date().getTime();
        document.getElementById('submission_token').value = submissionToken;
        
        // When the page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Set up date restrictions for pickup time
            setupPickupDateLimits();
            
            // Get the order type from the hidden input
            const orderType = document.getElementById('order_type').value;
            
            // Ensure form fields validation is set up properly based on delivery method
            updateRequiredFields(orderType);
        });
        
        function updateRequiredFields(orderType) {
            // Get delivery form fields
            const deliveryFields = ['firstName', 'lastName', 'address', 'city'];
            
            // Get pickup form fields
            const pickupFields = ['pickup_firstName', 'pickup_lastName', 'pickup_time'];
            
            if (orderType === 'delivery') {
                // Make delivery fields required
                deliveryFields.forEach(field => {
                    const element = document.getElementById(field);
                    if (element) element.required = true;
                });
                
                // Make pickup fields not required
                pickupFields.forEach(field => {
                    const element = document.getElementById(field);
                    if (element) element.required = false;
                });
                
                // Show delivery section and hide pickup
                document.getElementById('delivery-section').classList.remove('hidden');
                document.getElementById('pickup-section').classList.add('hidden');
            } else {
                // Make pickup fields required
                pickupFields.forEach(field => {
                    const element = document.getElementById(field);
                    if (element) element.required = true;
                });
                
                // Make delivery fields not required
                deliveryFields.forEach(field => {
                    const element = document.getElementById(field);
                    if (element) element.required = false;
                });
                
                // Show pickup section and hide delivery
                document.getElementById('pickup-section').classList.remove('hidden');
                document.getElementById('delivery-section').classList.add('hidden');
            }
        }
        
        function setupPickupDateLimits() {
            const pickupTimeInput = document.getElementById('pickup_time');
            if (pickupTimeInput) {
                // Minimum time (3 hours from now)
                const minTime = new Date();
                minTime.setHours(minTime.getHours() + 3);
                
                // Maximum time (1 week from now)
                const maxTime = new Date();
                maxTime.setDate(maxTime.getDate() + 7);
                
                // Format dates for the input
                const formatDateTime = (date) => {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    const hours = String(date.getHours()).padStart(2, '0');
                    const minutes = String(date.getMinutes()).padStart(2, '0');
                    
                    return `${year}-${month}-${day}T${hours}:${minutes}`;
                };
                
                // Set min and max attributes
                pickupTimeInput.min = formatDateTime(minTime);
                pickupTimeInput.max = formatDateTime(maxTime);
                
                // Set default value to tomorrow at noon
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                tomorrow.setHours(12, 0, 0, 0);
                
                pickupTimeInput.value = formatDateTime(tomorrow);
            }
        }
        
        // Flag to track if form has been submitted already
        let formSubmitted = false;
        
        // Handle form submission via AJAX
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Prevent double submissions
            if (formSubmitted) {
                console.log('Form already submitted, ignoring duplicate submission');
                return false;
            }
            
            // Mark as submitted
            formSubmitted = true;
            
            const form = this;
            const formData = new FormData(form);
            
            // Create an animated loading state for the button
            const submitBtn = form.querySelector('.submit-btn');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;
            
            fetch('checkout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Try to parse response as JSON, but handle if it's not valid JSON
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        // If the response is not valid JSON, show it as a raw error
                        console.error('Invalid JSON response:', text);
                        throw new Error('Server returned invalid response. See console for details.');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    // Show confirmation modal
                    const email = document.getElementById('email').value;
                    document.getElementById('confirmation-email').textContent = email;
                    document.getElementById('order-number').textContent = 'SWEET-' + data.order_id;
                    document.getElementById('confirmation-modal').style.display = 'flex';
                } else {
                    console.error('Order error:', data);
                    alert('Error: ' + (data.error || 'Failed to process your order. Please try again.'));
                    // Restore button
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Request error:', error);
                alert('An error occurred: ' + error.message);
                // Restore button
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
            });
        });
        
        function closeModal() {
            document.getElementById('confirmation-modal').style.display = 'none';
            window.location.href = 'shop.php';
        }