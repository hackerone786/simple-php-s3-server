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

Include the generator script and use it:

```php
<?php
require_once 'temp_link_generator.php';

// Generate a temporary link
$link_data = create_temp_link('my-bucket', 'path/to/file.txt');
echo "Temporary link: " . $link_data['temp_link'];
echo "Expires: " . $link_data['expires_at'];
?>
```

### JavaScript Client

Include the generator script and use it:

```javascript
// Load the script first
// <script src="temp_link_generator.js"></script>

// Generate a temporary link
const linkData = TempLinkGenerator.createTempLink('my-bucket', 'path/to/file.txt');
console.log('Temporary link:', linkData.tempLink);
console.log('Expires at:', linkData.expiresAt);
```

### Command Line Usage

```bash
php temp_link_generator.php my-bucket path/to/file.txt
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

## Error Handling

- **404**: File not found or temporary link expired
- **400**: Invalid link format or missing parameters

## Advantages of Client-Side Generation

1. **No Server Requests**: Generate links instantly without API calls
2. **Better Performance**: No network latency for link creation
3. **Offline Capability**: Can generate links even when server is unreachable
4. **Scalable**: No server resources used for link generation
5. **Simple**: Easy to integrate into any application

## Example Integration

### Web Application
```html
<script src="temp_link_generator.js"></script>
<script>
function shareFile(bucket, key) {
    const linkData = TempLinkGenerator.createTempLink(bucket, key);
    // Copy link to clipboard or display to user
    navigator.clipboard.writeText(linkData.tempLink);
    alert('Temporary link copied to clipboard!');
}
</script>
```

### Mobile App
```javascript
// React Native example
import { createTempLink } from './temp_link_generator.js';

const shareFile = (bucket, key) => {
    const linkData = createTempLink(bucket, key);
    Share.share({
        message: linkData.tempLink,
        title: 'Temporary File Link'
    });
};
```