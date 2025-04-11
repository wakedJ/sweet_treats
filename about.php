<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Sweet Treats</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">    
    <link rel="stylesheet" href="css/about.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
   
    <section class="about-hero">
        <h1>Our Sweet Story</h1>
        <p>Bringing unique treats and international flavors to your doorstep</p>
    </section>
    
    <div class="about-sections">
        <div class="about-section">
            <div class="about-text">
                <h2>How We Started</h2>
                <p>Sweet Treats began with a simple idea: to share our love of unique, hard-to-find treats with the world. What started as a small online shop run from our founder's kitchen has grown into a beloved source for specialty items from around the globe.</p>
                <p>Our journey began during the challenging days of 2020 when finding joy in the little things became more important than ever. We noticed how difficult it was to get access to international candies, snacks, and specialty items, so we created a solution.</p>
            </div>
            <div class="about-image">
                <img src="images/kiosk.jpg" alt="Sweet Treats founders in store">
            </div>
        </div>
        
        <div class="about-section">
            <div class="about-text">
                <h2>What Makes Us Special</h2>
                <p>Unlike traditional candy shops, we curate a unique selection that spans beyond just sweets. From viral cleaning products to international snacks that you've seen on social media but couldn't find locally, we make the impossible possible.</p>
                <p>Every item in our store is carefully selected for quality, uniqueness, and that special "wow factor" that makes our customers come back again and again. We take pride in being your one-stop shop for treats and treasures you won't find on regular store shelves.</p>
            </div>
            <div class="about-image">
                <img src="images/snacks_collection.jpg" alt="Collection of unique treats">
            </div>
        </div>
        
        <div class="about-section">
            <div class="about-text">
                <h2>Our Mission</h2>
                <p>At Sweet Treats, our mission is to bring joy, excitement, and a sense of discovery to everyday life. We believe that finding a special treat or that elusive product you've been searching for should be an experience full of delight.</p>
                <p>We're committed to offering exceptional customer service, carefully packaged products, and a constantly updated inventory that reflects the latest trends and timeless classics alike. Every package we send is wrapped with care because we know the anticipation of receiving something special is part of the experience.</p>
            </div>
            <div class="about-image">
                <img src="images/package.jpg" alt="Beautifully packaged Sweet Treats order">
            </div>
        </div>
    </div>
    
    
    <section class="values-section">
        <h2>Our Values</h2>
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3>Joy in Every Package</h3>
                <p>We believe in creating moments of delight with every order. From selection to packaging to delivery, we aim to bring smiles to our customers.</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-globe"></i>
                </div>
                <h3>Global Perspective</h3>
                <p>We celebrate diverse flavors and products from around the world, helping our customers explore new cultures through tastes and experiences.</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-star"></i>
                </div>
                <h3>Quality First</h3>
                <p>We never compromise on quality. Every item we offer has been personally tested and approved by our dedicated team.</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Customer Community</h3>
                <p>Our customers are family. We value your feedback, suggestions, and shared excitement about new discoveries in our collection.</p>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
        // Fixed header on scroll
        window.addEventListener('scroll', function() {
            const header = document.getElementById('mainHeader');
            const scrollPosition = window.scrollY;
            
            // Fix header when scrolled down
            if (scrollPosition > 50) {
                header.classList.add('fixed-header');
            } else {
                header.classList.remove('fixed-header');
            }
        });
        // Add this script to your account.php page, after the existing JavaScript

// Profile Update Form Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle Profile Update Form Submission
    const profileForm = document.getElementById('profileUpdateForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // In a real implementation, you would send this data to the server
            // This is just a demonstration
            
            // Display success message (you'll need to add an alert div to your HTML)
            showSuccessMessage('profileUpdateForm', 'Profile updated successfully!');
        });
    }
    
    // Handle Password Update Form Submission
    const passwordForm = document.getElementById('passwordUpdateForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            // Validate passwords
            if (!currentPassword || !newPassword || !confirmPassword) {
                alert('Please fill in all password fields');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                alert('New passwords do not match');
                return;
            }
            
            // In a real implementation, you would send this data to the server
            // This is just a demonstration
            
            // Clear password fields
            document.getElementById('currentPassword').value = '';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            
            // Display success message
            showSuccessMessage('passwordUpdateForm', 'Password updated successfully!');
        });
    }
    
    // Handle Preferences Form Submission
    const preferencesForm = document.getElementById('preferencesForm');
    if (preferencesForm) {
        preferencesForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // In a real implementation, you would send this data to the server
            // This is just a demonstration
            
            // Display success message
            showSuccessMessage('preferencesForm', 'Preferences saved successfully!');
        });
    }
    
    // Function to show success message
    function showSuccessMessage(formId, message) {
        // Check if alert already exists
        let alertElement = document.querySelector(`#${formId} .alert`);
        
        if (!alertElement) {
            // Create alert element if it doesn't exist
            alertElement = document.createElement('div');
            alertElement.className = 'alert alert-success';
            document.getElementById(formId).prepend(alertElement);
        }
        
        // Update message and display
        alertElement.textContent = message;
        alertElement.style.display = 'block';
        
        // Hide after 3 seconds
        setTimeout(function() {
            alertElement.style.display = 'none';
        }, 3000);
    }
});
    </script>
</body>
</html>