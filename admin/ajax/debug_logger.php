<?php
// Create this as a new file: ajax/debug_logger.php
// This is a utility to help debug path issues

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to write debug info to a file
function debug_log($message, $filename = 'debug_log.txt') {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    
    // Write to file
    file_put_contents($filename, $log_message, FILE_APPEND);
}

// Try to create a test file with directory permissions
function test_writing_permissions() {
    $dirs = [
        '.' => 'Current directory',
        '..' => 'Parent directory',
        '../includes' => 'Includes directory'
    ];
    
    $results = [];
    
    foreach ($dirs as $dir => $description) {
        $results[$dir] = [
            'exists' => is_dir($dir),
            'writable' => is_writable($dir),
            'readable' => is_readable($dir)
        ];
        
        if ($results[$dir]['exists'] && $results[$dir]['writable']) {
            $test_file = "$dir/test_write_permissions.txt";
            $write_test = @file_put_contents($test_file, "Test write at " . date('Y-m-d H:i:s'));
            $results[$dir]['write_test'] = ($write_test !== false);
            
            // Clean up test file
            if ($results[$dir]['write_test']) {
                @unlink($test_file);
            }
        }
    }
    
    return $results;
}

// Add this to your send-reply.php file at the top
// to help with debugging
if (isset($_GET['debug'])) {
    header('Content-Type: application/json');
    
    $debug_info = [
        'server_info' => [
            'php_version' => PHP_VERSION,
            'document_root' => $_SERVER['DOCUMENT_ROOT'],
            'script_filename' => $_SERVER['SCRIPT_FILENAME'],
            'current_dir' => getcwd(),
            'parent_dir' => dirname(getcwd())
        ],
        'file_checks' => [
            'db_include_path' => '../includes/db.php',
            'db_file_exists' => file_exists('../includes/db.php'),
            'alt_db_path' => '../../includes/db.php',
            'alt_db_file_exists' => file_exists('../../includes/db.php')
        ],
        'directory_permissions' => test_writing_permissions(),
        'post_data' => $_POST
    ];
    
    echo json_encode($debug_info, JSON_PRETTY_PRINT);
    exit;
}
?>