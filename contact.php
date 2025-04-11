<?php
// The session_start is in the header.php file

// Simple form processing (you would expand this with validation, etc.)
$formSubmitted = false;
$formError = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Basic validation could go here
    if (!empty($_POST['name']) && !empty($_POST['email']) && !empty($_POST['message'])) {
        // In a real application, you would process the form data here
        // For example, sending an email or storing in database
        $formSubmitted = true;
    } else {
        $formError = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Sweet Treats</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="css/contact.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Contact Hero Section -->
    <div class="contact-hero">
        <div class="container">
            <h1>Get In Touch</h1>
            <p>We'd love to hear from you! Reach out with any questions, concerns, or just to say hello.</p>
        </div>
    </div>
    
    <!-- Contact Content -->
    <div class="container contact-content">
        <!-- Contact Information Cards -->
        <div class="row mb-5">
            <div class="col-md-4">
                <div class="contact-info-card">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3>Our Location</h3>
                    <p>Main Street, near Taha Station<br>Kamed Allouz-Bekaa</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-info-card">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h3>Phone Number</h3>
                    <p>76 921 300<br>Mon-Fri: 9am - 6pm</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-info-card">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Email Address</h3>
                    <p>info@sweettreats.com<br>support@sweettreats.com</p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Contact Form -->
            <div class="col-lg-6">
                <div class="contact-form">
                    <h2>Send Us a Message/Review</h2>
                    
                    <?php if ($formSubmitted): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i> Thank you! Your message has been sent successfully. We'll respond as soon as possible.
                    </div>
                    <?php elseif ($formError): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> Please fill in all required fields.
                    </div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="mb-3">
                            <label for="name" class="form-label">Your Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject">
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Your Message</label>
                            <textarea class="form-control" id="message" name="message" required></textarea>
                        </div>
                        <button type="submit" class="submit-btn">Send Message</button>
                    </form>
                </div>
            </div>
            
            <!-- Google Map -->
            <div class="col-lg-6">
                <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m16!1m12!1m3!1d594.2147322222334!2d35.80823071825051!3d33.62786734496674!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!2m1!1sKamed%20El%20Laouz%20wakid&#39;s%20store!5e0!3m2!1sen!2sus!4v1741579218137!5m2!1sen!2sus" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="faq-section">
            <h2>Frequently Asked Questions</h2>
            
            <div class="accordion" id="contactFaq">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            How long does shipping take?
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#contactFaq">
                        <div class="accordion-body">
                            Standard shipping typically takes 3-5 business days.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            What is your return policy?
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#contactFaq">
                        <div class="accordion-body">
                            We accept returns within 3 days of purchase for most items. Food items and perishables cannot be returned once opened. Please contact us to initiate a return and receive a return shipping label.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            Do you offer wholesale orders?
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#contactFaq">
                        <div class="accordion-body">
                            No, we do not currently offer wholesale orders for retailers, event planners, or businesses.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                            How can I track my order?
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#contactFaq">
                        <div class="accordion-body">
                        Once your order ships, you'll receive a confirmation email with the estimated delivery days. The delivery team will contact you directly for further details.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fixed header on scroll
        window.addEventListener('scroll', function() {
            const header = document.getElementById('mainHeader');
            const scrollPosition = window.scrollY;
            
            if (scrollPosition > 50) {
                header.classList.add('fixed-header');
            } else {
                header.classList.remove('fixed-header');
            }
        });
    </script>
</body>
</html>