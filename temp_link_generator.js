/**
 * Client-side Temporary Link Generator (JavaScript)
 * 
 * This script allows you to generate temporary links on the client side
 * without making any server requests.
 */

// Configuration - Change these to match your server
const SERVER_URL = 'http://localhost'; // Your server URL
const ACCESS_KEY = 'put_your_key_here'; // Your access key
const TEMP_LINK_EXPIRY = 3600; // 1 hour in seconds

/**
 * Generate MD5 hash (you'll need to include an MD5 library)
 * For this example, we'll use a simple implementation
 * In production, use a proper MD5 library like crypto-js
 */
function md5(string) {
    // This is a placeholder - you should use a proper MD5 library
    // Example: npm install crypto-js
    // import md5 from 'crypto-js/md5';
    // return md5(string).toString();
    
    // For now, we'll use a simple hash function (not MD5)
    let hash = 0;
    if (string.length === 0) return hash.toString();
    for (let i = 0; i < string.length; i++) {
        const char = string.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash; // Convert to 32bit integer
    }
    return Math.abs(hash).toString(16);
}

/**
 * Generate a temporary link for a file
 * 
 * @param {string} bucket The bucket name
 * @param {string} key The file key/path
 * @param {string} accessKey The access key
 * @param {number} timestamp Unix timestamp (optional, defaults to current time)
 * @returns {string} The temporary link URL
 */
function generateTempLink(bucket, key, accessKey, timestamp = null) {
    if (timestamp === null) {
        timestamp = Math.floor(Date.now() / 1000);
    }
    
    // Generate the MD5 hash
    const data = timestamp + bucket + key + accessKey;
    const linkKey = md5(data);
    
    // Build the URL
    const encodedBucket = encodeURIComponent(bucket);
    const encodedKey = encodeURIComponent(key);
    
    return `${SERVER_URL}/temp/${accessKey}/${timestamp}/${encodedBucket}/${encodedKey}`;
}

/**
 * Generate a temporary link with current timestamp
 * 
 * @param {string} bucket The bucket name
 * @param {string} key The file key/path
 * @returns {object} Object containing link URL and expiry information
 */
function createTempLink(bucket, key) {
    const timestamp = Math.floor(Date.now() / 1000);
    const linkUrl = generateTempLink(bucket, key, ACCESS_KEY, timestamp);
    const expiresAt = timestamp + TEMP_LINK_EXPIRY;
    
    return {
        tempLink: linkUrl,
        timestamp: timestamp,
        expiresAt: new Date(expiresAt * 1000).toISOString(),
        expiresInSeconds: TEMP_LINK_EXPIRY
    };
}

/**
 * Generate a temporary link with a specific timestamp
 * 
 * @param {string} bucket The bucket name
 * @param {string} key The file key/path
 * @param {number} timestamp Unix timestamp
 * @returns {object} Object containing link URL and expiry information
 */
function createTempLinkWithTimestamp(bucket, key, timestamp) {
    const linkUrl = generateTempLink(bucket, key, ACCESS_KEY, timestamp);
    const expiresAt = timestamp + TEMP_LINK_EXPIRY;
    
    return {
        tempLink: linkUrl,
        timestamp: timestamp,
        expiresAt: new Date(expiresAt * 1000).toISOString(),
        expiresInSeconds: TEMP_LINK_EXPIRY
    };
}

// Example usage
if (typeof module !== 'undefined' && module.exports) {
    // Node.js usage
    module.exports = {
        generateTempLink,
        createTempLink,
        createTempLinkWithTimestamp
    };
} else {
    // Browser usage
    window.TempLinkGenerator = {
        generateTempLink,
        createTempLink,
        createTempLinkWithTimestamp
    };
    
    // Example usage in browser
    console.log('TempLinkGenerator loaded. Usage:');
    console.log('TempLinkGenerator.createTempLink("my-bucket", "path/to/file.txt")');
}

// Example usage:
// const linkData = createTempLink('my-bucket', 'path/to/file.txt');
// console.log('Temporary link:', linkData.tempLink);
// console.log('Expires at:', linkData.expiresAt);