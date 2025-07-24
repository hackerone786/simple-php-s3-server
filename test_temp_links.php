<?php
/**
 * Test script for client-side temporary file links
 * 
 * This script demonstrates how to generate and use temporary file links
 */

// Include the generator
require_once 'temp_link_generator.php';

// Configuration
$test_bucket = 'test-bucket';
$test_file = 'test-file.txt';

// Create test file if it doesn't exist
$test_file_path = "./data/{$test_bucket}/{$test_file}";
if (!file_exists(dirname($test_file_path))) {
    mkdir(dirname($test_file_path), 0777, true);
}
if (!file_exists($test_file_path)) {
    file_put_contents($test_file_path, "This is a test file created at " . date('Y-m-d H:i:s'));
    echo "Created test file: {$test_file_path}\n";
}

echo "=== Client-Side Temporary File Link Test ===\n\n";

// Step 1: Generate a temporary link (client-side)
echo "1. Generating temporary link (client-side)...\n";
$link_data = create_temp_link($test_bucket, $test_file);

echo "✓ Temporary link generated successfully!\n";
echo "   Link: {$link_data['temp_link']}\n";
echo "   Expires: {$link_data['expires_at']}\n\n";

// Step 2: Test the temporary link access
echo "2. Testing temporary link access...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $link_data['temp_link'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_NOBODY => true
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    echo "✓ Temporary link works correctly!\n";
    echo "   HTTP Code: {$http_code}\n";
} else {
    echo "✗ Temporary link failed. HTTP Code: {$http_code}\n";
    echo "Response: {$response}\n";
}

// Step 3: Test with actual file download
echo "\n3. Testing file download via temporary link...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $link_data['temp_link'],
    CURLOPT_RETURNTRANSFER => true
]);

$file_content = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200 && !empty($file_content)) {
    echo "✓ File download successful!\n";
    echo "   File size: " . strlen($file_content) . " bytes\n";
    echo "   Content preview: " . substr($file_content, 0, 50) . "...\n";
} else {
    echo "✗ File download failed. HTTP Code: {$http_code}\n";
}

// Step 4: Test with different timestamp
echo "\n4. Testing with specific timestamp...\n";
$timestamp = time() - 1800; // 30 minutes ago
$link_data_old = create_temp_link_with_timestamp($test_bucket, $test_file, $timestamp);

echo "   Generated link with timestamp {$timestamp}: {$link_data_old['temp_link']}\n";

// Test the old link
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $link_data_old['temp_link'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_NOBODY => true
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    echo "✓ Old timestamp link works (should work for 30 more minutes)\n";
} else {
    echo "✗ Old timestamp link failed. HTTP Code: {$http_code}\n";
}

echo "\n=== Test Complete ===\n";
echo "Note: The temporary link will expire in 1 hour.\n";
echo "This is a client-side implementation - no server requests needed for link generation.\n";

// Helper function for testing with specific timestamp
function create_temp_link_with_timestamp($bucket, $key, $timestamp) {
    global $access_key, $temp_link_expiry;
    
    $link_url = generate_temp_link($bucket, $key, $access_key, $timestamp);
    $expires_at = $timestamp + $temp_link_expiry;
    
    return [
        'temp_link' => $link_url,
        'timestamp' => $timestamp,
        'expires_at' => date('Y-m-d H:i:s', $expires_at),
        'expires_in_seconds' => $temp_link_expiry
    ];
}