#!/usr/bin/env python3
"""
Client-side Temporary Link Generator (Python)

This script allows you to generate temporary links on the client side
without making any server requests.
"""

import hashlib
import time
import urllib.parse
import sys
from datetime import datetime, timedelta

# Configuration - Change these to match your server
SERVER_URL = 'http://localhost'  # Your server URL
ACCESS_KEY = 'put_your_key_here'  # Your access key
TEMP_LINK_EXPIRY = 3600  # 1 hour in seconds


def generate_temp_link(bucket, key, access_key, timestamp=None):
    """
    Generate a temporary link for a file
    
    Args:
        bucket (str): The bucket name
        key (str): The file key/path
        access_key (str): The access key
        timestamp (int, optional): Unix timestamp (defaults to current time)
    
    Returns:
        str: The temporary link URL
    """
    if timestamp is None:
        timestamp = int(time.time())
    
    # Generate the MD5 hash
    data = f"{timestamp}{bucket}{key}{access_key}"
    link_key = hashlib.md5(data.encode('utf-8')).hexdigest()
    
    # Build the URL
    encoded_bucket = urllib.parse.quote(bucket)
    encoded_key = urllib.parse.quote(key)
    
    return f"{SERVER_URL}/temp/{access_key}/{timestamp}/{encoded_bucket}/{encoded_key}"


def create_temp_link(bucket, key):
    """
    Generate a temporary link with current timestamp
    
    Args:
        bucket (str): The bucket name
        key (str): The file key/path
    
    Returns:
        dict: Dictionary containing link URL and expiry information
    """
    timestamp = int(time.time())
    link_url = generate_temp_link(bucket, key, ACCESS_KEY, timestamp)
    expires_at = timestamp + TEMP_LINK_EXPIRY
    
    return {
        'temp_link': link_url,
        'timestamp': timestamp,
        'expires_at': datetime.fromtimestamp(expires_at).isoformat(),
        'expires_in_seconds': TEMP_LINK_EXPIRY
    }


def create_temp_link_with_timestamp(bucket, key, timestamp):
    """
    Generate a temporary link with a specific timestamp
    
    Args:
        bucket (str): The bucket name
        key (str): The file key/path
        timestamp (int): Unix timestamp
    
    Returns:
        dict: Dictionary containing link URL and expiry information
    """
    link_url = generate_temp_link(bucket, key, ACCESS_KEY, timestamp)
    expires_at = timestamp + TEMP_LINK_EXPIRY
    
    return {
        'temp_link': link_url,
        'timestamp': timestamp,
        'expires_at': datetime.fromtimestamp(expires_at).isoformat(),
        'expires_in_seconds': TEMP_LINK_EXPIRY
    }


def main():
    """Command line interface"""
    if len(sys.argv) < 3:
        print("Usage: python temp_link_generator.py <bucket> <key>")
        print("Example: python temp_link_generator.py my-bucket path/to/file.txt")
        sys.exit(1)
    
    bucket = sys.argv[1]
    key = sys.argv[2]
    
    link_data = create_temp_link(bucket, key)
    
    print("=== Temporary Link Generated ===")
    print(f"Bucket: {bucket}")
    print(f"Key: {key}")
    print(f"Link: {link_data['temp_link']}")
    print(f"Expires: {link_data['expires_at']}")
    print(f"Expires in: {link_data['expires_in_seconds']} seconds")
    print("===============================")


if __name__ == "__main__":
    main()