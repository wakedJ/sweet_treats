<? require_once './includes/check_admin.php'; ?>
<h3>Top Banner Management</h3>
                <div class="status-message success" style="display: none;">Changes saved successfully!</div>
                <div class="status-message error" style="display: none;">Error saving changes. Please try again.</div>
                
                <form id="banner-form">
                    <div class="form-row">
                        <div class="form-toggle">
                            <label for="banner-status">Banner Status</label>
                            <label class="switch">
                                <input type="checkbox" id="banner-status">
                                <span class="slider round"></span>
                            </label>
                            <span class="toggle-label" id="banner-status-label">Disabled</span>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <label for="banner-text">Top Banner Text</label>
                        <input type="text" id="banner-text" placeholder="Enter banner text (e.g., Free for orders above $50)" required>
                        <div class="error-message" id="banner-text-error"></div>
                    </div>
                    
                   
                    
                    <div class="form-row">
                        <label for="banner-text-color">Text Color</label>
                        <div class="color-picker-container">
                            <input type="color" id="banner-text-color" value="#ffffff">
                            <input type="text" id="banner-text-color-hex" value="#ffffff" maxlength="7">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <label for="banner-bg-color">Background Color</label>
                        <div class="color-picker-container">
                            <input type="color" id="banner-bg-color" value="#000000">
                            <input type="text" id="banner-bg-color-hex" value="#000000" maxlength="7">
                        </div>
                    </div>
                    
                    <div class="banner-preview">
                        <label>Banner Preview</label>
                        <div id="banner-preview-box">Free shipping on orders over $50! Use code: SHIP50</div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn-primary">Save Banner</button>
                        <button type="button" class="btn-secondary" id="banner-reset">Reset</button>
                    </div>
                </form>
                
                <div class="history-log">
                    <h4>Change History</h4>
                    <div class="log-container">
                        <div class="log-item">
                            <span class="log-date">2025-04-03 10:23</span>
                            <span class="log-action">Banner enabled - "Spring Sale! 20% off with code SPRING20"</span>
                        </div>
                        <div class="log-item">
                            <span class="log-date">2025-04-01 14:45</span>
                            <span class="log-action">Banner disabled</span>
                        </div>
                    </div>
                </div>