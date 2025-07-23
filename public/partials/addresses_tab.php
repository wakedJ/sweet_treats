<div class="tab-content active" id="addressesTab">
    <h3>My Addresses</h3>
    
    <div class="addresses-container">
        <div class="address-list">
            <?php if (empty($user_addresses)): ?>
                <div class="no-addresses">
                    <i class="fas fa-map-marker-alt" style="font-size: 3rem; color: #ffd1dc; margin-bottom: 20px;"></i>
                    <p>You haven't added any addresses yet.</p>
                    <button id="addNewAddressBtn" class="account-btn">Add Your First Address</button>
                </div>
            <?php else: ?>
                <div class="address-action">
                    <button id="addNewAddressBtn" class="account-btn"><i class="fas fa-plus"></i> Add New Address</button>
                </div>
                
                <?php foreach ($user_addresses as $index => $address): ?>
                <div class="address-card <?php echo ($address['is_default'] == 1) ? 'default-address' : ''; ?>">
                    <?php if ($address['is_default'] == 1): ?>
                        <div class="default-badge">Default</div>
                    <?php endif; ?>
                    
                    <div class="address-content">
                        <p class="address-line"><?php echo htmlspecialchars($address['street_address']); ?></p>
                        <p class="address-line">
                            <?php 
                                echo htmlspecialchars($address['city']);
                                if (!empty($address['state'])) {
                                    echo ', ' . htmlspecialchars($address['state']);
                                }
                                if (!empty($address['postal_code'])) {
                                    echo ' ' . htmlspecialchars($address['postal_code']);
                                }
                            ?>
                        </p>
                        <p class="address-line"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($address['phone_number']); ?></p>
                    </div>
                    
                    <div class="address-actions">
                        <button class="delete-address-btn" data-address-id="<?php echo $address['id']; ?>">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="address-form-container" id="addressFormContainer" style="display: none;">
            <h4 id="addressFormTitle">Add New Address</h4>
            <form method="post" action="account.php" class="address-form" id="addressForm">
                <input type="hidden" name="add_address" value="1">
                
                <div class="form-group">
                    <label for="street_address">Street Address <span class="required">*</span></label>
                    <input type="text" id="street_address" name="street_address" class="form-control" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="city">City <span class="required">*</span></label>
                            <input type="text" id="city" name="city" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="state">State/Province (optional)</label>
                            <input type="text" id="state" name="state" class="form-control">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="postal_code">Postal/ZIP Code (optional)</label>
                            <input type="text" id="postal_code" name="postal_code" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone_number">Phone Number <span class="required">*</span></label>
                            <input type="tel" id="phone_number" name="phone_number" class="form-control" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-check mb-3">
                    <input type="checkbox" id="is_default" name="is_default" class="form-check-input">
                    <label for="is_default" class="form-check-label">Set as default address</label>
                </div>
                
                <div class="form-actions">
                    <button type="button" id="cancelAddressBtn" class="account-btn secondary">Cancel</button>
                    <button type="submit" id="saveAddressBtn" class="account-btn">Save Address</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Form (hidden) -->
    <form method="post" action="account.php" id="deleteAddressForm" style="display: none;">
        <input type="hidden" name="address_id" id="delete_address_id" value="">
        <input type="hidden" name="delete_address" value="1">
    </form>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteAddressModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this address?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Simplified Address management functionality - Add & Delete only
document.addEventListener('DOMContentLoaded', function() {
    console.log('Address management script loaded');
    
    // Get all necessary DOM elements
    const addressFormContainer = document.getElementById('addressFormContainer');
    const addressForm = document.getElementById('addressForm');
    const addNewAddressBtn = document.getElementById('addNewAddressBtn');
    const cancelAddressBtn = document.getElementById('cancelAddressBtn');
    const deleteAddressModal = document.getElementById('deleteAddressModal');
    const deleteAddressForm = document.getElementById('deleteAddressForm');
    
    // Initialize Bootstrap modal for delete confirmation if Bootstrap is loaded
    let deleteModal = null;
    if (deleteAddressModal && typeof bootstrap !== 'undefined') {
        deleteModal = new bootstrap.Modal(deleteAddressModal);
    }
    
    // Handle Add New Address button click
    if (addNewAddressBtn) {
        addNewAddressBtn.addEventListener('click', function() {
            console.log('Add new address button clicked');
            showAddressForm();
        });
    }
    
    // Function to show and reset the address form for adding
    function showAddressForm() {
        if (addressForm) {
            // Reset form to clear any previous values
            addressForm.reset();
            
            // Show form container
            if (addressFormContainer) {
                addressFormContainer.style.display = 'block';
                // Scroll to form
                addressFormContainer.scrollIntoView({ behavior: 'smooth' });
            }
        }
    }
    
    // Handle Cancel button click
    if (cancelAddressBtn) {
        cancelAddressBtn.addEventListener('click', function() {
            console.log('Cancel button clicked');
            if (addressFormContainer) {
                addressFormContainer.style.display = 'none';
            }
        });
    }
    
    // Handle Delete buttons
    const deleteButtons = document.querySelectorAll('.delete-address-btn');
    console.log('Delete buttons found:', deleteButtons.length);
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Delete button clicked');
            
            const addressId = this.getAttribute('data-address-id');
            console.log('Address ID to delete:', addressId);
            
            if (!addressId) {
                console.error('No address ID found');
                alert('Error: Could not identify address to delete');
                return;
            }
            
            // Set the address ID in the delete form
            const deleteIdInput = document.getElementById('delete_address_id');
            if (deleteIdInput) {
                deleteIdInput.value = addressId;
                console.log('Set address ID in form:', addressId);
            } else {
                console.error('Delete ID input not found');
                return;
            }
            
            // Show the delete confirmation modal
            if (deleteModal) {
                deleteModal.show();
            } else if (deleteAddressModal) {
                // Fallback if Bootstrap is not available
                deleteAddressModal.style.display = 'block';
                deleteAddressModal.classList.add('show');
            } else {
                // Final fallback - simple confirm dialog
                if (confirm('Are you sure you want to delete this address?')) {
                    submitDeleteForm(addressId);
                }
            }
        });
    });
    
    // Handle confirm delete button in modal
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            console.log('Confirm delete button clicked');
            
            const addressId = document.getElementById('delete_address_id').value;
            console.log('Confirming delete for address ID:', addressId);
            
            if (!addressId) {
                console.error('No address ID set for deletion');
                alert('Error: No address selected for deletion');
                return;
            }
            
            // Show loading state
            this.textContent = 'Deleting...';
            this.disabled = true;
            
            // Submit the delete form
            submitDeleteForm(addressId);
        });
    }
    
    // Function to submit delete form
    function submitDeleteForm(addressId) {
        console.log('Submitting delete form for address:', addressId);
        
        if (deleteAddressForm) {
            // Make sure the address ID is set
            const deleteIdInput = document.getElementById('delete_address_id');
            if (deleteIdInput) {
                deleteIdInput.value = addressId;
            }
            
            // Submit the form
            deleteAddressForm.submit();
        } else {
            console.error('Delete form not found');
            // Fallback: create and submit form
            const form = document.createElement('form');
            form.method = 'post';
            form.action = 'account.php';
            
            const addressIdInput = document.createElement('input');
            addressIdInput.type = 'hidden';
            addressIdInput.name = 'address_id';
            addressIdInput.value = addressId;
            
            const deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'delete_address';
            deleteInput.value = '1';
            
            form.appendChild(addressIdInput);
            form.appendChild(deleteInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // Handle form submission validation
    if (addressForm) {
        addressForm.addEventListener('submit', function(e) {
            console.log('Address form submitted');
            
            // Basic validation
            const streetAddress = document.getElementById('street_address').value.trim();
            const city = document.getElementById('city').value.trim();
            const phoneNumber = document.getElementById('phone_number').value.trim();
            
            if (!streetAddress || !city || !phoneNumber) {
                e.preventDefault();
                alert('Please fill out all required fields (Street Address, City, and Phone Number).');
                return false;
            }
            
            // Show loading state on save button
            const saveButton = document.getElementById('saveAddressBtn');
            if (saveButton) {
                saveButton.textContent = 'Saving...';
                saveButton.disabled = true;
            }
            
            return true;
        });
    }
    
    // Handle delete form submission
    if (deleteAddressForm) {
        deleteAddressForm.addEventListener('submit', function(e) {
            console.log('Delete form being submitted');
            
            const addressId = document.getElementById('delete_address_id').value;
            console.log('Form submission - Address ID:', addressId);
            
            if (!addressId) {
                e.preventDefault();
                console.error('No address ID set in form');
                alert('Error: No address selected for deletion');
                return false;
            }
            
            console.log('Form will be submitted to account.php');
            return true;
        });
    }
    
    // Handle modal close buttons
    if (deleteAddressModal) {
        const closeButtons = deleteAddressModal.querySelectorAll('.btn-close, .btn-secondary');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                if (deleteModal) {
                    deleteModal.hide();
                } else {
                    deleteAddressModal.style.display = 'none';
                    deleteAddressModal.classList.remove('show');
                }
            });
        });
    }
    
    // Handle phone number formatting
    const phoneInput = document.getElementById('phone_number');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            this.value = formatPhoneNumber(this.value);
        });
    }
    
    /**
     * Format phone number as user types: (XXX) XXX-XXXX
     */
    function formatPhoneNumber(value) {
        // Strip all non-numeric characters
        const phoneNumber = value.replace(/\D/g, '');
        
        // Format based on length
        if (phoneNumber.length === 0) {
            return '';
        } else if (phoneNumber.length <= 3) {
            return phoneNumber;
        } else if (phoneNumber.length <= 6) {
            return `(${phoneNumber.slice(0, 3)}) ${phoneNumber.slice(3)}`;
        } else {
            return `(${phoneNumber.slice(0, 3)}) ${phoneNumber.slice(3, 6)}-${phoneNumber.slice(6, 10)}`;
        }
    }
});
</script>