# Temporary File Links (Client-Side Generation)

This S3-compatible file server supports client-side temporary file link generation with the following features:

- **Client-side generation**: No server requests needed to create links
- **MD5-based verification**: Uses timestamp + bucket + key + access_key
- **1-hour expiration**: Links automatically expire after 3600 seconds
- **No storage required**: Links are verified on-the-fly without saving to disk
- **No authentication required**: Access files without AWS credentials

## How It Works

The system uses a simple algorithm that allows clients to generate links themselves:

1. **Client generates link**: `md5(timestamp + bucket + key + access_key)`
2. **URL format**: `/temp/{access_key}/{timestamp}/{encoded_bucket}/{encoded_key}`
3. **Server verifies**: Checks if the link is valid and not expired

## Client-Side Usage

### PHP Client

#### Basic Usage
```php
<?php
require_once 'temp_link_generator.php';

// Generate a temporary link
$link_data = create_temp_link('my-bucket', 'path/to/file.txt');
echo "Temporary link: " . $link_data['temp_link'];
echo "Expires: " . $link_data['expires_at'];
?>
```

#### Advanced Usage
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

#### Laravel Integration
```php
<?php
// In your Laravel controller
use App\Services\TempLinkService;

class FileController extends Controller
{
    public function share(Request $request)
    {
        $bucket = $request->input('bucket');
        $key = $request->input('key');
        
        require_once base_path('app/Services/temp_link_generator.php');
        $link_data = create_temp_link($bucket, $key);
        
        return response()->json([
            'success' => true,
            'temp_link' => $link_data['temp_link'],
            'expires_at' => $link_data['expires_at']
        ]);
    }
}
?>
```

### JavaScript Client

#### Browser Usage
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
            
            // Or display in a modal
            showShareModal(linkData);
        }
        
        function showShareModal(linkData) {
            const modal = document.createElement('div');
            modal.innerHTML = `
                <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                           background: white; padding: 20px; border: 1px solid #ccc;">
                    <h3>Temporary Link Generated</h3>
                    <p><strong>Link:</strong> <a href="${linkData.tempLink}" target="_blank">${linkData.tempLink}</a></p>
                    <p><strong>Expires:</strong> ${linkData.expiresAt}</p>
                    <button onclick="this.parentElement.remove()">Close</button>
                </div>
            `;
            document.body.appendChild(modal);
        }
    </script>
</body>
</html>
```

#### Node.js Usage
```javascript
const TempLinkGenerator = require('./temp_link_generator.js');

// Generate a temporary link
const linkData = TempLinkGenerator.createTempLink('my-bucket', 'path/to/file.txt');
console.log('Temporary link:', linkData.tempLink);
console.log('Expires at:', linkData.expiresAt);

// Use in Express.js application
const express = require('express');
const app = express();

app.post('/api/share-file', (req, res) => {
    const { bucket, key } = req.body;
    
    const linkData = TempLinkGenerator.createTempLink(bucket, key);
    
    res.json({
        success: true,
        temp_link: linkData.tempLink,
        expires_at: linkData.expiresAt
    });
});

app.listen(3000, () => {
    console.log('Server running on port 3000');
});
```

#### React Component
```jsx
import React, { useState } from 'react';
import { TempLinkGenerator } from './temp_link_generator.js';

function FileSharingComponent({ bucket, key }) {
    const [tempLink, setTempLink] = useState(null);
    const [loading, setLoading] = useState(false);

    const generateLink = () => {
        setLoading(true);
        
        try {
            const linkData = TempLinkGenerator.createTempLink(bucket, key);
            setTempLink(linkData);
        } catch (error) {
            console.error('Error generating link:', error);
        } finally {
            setLoading(false);
        }
    };

    const copyToClipboard = () => {
        navigator.clipboard.writeText(tempLink.tempLink);
        alert('Link copied to clipboard!');
    };

    return (
        <div className="file-sharing">
            <button onClick={generateLink} disabled={loading}>
                {loading ? 'Generating...' : 'Generate Temporary Link'}
            </button>
            
            {tempLink && (
                <div className="link-info">
                    <h3>Temporary Link Generated</h3>
                    <p><strong>Link:</strong> <a href={tempLink.tempLink} target="_blank" rel="noopener noreferrer">{tempLink.tempLink}</a></p>
                    <p><strong>Expires:</strong> {tempLink.expiresAt}</p>
                    <button onClick={copyToClipboard}>Copy Link</button>
                </div>
            )}
        </div>
    );
}

export default FileSharingComponent;
```

### Python Client

#### Basic Usage
```python
from temp_link_generator import create_temp_link

# Generate a temporary link
link_data = create_temp_link('my-bucket', 'path/to/file.txt')
print(f"Temporary link: {link_data['temp_link']}")
print(f"Expires: {link_data['expires_at']}")
```

#### Flask Integration
```python
from flask import Flask, request, jsonify
from temp_link_generator import create_temp_link

app = Flask(__name__)

@app.route('/api/share-file', methods=['POST'])
def share_file():
    data = request.get_json()
    bucket = data.get('bucket')
    key = data.get('key')
    
    if not bucket or not key:
        return jsonify({'error': 'Missing bucket or key'}), 400
    
    try:
        link_data = create_temp_link(bucket, key)
        return jsonify({
            'success': True,
            'temp_link': link_data['temp_link'],
            'expires_at': link_data['expires_at']
        })
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/share/<bucket>/<path:key>')
def share_file_page(bucket, key):
    link_data = create_temp_link(bucket, key)
    return f"""
    <html>
        <head><title>File Share</title></head>
        <body>
            <h1>File Share</h1>
            <p><strong>File:</strong> {bucket}/{key}</p>
            <p><strong>Temporary Link:</strong> <a href="{link_data['temp_link']}">{link_data['temp_link']}</a></p>
            <p><strong>Expires:</strong> {link_data['expires_at']}</p>
        </body>
    </html>
    """

if __name__ == '__main__':
    app.run(debug=True)
```

#### Django Integration
```python
# views.py
from django.http import JsonResponse
from django.views.decorators.csrf import csrf_exempt
from django.views.decorators.http import require_http_methods
import json
from temp_link_generator import create_temp_link

@csrf_exempt
@require_http_methods(["POST"])
def share_file(request):
    try:
        data = json.loads(request.body)
        bucket = data.get('bucket')
        key = data.get('key')
        
        if not bucket or not key:
            return JsonResponse({'error': 'Missing bucket or key'}, status=400)
        
        link_data = create_temp_link(bucket, key)
        
        return JsonResponse({
            'success': True,
            'temp_link': link_data['temp_link'],
            'expires_at': link_data['expires_at']
        })
    except Exception as e:
        return JsonResponse({'error': str(e)}, status=500)

# urls.py
from django.urls import path
from . import views

urlpatterns = [
    path('api/share-file/', views.share_file, name='share_file'),
]
```

#### Command Line Usage
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

## URL Structure

```
/temp/{access_key}/{timestamp}/{urlencoded_bucket}/{urlencoded_key}
```

Example:
```
/temp/my_access_key/1705312200/my-bucket/path%2Fto%2Ffile.txt
```

## Algorithm Details

### Link Generation (Client-Side)
```php
$timestamp = time();
$data = $timestamp . $bucket . $key . $access_key;
$link_key = md5($data);
$url = "/temp/{$access_key}/{$timestamp}/" . urlencode($bucket) . "/" . urlencode($key);
```

### Link Verification (Server-Side)
```php
// Check if timestamp is within expiry window
if ($timestamp + TEMP_LINK_EXPIRY > $current_time) {
    $expected_key = md5($timestamp . $bucket . $key . $access_key);
    if ($expected_key === $link_key) {
        return true; // Valid link
    }
}
```

## Features

1. **Client-Side Generation**: No server requests needed to create links
2. **Stateless Design**: No database or file storage required
3. **Secure Verification**: Uses MD5 hash of timestamp + bucket + key + access_key
4. **Automatic Expiration**: Links expire after exactly 1 hour
5. **Range Request Support**: Supports HTTP Range headers for partial downloads
6. **File Validation**: Server checks if the target file exists

## Security Considerations

- Temporary links bypass authentication completely
- Links are tied to the specific access key that created them
- The MD5 key includes a timestamp to prevent replay attacks
- Links automatically expire after 1 hour
- No persistent storage means no cleanup required
- Access key is visible in the URL (consider using HTTPS)

## Configuration

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

## Error Handling

- **404**: File not found or temporary link expired
- **400**: Invalid link format or missing parameters

## Advantages of Client-Side Generation

1. **No Server Requests**: Generate links instantly without API calls
2. **Better Performance**: No network latency for link creation
3. **Offline Capability**: Can generate links even when server is unreachable
4. **Scalable**: No server resources used for link generation
5. **Simple**: Easy to integrate into any application

## Testing

### PHP Test
```bash
php test_temp_links.php
```

### Python Test
```bash
python temp_link_generator.py test-bucket test-file.txt
```

### JavaScript Test
```javascript
// In browser console
const linkData = TempLinkGenerator.createTempLink('test-bucket', 'test-file.txt');
console.log('Test link:', linkData.tempLink);
```