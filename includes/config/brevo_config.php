<?php
/**
 * Brevo API Configuration File
 * 
 * This file contains sensitive API credentials and configuration 
 * for the Brevo email service. Keep this file secure and outside
 * of public web directories.
 */


// Define constants for Brevo API - add validation to ensure proper format
define('BREVO_API_KEY', getenv('BREVO_API_KEY'));
define('BREVO_SENDER_EMAIL', 'lanawaked237@gmail.com'); // Your verified sender email
define('BREVO_SENDER_NAME', 'Sweet Treats');
define('BREVO_API_URL', 'https://api.brevo.com/v3/smtp/email');

// Validate API key format
if (!preg_match('/^xkeysib-[a-zA-Z0-9-]+$/', BREVO_API_KEY)) {
    error_log("WARNING: Brevo API key appears to be in invalid format. Check your API key.");
}

// Validate sender email
if (!filter_var(BREVO_SENDER_EMAIL, FILTER_VALIDATE_EMAIL)) {
    error_log("WARNING: Brevo sender email appears to be invalid. Check your sender email.");
}

/**
 * Send an email using Brevo API
 * 
 * @param string $to_email Recipient email
 * @param string $to_name Recipient name
 * @param string $subject Email subject
 * @param string $html_content HTML content of the email
 * @param string $text_content Plain text content of the email
 * @return array Array containing success status and message
 */
function send_brevo_email($to_email, $to_name, $subject, $html_content, $text_content = '') {
    try {
        // Validate input parameters
        if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid recipient email address'
            ];
        }
        
        if (empty($subject)) {
            return [
                'success' => false,
                'message' => 'Email subject cannot be empty'
            ];
        }
        
        if (empty($html_content)) {
            return [
                'success' => false,
                'message' => 'Email content cannot be empty'
            ];
        }
        
        // If text content is empty, generate a basic version from HTML
        if (empty($text_content)) {
            $text_content = strip_tags($html_content);
        }
        
        // Set up the request data
        $data = [
            "sender" => [
                "name" => BREVO_SENDER_NAME,
                "email" => BREVO_SENDER_EMAIL
            ],
            "to" => [
                [
                    "email" => $to_email,
                    "name" => $to_name
                ]
            ],
            "subject" => $subject,
            "htmlContent" => $html_content,
            "textContent" => $text_content
        ];
        
        // Convert data to JSON
        $data_json = json_encode($data);
        if ($data_json === false) {
            error_log("JSON encoding error: " . json_last_error_msg());
            return [
                'success' => false,
                'message' => 'JSON encoding error: ' . json_last_error_msg()
            ];
        }
        
        // Initialize cURL session
        $ch = curl_init(BREVO_API_URL);
        if ($ch === false) {
            error_log("Failed to initialize cURL");
            return [
                'success' => false,
                'message' => 'Failed to initialize cURL'
            ];
        }
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "Content-Type: application/json",
            "api-key: " . BREVO_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Enable SSL verification in production
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Set a reasonable timeout
        
        // Execute cURL request and get response
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Check for cURL errors
        if (curl_errno($ch)) {
            $error_message = "Brevo cURL Error #" . curl_errno($ch) . ": " . curl_error($ch);
            error_log($error_message);
            curl_close($ch);
            return [
                'success' => false, 
                'message' => $error_message
            ];
        }
        
        // Close cURL session
        curl_close($ch);
        
        // Log the response (but sanitize for security)
        error_log("Brevo API Response Code: " . $http_code);
        
        // Return result
        if ($http_code >= 200 && $http_code < 300) {
            $response_data = json_decode($response, true);
            return [
                'success' => true,
                'message' => 'Email sent successfully',
                'message_id' => $response_data['messageId'] ?? null
            ];
        } else {
            // Get more detailed error message from response if possible
            $response_data = json_decode($response, true);
            $error_details = isset($response_data['message']) ? " - " . $response_data['message'] : "";
            
            $error_message = "Brevo API Error: HTTP Code " . $http_code . $error_details;
            error_log($error_message);
            
            // Add more context for specific error codes
            $error_context = '';
            if ($http_code == 401) {
                $error_context = " Check if your API key is valid.";
            } elseif ($http_code == 400) {
                $error_context = " Check if your request data is correct.";
            } elseif ($http_code == 429) {
                $error_context = " You have exceeded your API rate limit.";
            }
            
            return [
                'success' => false,
                'message' => $error_message . $error_context
            ];
        }
    } catch (Exception $e) {
        $error_message = "Brevo Exception: " . $e->getMessage();
        error_log($error_message);
        return [
            'success' => false,
            'message' => $error_message
        ];
    }
}
?>