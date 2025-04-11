<?php
session_start(); // Start the session to access login information
include "includes/db.php"; // Include database connection

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['user_name'] : '';
$user_id = $is_logged_in ? $_SESSION['user_id'] : '';

// If user is logged in, fetch their profile information
$user_data = [];
if ($is_logged_in) {
    try {
        // Use the existing connection from db.php
        $query = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user_data = $result->fetch_assoc();
        }
        
        $stmt->close();
    } catch (Exception $e) {
        // Handle error (optionally display message)
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Sweet Treats</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/account.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="account-container">
        <!-- This section shows when user is not logged in -->
         <!-- Display messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['message']; 
                unset($_SESSION['message']); 
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']); 
                ?>
            </div>
        <?php endif; ?>
        <div class="not-logged-in" style="<?php echo $is_logged_in ? 'display: none;' : ''; ?>">
            <div class="account-header">
                <h1>My Account</h1>
                <p>Sign in to manage your shipping address and receive special offers on our sweet treats!</p>
            </div>
            
            <div class="account-options">
                <div class="account-card">
                    <div class="card-header">
                        <i class="fas fa-sign-in-alt"></i>
                        <h2>Existing Customers</h2>
                        <p>Welcome back! Sign in to your account</p>
                    </div>
                    <div class="card-content">
                        <ul class="features-list">
                            <li><i class="fas fa-check-circle"></i> Save your shipping address</li>
                            <li><i class="fas fa-check-circle"></i> Faster checkout experience</li>
                            <li><i class="fas fa-check-circle"></i> Receive special offers & promotions</li>
                        </ul>
                        <a href="login.php" class="account-btn">Sign In</a>
                    </div>
                </div>
                
                <div class="account-card">
                    <div class="card-header">
                        <i class="fas fa-user-plus"></i>
                        <h2>New Customers</h2>
                        <p>Create an account for a sweeter experience</p>
                    </div>
                    <div class="card-content">
                        <a href="register.php" class="account-btn">Create Account</a>
                        <a href="shop.php" class="account-btn secondary">Continue as Guest</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- This section will show when user is logged in -->
        <div class="logged-in-container" id="loggedInContainer" style="<?php echo $is_logged_in ? 'display: block;' : 'display: none;'; ?>">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <h2>Hello, <span id="userName"><?php echo htmlspecialchars($user_name); ?></span>!</h2>
                    <p id="userEmail"><?php echo htmlspecialchars($user_data['email'] ?? ''); ?></p>
                </div>
            </div>
            
            <div class="account-tabs">
                <div class="account-tab active" data-tab="addresses">My Address</div>
                <div class="account-tab" data-tab="settings">Account Settings</div>
            </div>
            
            <div class="tab-content active" id="addressesTab">
                <div class="address-card">
                    <h3>Shipping Address</h3>
                    <p><?php echo htmlspecialchars($user_data['full_name'] ?? 'Your Name'); ?></p>
                    <p><?php echo htmlspecialchars($user_data['address_line1'] ?? '123 Sweet Street'); ?></p>
                    <p><?php echo htmlspecialchars($user_data['address_line2'] ?? 'Apartment 4B'); ?></p>
                    <p><?php echo htmlspecialchars($user_data['city'] ?? 'Candyville') . ', ' . 
                              htmlspecialchars($user_data['state'] ?? 'CA') . ' ' . 
                              htmlspecialchars($user_data['zip'] ?? '90210'); ?></p>
                    <p><?php echo htmlspecialchars($user_data['country'] ?? 'United States'); ?></p>
                    <div class="address-actions">
                        <button>Edit</button>
                    </div>
                </div>
                
                <a href="#" class="account-btn" style="max-width: 200px; margin-top: 20px;">Update Address</a>
            </div>
            
            <div class="tab-content" id="settingsTab">
                <h3 style="color: #ff69b4; margin-bottom: 20px;">Account Settings</h3>
                <p style="margin-bottom: 20px; color: #555;">Manage your personal information and preferences</p>
                
                <!-- Profile Update Form -->
                <div class="settings-section">
                    <h4 style="color: #666; margin-bottom: 15px;">Personal Information</h4>
                    
                    <form id="profileUpdateForm" class="profile-form" method="post" action="update_profile.php">
                        <div class="form-group">
                            <label for="fullName">Full Name</label>
                            <input type="text" id="fullName" name="fullName" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="birthdate">Birthday</label>
                            <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($user_data['birthdate'] ?? ''); ?>" class="form-control">
                            <small class="form-text">We'll send you a special birthday treat!</small>
                        </div>
                        
                        <button type="submit" name="update_profile" class="account-btn">Save Changes</button>
                    </form>
                </div>
                
                <!-- Password Update Section -->
                <div class="settings-section">
                    <h4 style="color: #666; margin-bottom: 15px;">Security</h4>
                    
                    <form id="passwordUpdateForm" class="profile-form" method="post" action="update_password.php">
                        <div class="form-group">
                            <label for="currentPassword">Current Password</label>
                            <input type="password" id="currentPassword" name="currentPassword" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <input type="password" id="newPassword" name="newPassword" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmPassword">Confirm New Password</label>
                            <input type="password" id="confirmPassword" name="confirmPassword" class="form-control">
                        </div>
                        
                        <button type="submit" name="update_password" class="account-btn">Update Password</button>
                    </form>
                </div>
                
                <!-- Communication Preferences -->
                <div class="settings-section">
                    <h4 style="color: #666; margin-bottom: 15px;">Communication Preferences</h4>
                    
                    <form id="preferencesForm" class="profile-form" method="post" action="update_preferences.php">
                        <div class="form-check">
                            <input type="checkbox" id="emailPromo" name="emailPromo" class="form-check-input" <?php echo ($user_data['email_promo'] ?? 1) ? 'checked' : ''; ?>>
                            <label for="emailPromo" class="form-check-label">Email me about new products and promotions</label>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="specialOffers" name="specialOffers" class="form-check-input" <?php echo ($user_data['special_offers'] ?? 1) ? 'checked' : ''; ?>>
                            <label for="specialOffers" class="form-check-label">Notify me about special offers and discounts</label>
                        </div>
                        
                        <button type="submit" name="update_preferences" class="account-btn">Save Preferences</button>
                    </form>
                </div>
            </div>
            
            <a href="logout.php" class="logout-btn" id="logoutBtn">Sign Out</a>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>

    <script>
        // Fixed header on scroll
        window.addEventListener('scroll', function() {
            const header = document.getElementById('mainHeader');
            if (window.scrollY > 50) {
                header.style.position = 'fixed';
                header.style.top = '0';
                header.style.left = '0';
                header.style.right = '0';
                header.style.zIndex = '1000';
                header.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
            } else {
                header.style.position = '';
                header.style.boxShadow = '';
            }
        });
        
        // Tab functionality
        document.querySelectorAll('.account-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.account-tab').forEach(t => {
                    t.classList.remove('active');
                });
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Hide all tab content
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                // Show selected tab content
                const tabId = this.getAttribute('data-tab') + 'Tab';
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Profile form submission handling
        const profileForm = document.getElementById('profileUpdateForm');
        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                // The form will submit normally to update_profile.php
                // No need to prevent default
            });
        }
        
        // Password form submission handling with validation
        const passwordForm = document.getElementById('passwordUpdateForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                const currentPassword = document.getElementById('currentPassword').value;
                const newPassword = document.getElementById('newPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                
                // Validate passwords
                if (!currentPassword || !newPassword || !confirmPassword) {
                    alert('Please fill in all password fields');
                    e.preventDefault();
                    return;
                }
                
                if (newPassword !== confirmPassword) {
                    alert('New passwords do not match');
                    e.preventDefault();
                    return;
                }
                
                // The form will submit to update_password.php if validation passes
            });
        }
    </script>
</body>
</html>