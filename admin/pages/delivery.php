<h3>Delivery Rules</h3>
                <div class="status-message success" style="display: none;">Delivery rules updated successfully!</div>
                <div class="status-message error" style="display: none;">Error updating delivery rules. Please try again.</div>
                
                <form id="delivery-form">
                    <div class="form-row">
                        <label for="min-order-amount">Minimum Order for Free Delivery ($)</label>
                        <input type="number" id="min-order-amount" placeholder="Enter amount for free delivery (e.g., 50)" required min="0" step="0.01">
                        <div class="error-message" id="min-order-error"></div>
                    </div>
                    
                    <div class="form-row">
                        <label for="delivery-fee">Standard Delivery Fee ($)</label>
                        <input type="number" id="delivery-fee" placeholder="Enter delivery fee" required min="0" max="50" step="0.01">
                        <div class="error-message" id="delivery-fee-error"></div>
                    </div>
                    
                    <div class="form-section">
                        <h4>Bulk Order Shipping Discounts</h4>
                        <div class="form-row">
                            <label for="bulk-threshold-1">Order Value Threshold 1 ($)</label>
                            <input type="number" id="bulk-threshold-1" placeholder="e.g., 100" min="0" step="0.01">
                            <label for="bulk-discount-1">Shipping Discount (%)</label>
                            <input type="number" id="bulk-discount-1" placeholder="e.g., 50" min="0" max="100">
                        </div>
                        
                        <div class="form-row">
                            <label for="bulk-threshold-2">Order Value Threshold 2 ($)</label>
                            <input type="number" id="bulk-threshold-2" placeholder="e.g., 200" min="0" step="0.01">
                            <label for="bulk-discount-2">Shipping Discount (%)</label>
                            <input type="number" id="bulk-discount-2" placeholder="e.g., 100" min="0" max="100">
                        </div>
                        
                        <div class="info-box">
                            <p>Example: 50% off shipping for orders over $100, free shipping for orders over $200</p>
                        </div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn-primary">Save Rules</button>
                        <button type="button" class="btn-secondary" id="delivery-reset">Reset</button>
                    </div>
                </form>
                
                <div class="history-log">
                    <h4>Change History</h4>
                    <div class="log-container">
                        <div class="log-item">
                            <span class="log-date">2025-04-02 09:15</span>
                            <span class="log-action">Updated free shipping threshold to $75</span>
                        </div>
                        <div class="log-item">
                            <span class="log-date">2025-03-28 16:30</span>
                            <span class="log-action">Added bulk discount: 50% off shipping for orders over $100</span>
                        </div>
                    </div>
                </div>