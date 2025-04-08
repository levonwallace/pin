<?php
/**
 * S3Manager - Handles file operations with AWS S3
 * 
 * This class provides methods to interact with an S3 bucket for file storage
 * and retrieval, allowing the application to use S3 as a database.
 */

// Include Composer's autoloader
require_once __DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;

class S3Manager {
    private $s3Client;
    private $bucket;
    private $region;
    private $baseUrl;
    private $localCacheDir;
    private $useLocalCache;
    
    /**
     * Constructor for S3Manager
     * 
     * @param string $bucket The S3 bucket name
     * @param string $region The AWS region (e.g., 'us-east-1')
     * @param bool $useLocalCache Whether to use local caching for files
     */
    public function __construct($bucket, $region = 'us-east-1', $useLocalCache = true) {
        $this->bucket = $bucket;
        $this->region = $region;
        $this->baseUrl = "https://{$bucket}.s3.{$region}.amazonaws.com";
        $this->useLocalCache = $useLocalCache;
        $this->localCacheDir = __DIR__ . '/cache';
        
        // Initialize S3 Client
        // Credentials will be automatically sourced from environment variables,
        // IAM role, or shared credential file (e.g., ~/.aws/credentials)
        $this->s3Client = new S3Client([
            'region'  => $this->region,
            'version' => 'latest'
        ]);

        // Create cache directory if it doesn't exist
        if ($this->useLocalCache && !file_exists($this->localCacheDir)) {
            if (!mkdir($this->localCacheDir, 0755, true) && !is_dir($this->localCacheDir)) {
                // Handle error if directory creation fails
                // You might want to log this error or throw an exception
                error_log("S3Manager: Failed to create cache directory: {$this->localCacheDir}");
                $this->useLocalCache = false; // Disable cache if directory can't be created
            }
        }
    }
    
    /**
     * Get the base URL for the S3 bucket
     * 
     * @return string The base URL
     */
    public function getBaseUrl() {
        return $this->baseUrl;
    }
    
    /**
     * Read a file from S3
     * 
     * @param string $path The path to the file in S3 (key)
     * @return mixed The file contents as a string or false on failure
     */
    public function readFile($path) {
        $cachePath = $this->getCachePath($path);

        // Check local cache first if enabled
        if ($this->useLocalCache && file_exists($cachePath)) {
            // Optionally: Add logic to check S3 last modified time against cache file time
            return file_get_contents($cachePath);
        }
        
        try {
            $result = $this->s3Client->getObject([
                'Bucket' => $this->bucket,
                'Key'    => $path
            ]);
            
            $contents = $result['Body']->getContents();
            
            // Cache the file if successful
            if ($this->useLocalCache) {
                $this->cacheFile($path, $contents);
            }
            
            return $contents;

        } catch (S3Exception $e) {
            // Handle S3 specific errors (e.g., NoSuchKey)
            error_log("S3 Read Error (S3Exception) for {$path}: " . $e->getMessage());
            return false;
        } catch (AwsException $e) {
            // Handle general AWS SDK errors
            error_log("S3 Read Error (AwsException) for {$path}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Write a file to S3
     * 
     * @param string $path The path (key) for the file in S3
     * @param string $contents The file contents
     * @param string $contentType Optional content type (e.g., 'application/json')
     * @return bool True on success, false on failure
     */
    public function writeFile($path, $contents, $contentType = null) {
        try {
            $params = [
                'Bucket' => $this->bucket,
                'Key'    => $path,
                'Body'   => $contents,
            ];
            // Set content type if provided, otherwise S3 might guess or use default
            if ($contentType) {
                $params['ContentType'] = $contentType;
            }

            $this->s3Client->putObject($params);

            // Update local cache if write is successful
            if ($this->useLocalCache) {
                $this->cacheFile($path, $contents);
            }
            
            return true;

        } catch (AwsException $e) {
            // Handle AWS SDK errors
            error_log("S3 Write Error for {$path}: " . $e->getMessage());
            // Optionally remove from cache if write fails?
            // $this->deleteCacheFile($path);
            return false;
        }
    }
    
    /**
     * List files/objects in a specific S3 prefix (directory)
     * 
     * @param string $directory The prefix (directory path) to list. Use '' for root.
     * @return array An array of file keys (paths) or false on failure
     */
    public function listFiles($directory = '') {
        try {
            $params = [
                'Bucket' => $this->bucket,
                'Prefix' => $directory ? rtrim($directory, '/') . '/' : '', // Ensure prefix ends with /
            ];
            
            $results = $this->s3Client->listObjectsV2($params);
            $files = [];
            if (!empty($results['Contents'])) {
                foreach ($results['Contents'] as $object) {
                    // Exclude the directory placeholder itself if listing a specific directory
                    if ($object['Key'] !== $params['Prefix']) {
                        $files[] = $object['Key'];
                    }
                }
            }
            return $files;

        } catch (AwsException $e) {
            error_log("S3 List Error for prefix '{$directory}': " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if a file exists in S3
     * 
     * @param string $path The path (key) to the file in S3
     * @return bool True if the file exists, false otherwise
     */
    public function fileExists($path) {
        // Check local cache first if enabled and file exists there
        // Note: This doesn't guarantee it still exists in S3
        if ($this->useLocalCache && file_exists($this->getCachePath($path))) {
             // For stronger consistency, you might want to skip cache check 
             // or always check S3 regardless of cache status.
            // return true; 
        }

        try {
            $this->s3Client->headObject([
                'Bucket' => $this->bucket,
                'Key'    => $path
            ]);
            return true; // Object exists
        } catch (S3Exception $e) {
            if ($e->getAwsErrorCode() === 'NotFound' || $e->getStatusCode() === 404) {
                return false; // Object does not exist
            }
            // Log other S3 errors
            error_log("S3 Exists Error (S3Exception) for {$path}: " . $e->getMessage());
            return false;
        } catch (AwsException $e) {
            // Handle other AWS SDK errors
            error_log("S3 Exists Error (AwsException) for {$path}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a file from S3
     * 
     * @param string $path The path (key) to the file in S3
     * @return bool True on success, false on failure
     */
    public function deleteFile($path) {
        try {
            $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $path
            ]);
            
            // Remove from local cache if deletion is successful
            if ($this->useLocalCache) {
                $this->deleteCacheFile($path);
            }
            
            return true;

        } catch (AwsException $e) {
            error_log("S3 Delete Error for {$path}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the S3 URL for a file (useful for public access)
     * 
     * @param string $path The path to the file in S3
     * @return string The S3 URL
     */
    private function getS3Url($path) {
        // Consider using S3Client::getObjectUrl for potentially more robust URL generation
        // especially if dealing with special characters or different endpoint styles.
        return $this->baseUrl . '/' . ltrim($path, '/');
    }
    
    /**
     * Get the local cache path for an S3 object path
     * 
     * @param string $s3Path The path (key) of the object in S3
     * @return string The full local cache file path
     */
    private function getCachePath($s3Path) {
        // Use a more robust way to handle potential path collisions or invalid characters
        // Using md5 is simple but can have collisions. Consider sha1 or directory structure.
        // Ensure the base directory exists
        $cacheKey = md5($s3Path);
        $subDir = substr($cacheKey, 0, 2); // Example: create subdirectories like /cache/ab/
        $fullDir = $this->localCacheDir . '/' . $subDir;
        if ($this->useLocalCache && !is_dir($fullDir)) {
            @mkdir($fullDir, 0755, true);
        }
        return $fullDir . '/' . $cacheKey;
    }
    
    /**
     * Cache a file locally
     * 
     * @param string $s3Path The path (key) of the object in S3
     * @param string $contents The file contents
     */
    private function cacheFile($s3Path, $contents) {
        if (!$this->useLocalCache) return;
        $cachePath = $this->getCachePath($s3Path);
        $dir = dirname($cachePath);
        if (!is_dir($dir)) {
            // Attempt to create directory again just in case
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                error_log("S3Manager: Failed to create cache subdirectory: {$dir}");
                return; // Don't try to write if dir doesn't exist
            }
        }
        file_put_contents($cachePath, $contents);
    }

    /**
     * Delete a file from the local cache
     *
     * @param string $s3Path The path (key) of the object in S3
     */
    private function deleteCacheFile($s3Path) {
        if (!$this->useLocalCache) return;
        $cachePath = $this->getCachePath($s3Path);
        if (file_exists($cachePath)) {
            unlink($cachePath);
        }
    }
}
?> 