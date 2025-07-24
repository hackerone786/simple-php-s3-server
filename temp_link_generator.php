<?php
/**
 * Client-side Temporary Link Generator
 * 
 * This script allows you to generate temporary links on the client side
 * without making any server requests.
 */

// Configuration - Change these to match your server
$server_url = 'http://localhost'; // Your server URL
$access_key = 'put_your_key_here'; // Your access key
$temp_link_expiry = 3600; // 1 hour in seconds

/**
 * Generate a temporary link for a file
 * 
 * @param string $bucket The bucket name
 * @param string $key The file key/path
 * @param string $access_key The access key
 * @param int $timestamp Unix timestamp (optional, defaults to current time)
 * @return string The temporary link URL
 */
function generate_temp_link($bucket, $key, $access_key, $timestamp = null) {
    global $server_url;
    
    if ($timestamp === null) {
        $timestamp = time();
    }
    
    // Generate the MD5 hash
    $data = $timestamp . $bucket . $key . $access_key;
    $link_key = md5($data);
    
    // Build the URL
    $encoded_bucket = urlencode($bucket);
    $encoded_key = urlencode($key);
    
    return "{$server_url}/temp/{$access_key}/{$timestamp}/{$encoded_bucket}/{$encoded_key}";
}

/**
 * Generate a temporary link with current timestamp
 * 
 * @param string $bucket The bucket name
 * @param string $key The file key/path
 * @return array Array containing link URL and expiry information
 */
function create_temp_link($bucket, $key) {
    global $access_key, $temp_link_expiry;
    
    $timestamp = time();
    $link_url = generate_temp_link($bucket, $key, $access_key, $timestamp);
    $expires_at = $timestamp + $temp_link_expiry;
    
    return [
        'temp_link' => $link_url,
        'timestamp' => $timestamp,
        'expires_at' => date('Y-m-d H:i:s', $expires_at),
        'expires_in_seconds' => $temp_link_expiry
    ];
}

// Example usage
if (php_sapi_name() === 'cli') {
    // Command line usage
    if ($argc < 3) {
        echo "Usage: php temp_link_generator.php <bucket> <key>\n";
        echo "Example: php temp_link_generator.php my-bucket path/to/file.txt\n";
        exit(1);
    }
    
    $bucket = $argv[1];
    $key = $argv[2];
    
    $link_data = create_temp_link($bucket, $key);
    
    echo "=== Temporary Link Generated ===\n";
    echo "Bucket: {$bucket}\n";
    echo "Key: {$key}\n";
    echo "Link: {$link_data['temp_link']}\n";
    echo "Expires: {$link_data['expires_at']}\n";
    echo "Expires in: {$link_data['expires_in_seconds']} seconds\n";
    echo "===============================\n";
} else {
    // Web usage - include this file in your web application
    echo "<!-- Temporary Link Generator loaded -->\n";
    echo "<!-- Use create_temp_link('bucket', 'key') function -->\n";
}
?>