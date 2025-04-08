<?php
/**
 * Configuration file for the Pin application
 * 
 * This file contains configuration settings for the application,
 * including S3 bucket settings and other application settings.
 */

// S3 Configuration
$config = [
    // S3 bucket settings
    's3' => [
        'bucket' => 'piiiiiiin-dev', // Replace with your S3 bucket name
        'region' => 'us-east-1',       // Replace with your AWS region
        'use_local_cache' => false,     // Whether to use local caching for files
    ],
    
    // Application settings
    'app' => [
        'name' => 'Pin',
        'version' => '1.0.0',
        'debug' => false,
    ],
    
    // File paths
    'paths' => [
        'dms' => 'dms',
        'chats' => 'chats',
        'pins' => 'pins',
        'audio' => 'audio',
        'images' => 'images',
    ],
];

// Include the S3Manager class
require_once __DIR__ . '/S3Manager.php';

// Initialize the S3Manager
$s3Manager = new S3Manager(
    $config['s3']['bucket'],
    $config['s3']['region'],
    $config['s3']['use_local_cache']
);

// Function to get the S3Manager instance
function getS3Manager() {
    global $s3Manager;
    return $s3Manager;
}

// Function to get a configuration value
function getConfig($key) {
    global $config;
    
    // Split the key by dots to access nested values
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return null;
        }
        $value = $value[$k];
    }
    
    return $value;
}

// Function to get a file path
function getFilePath($type, $filename) {
    $path = getConfig("paths.{$type}");
    return $path . '/' . $filename;
}

// Function to read a file from S3
function s3ReadFile($type, $filename) {
    $s3Manager = getS3Manager();
    $path = getFilePath($type, $filename);
    return $s3Manager->readFile($path);
}

// Function to write a file to S3
function s3WriteFile($type, $filename, $contents) {
    $s3Manager = getS3Manager();
    $path = getFilePath($type, $filename);
    return $s3Manager->writeFile($path, $contents);
}

// Function to check if a file exists in S3
function s3FileExists($type, $filename) {
    $s3Manager = getS3Manager();
    $path = getFilePath($type, $filename);
    return $s3Manager->fileExists($path);
}

// Function to delete a file from S3
function s3DeleteFile($type, $filename) {
    $s3Manager = getS3Manager();
    $path = getFilePath($type, $filename);
    return $s3Manager->deleteFile($path);
}

// Function to list files in a directory
function s3ListFiles($type) {
    $s3Manager = getS3Manager();
    $path = getConfig("paths.{$type}");
    return $s3Manager->listFiles($path);
}
?> 