# Temporary File Links (Stateless)

This S3-compatible file server now supports stateless temporary file links with the following features:

- **MD5-based keys**: Generated using timestamp + bucket + key + access_key
- **1-hour expiration**: Links automatically expire after 3600 seconds
- **No storage required**: Links are verified on-the-fly without saving to disk
- **No authentication required**: Access files without AWS credentials

## How It Works

The system uses a simple algorithm that doesn't require storing links:

1. **Link Creation**: Generate MD5 hash of `timestamp + bucket + key + access_key`
2. **Link Verification**: Check all possible timestamps within the expiry window
3. **URL Format**: `/temp/{md5_key}/{encoded_bucket}/{encoded_key}`

## Usage

### Creating a Temporary Link

To create a temporary link for a file, send a POST request to `/temp-link/create` with the following JSON body:

```bash
curl -X POST http://your-server.com/temp-link/create \
  -H "Authorization: AWS4-HMAC-SHA256 Credential=your_access_key/..." \
  -H "Content-Type: application/json" \
  -d '{
    "bucket": "your-bucket",
    "key": "path/to/your/file.txt"
  }'
```

**Response:**
```json
{
  "temp_link": "http://your-server.com/temp/abc123def456.../your-bucket/path%2Fto%2Fyour%2Ffile.txt",
  "expires_at": "2024-01-15 14:30:00",
  "expires_in_seconds": 3600
}
```

### Accessing Files via Temporary Link

Simply visit the temporary link URL in a browser or use curl:

```bash
curl "http://your-server.com/temp/abc123def456.../your-bucket/path%2Fto%2Fyour%2Ffile.txt"
```

The file will be downloaded without requiring any authentication.

## Algorithm Details

### Link Generation
```php
$timestamp = time();
$data = $timestamp . $bucket . $key . $access_key;
$link_key = md5($data);
```

### Link Verification
```php
// Check all possible timestamps within the expiry window
for ($timestamp = $current_time - TEMP_LINK_EXPIRY; $timestamp <= $current_time; $timestamp++) {
    $expected_key = md5($timestamp . $bucket . $key . $access_key);
    if ($expected_key === $link_key && $timestamp + TEMP_LINK_EXPIRY > $current_time) {
        return true; // Valid link
    }
}
```

## Features

1. **Stateless Design**: No database or file storage required
2. **Secure Key Generation**: Uses MD5 hash of timestamp + bucket + key + access_key
3. **Automatic Expiration**: Links expire after exactly 1 hour
4. **Range Request Support**: Supports HTTP Range headers for partial downloads
5. **File Validation**: Checks if the target file exists before creating a link

## Security Considerations

- Temporary links bypass authentication completely
- Links are tied to the specific access key that created them
- The MD5 key includes a timestamp to prevent replay attacks
- Links automatically expire after 1 hour
- No persistent storage means no cleanup required

## URL Structure

```
/temp/{md5_hash}/{urlencoded_bucket}/{urlencoded_key}
```

Example:
```
/temp/a1b2c3d4e5f6.../my-bucket/path%2Fto%2Ffile.txt
```

## Error Handling

- **404**: File not found or temporary link expired
- **400**: Missing bucket/key parameters or invalid link format
- **401**: Authentication required for creating links

## Advantages of Stateless Approach

1. **No Storage Overhead**: No need to store or manage link data
2. **No Cleanup Required**: Expired links are automatically invalid
3. **Scalable**: Works across multiple server instances
4. **Simple**: Easy to understand and maintain
5. **Secure**: No persistent data that could be compromised