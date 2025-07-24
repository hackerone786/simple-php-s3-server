# PHP S3 Server

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)

A lightweight S3-compatible object storage server implemented in PHP, using local filesystem as storage backend.

## Key Features

- ✅ S3 OBJECT API compatibility (PUT/GET/DELETE/POST)
- ✅ Multipart upload support
- ✅ No database required - pure filesystem storage
- ✅ Simple AWS V4 signature authentication
- ✅ Lightweight single-file deployment


## TLDR

Simply create a new website on your virtual host, place the `index.php` file from the GitHub repository into the website's root directory, modify the password configuration at the beginning of `index.php`, then config the rewite rule set all route to index.php, and you're ready to use it.

- **Endpoint**: Your website domain  
- **Access Key**: The password you configured  
- **Secret Key**: Can be any value (not used in this project)  
- **Region**: Can be any value (not used in this project)  

For example, if an object has:  
- `bucket="music"`  
- `key="hello.mp3"`  

It will be stored at: `./data/music/hello.mp3`  

You can also combine this with Cloudflare's CDN for faster and more stable performance.



## Quick Start

### Requirements

- PHP 8.0+
- Apache/Nginx (with mod_rewrite enabled)

### Installation

1. Set up a website

2. Download `index.php` to your website root directory

3. Create data directory  
Create a `data` folder in your website root directory

4. Configure URL rewriting (DirectAdmin example):  
Create `.htaccess` in root directory with:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    # If request is not for existing file/directory
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    # Redirect all requests to index.php
    RewriteRule ^(.*)$ index.php [L,QSA]
</IfModule>
```
> For other web servers, consult documentation on how to configure rewrite rules to redirect all requests to index.php

### Configuration

Edit the top section of `index.php`:
```php
define('ALLOWED_ACCESS_KEYS', ['your-access-key-here']);  
// Allowed access keys - when using third-party OSS tools, only the access-key is required.
// Other fields like region and secret-key can be arbitrary values.
```

### Start Using It!

#### Demo: Using in Minio

```python
oss_client = Minio("your-domain.com", access_key="your-access-key-here", secret_key="*", secure=True)

```

## Temporary File Links

This S3-compatible file server supports client-side temporary file link generation with the following features:

- **Client-side generation**: No server requests needed to create links
- **MD5-based verification**: Uses timestamp + bucket + key + access_key
- **1-hour expiration**: Links automatically expire after 3600 seconds
- **No storage required**: Links are verified on-the-fly without saving to disk
- **No authentication required**: Access files without AWS credentials

### How It Works

The system uses a simple algorithm that allows clients to generate links themselves:

1. **Client generates link**: `md5(timestamp + bucket + key + access_key)`
2. **URL format**: `/temp/{access_key}/{timestamp}/{encoded_bucket}/{encoded_key}`
3. **Server verifies**: Checks if the link is valid and not expired

### Client-Side Usage

#### PHP Client

**Basic Usage**
```php
<?php
require_once 'temp_link_generator.php';

// Generate a temporary link
$link_data = create_temp_link('my-bucket', 'path/to/file.txt');
echo "Temporary link: " . $link_data['temp_link'];
echo "Expires: " . $link_data['expires_at'];
?>
```

**Advanced Usage**
```php
<?php
require_once 'temp_link_generator.php';

// Generate link with specific timestamp
$timestamp = time() - 1800; // 30 minutes ago
$link_data = generate_temp_link('my-bucket', 'path/to/file.txt', 'my_access_key', $timestamp);

// Use in web application
function shareFile($bucket, $key) {
    $link_data = create_temp_link($bucket, $key);
    return [
        'url' => $link_data['temp_link'],
        'expires' => $link_data['expires_at'],
        'download_link' => '<a href="' . $link_data['temp_link'] . '">Download File</a>'
    ];
}

// Example usage
$share_data = shareFile('documents', 'report.pdf');
echo $share_data['download_link'];
?>
```

#### JavaScript Client

**Browser Usage**
```html
<!DOCTYPE html>
<html>
<head>
    <title>File Sharing</title>
    <script src="temp_link_generator.js"></script>
</head>
<body>
    <button onclick="shareFile('my-bucket', 'path/to/file.txt')">Share File</button>
    
    <script>
        function shareFile(bucket, key) {
            const linkData = TempLinkGenerator.createTempLink(bucket, key);
            
            // Copy to clipboard
            navigator.clipboard.writeText(linkData.tempLink).then(() => {
                alert('Temporary link copied to clipboard!');
            });
        }
    </script>
</body>
</html>
```

**Node.js Usage**
```javascript
const TempLinkGenerator = require('./temp_link_generator.js');

// Generate a temporary link
const linkData = TempLinkGenerator.createTempLink('my-bucket', 'path/to/file.txt');
console.log('Temporary link:', linkData.tempLink);
console.log('Expires at:', linkData.expiresAt);
```

#### Python Client

**Basic Usage**
```python
from temp_link_generator import create_temp_link

# Generate a temporary link
link_data = create_temp_link('my-bucket', 'path/to/file.txt')
print(f"Temporary link: {link_data['temp_link']}")
print(f"Expires: {link_data['expires_at']}")
```

**Flask Integration**
```python
from flask import Flask, request, jsonify
from temp_link_generator import create_temp_link

app = Flask(__name__)

@app.route('/api/share-file', methods=['POST'])
def share_file():
    data = request.get_json()
    bucket = data.get('bucket')
    key = data.get('key')
    
    link_data = create_temp_link(bucket, key)
    return jsonify({
        'success': True,
        'temp_link': link_data['temp_link'],
        'expires_at': link_data['expires_at']
    })
```

**Command Line Usage**
```bash
# Generate a temporary link
python temp_link_generator.py my-bucket path/to/file.txt

# Output:
# === Temporary Link Generated ===
# Bucket: my-bucket
# Key: path/to/file.txt
# Link: http://localhost/temp/my_access_key/1705312200/my-bucket/path%2Fto%2Ffile.txt
# Expires: 2024-01-15T15:30:00
# Expires in: 3600 seconds
# ===============================
```

### URL Structure

```
/temp/{access_key}/{timestamp}/{urlencoded_bucket}/{urlencoded_key}
```

Example:
```
/temp/my_access_key/1705312200/my-bucket/path%2Fto%2Ffile.txt
```

### Algorithm Details

**Link Generation (Client-Side)**
```php
$timestamp = time();
$data = $timestamp . $bucket . $key . $access_key;
$link_key = md5($data);
$url = "/temp/{$access_key}/{$timestamp}/" . urlencode($bucket) . "/" . urlencode($key);
```

**Link Verification (Server-Side)**
```php
// Check if timestamp is within expiry window
if ($timestamp + TEMP_LINK_EXPIRY > $current_time) {
    $expected_key = md5($timestamp . $bucket . $key . $access_key);
    if ($expected_key === $link_key) {
        return true; // Valid link
    }
}
```

### Configuration

Update these values in the generator scripts:

```php
// PHP version
$server_url = 'http://your-server.com';
$access_key = 'your_access_key';
$temp_link_expiry = 3600; // 1 hour
```

```javascript
// JavaScript version
const SERVER_URL = 'http://your-server.com';
const ACCESS_KEY = 'your_access_key';
const TEMP_LINK_EXPIRY = 3600; // 1 hour
```

```python
# Python version
SERVER_URL = 'http://your-server.com'
ACCESS_KEY = 'your_access_key'
TEMP_LINK_EXPIRY = 3600  # 1 hour
```

### Security Considerations

- Temporary links bypass authentication completely
- Links are tied to the specific access key that created them
- The MD5 key includes a timestamp to prevent replay attacks
- Links automatically expire after 1 hour
- No persistent storage means no cleanup required
- Access key is visible in the URL (consider using HTTPS)

### Advantages of Client-Side Generation

1. **No Server Requests**: Generate links instantly without API calls
2. **Better Performance**: No network latency for link creation
3. **Offline Capability**: Can generate links even when server is unreachable
4. **Scalable**: No server resources used for link generation
5. **Simple**: Easy to integrate into any application

### Testing

**PHP Test**
```bash
php test_temp_links.php
```

**Python Test**
```bash
python temp_link_generator.py test-bucket test-file.txt
```

**JavaScript Test**
```javascript
// In browser console
const linkData = TempLinkGenerator.createTempLink('test-bucket', 'test-file.txt');
console.log('Test link:', linkData.tempLink);
```