# Temporary File Links

This S3-compatible file server now supports temporary file links with the following features:

- **MD5-based keys**: Generated using timestamp + bucket + key + access_key
- **1-hour expiration**: Links automatically expire after 3600 seconds
- **No authentication required**: Access files without AWS credentials
- **Automatic cleanup**: Expired links are automatically removed

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
  "temp_link": "http://your-server.com/temp/abc123def456...",
  "expires_at": "2024-01-15 14:30:00",
  "expires_in_seconds": 3600
}
```

### Accessing Files via Temporary Link

Simply visit the temporary link URL in a browser or use curl:

```bash
curl "http://your-server.com/temp/abc123def456..."
```

The file will be downloaded without requiring any authentication.

## Features

1. **Secure Key Generation**: Uses MD5 hash of timestamp + bucket + key + access_key
2. **Automatic Expiration**: Links expire after exactly 1 hour
3. **Range Request Support**: Supports HTTP Range headers for partial downloads
4. **Automatic Cleanup**: Expired links are automatically removed from storage
5. **File Validation**: Checks if the target file exists before creating a link

## Storage

Temporary links are stored in `temp_links.json` in the root directory. The file structure is:

```json
{
  "md5_hash_key": {
    "bucket": "bucket-name",
    "key": "file/path.txt",
    "access_key": "access_key_used",
    "created_at": 1705312200,
    "expires_at": 1705315800
  }
}
```

## Security Considerations

- Temporary links bypass authentication completely
- Links are tied to the specific access key that created them
- Expired links are automatically cleaned up
- The MD5 key includes a timestamp to prevent replay attacks

## Error Handling

- **404**: File not found or temporary link expired
- **400**: Missing bucket or key parameters
- **401**: Authentication required for creating links