<div class="tab-content" id="settingsTab">
    <h3>Account Settings</h3>
    
    <div class="settings-section">
        <h4>Profile Information</h4>
        <form method="post" action="account.php" class="settings-form">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>" class="form-control" required>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="phone_number">Phone Number</label>
                <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user_data['phone_number'] ?? ''); ?>" class="form-control">
            </div>
            
            <button type="submit" name="update_profile" class="account-btn">Update Profile</button>
        </form>
    </div>
    
    <div class="settings-section">
        <h4>Change Password</h4>
        <form method="post" action="account.php" class="settings-form">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
            
            <button type="submit" name="update_password" class="account-btn">Update Password</button>
        </form>
    </div>
    
    <div class="settings-section">
        <h4>Communication Preferences</h4>
        <form method="post" action="update_preferences.php" class="settings-form">
            <div class="form-check mb-3">
                <input type="checkbox" id="email_promotions" name="email_promotions" class="form-check-input" <?php echo (isset($user_data['email_promotions']) && $user_data['email_promotions'] == 1) ? 'checked' : ''; ?>>
                <label for="email_promotions" class="form-check-label">Email me about promotions and new treats</label>
            </div>
            
            <div class="form-check mb-3">
                <input type="checkbox" id="sms_notifications" name="sms_notifications" class="form-check-input" <?php echo (isset($user_data['sms_notifications']) && $user_data['sms_notifications'] == 1) ? 'checked' : ''; ?>>
                <label for="sms_notifications" class="form-check-label">Send me SMS order updates</label>
            </div>
            
            <button type="submit" name="update_preferences" class="account-btn">Save Preferences</button>
        </form>
    </div>
</div>