<?php
/**
 * Test script for temporary file links
 * 
 * This script demonstrates how to create and use temporary file links
 */

// Configuration
$server_url = 'http://localhost'; // Change this to your server URL
$access_key = 'put_your_key_here'; // Change this to your access key
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

echo "=== Temporary File Link Test ===\n\n";

// Step 1: Create a temporary link
echo "1. Creating temporary link...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "{$server_url}/temp-link/create",
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'bucket' => $test_bucket,
        'key' => $test_file
    ]),
    CURLOPT_HTTPHEADER => [
        'Authorization: AWS4-HMAC-SHA256 Credential=' . $access_key . '/20240101/us-east-1/s3/aws4_request',
        'Content-Type: application/json'
    ],
    CURLOPT_RETURNTRANSFER => true
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    echo "Error creating temporary link. HTTP Code: {$http_code}\n";
    echo "Response: {$response}\n";
    exit(1);
}

$link_data = json_decode($response, true);
$temp_link = $link_data['temp_link'];
$expires_at = $link_data['expires_at'];

echo "✓ Temporary link created successfully!\n";
echo "   Link: {$temp_link}\n";
echo "   Expires: {$expires_at}\n\n";

// Step 2: Access the file via temporary link
echo "2. Testing temporary link access...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $temp_link,
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
    CURLOPT_URL => $temp_link,
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

echo "\n=== Test Complete ===\n";
echo "Note: The temporary link will expire in 1 hour.\n";
echo "You can test expiration by waiting or manually modifying the temp_links.json file.\n";