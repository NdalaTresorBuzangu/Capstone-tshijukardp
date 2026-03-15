<?php
/**
 * File Upload Service
 * 
 * Handles file upload operations with validation.
 */

namespace App\Services;

class FileUploadService
{
    private string $uploadDir;
    private array $allowedTypes;
    private int $maxSize;

    public function __construct(
        string $uploadDir = 'uploads',
        array $allowedTypes = ['image/jpeg', 'image/png', 'image/x-png', 'image/gif', 'image/webp', 'application/pdf'],
        int $maxSize = 5242880 // 5MB
    ) {
        $this->uploadDir = rtrim($uploadDir, '/\\');
        $this->allowedTypes = $allowedTypes;
        $this->maxSize = $maxSize;
    }

    /**
     * Upload a file
     */
    public function upload(array $file, string $subfolder = ''): array
    {
        // Validate file
        $validation = $this->validate($file);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }

        // Build target directory
        $targetDir = $this->getFullPath($subfolder);

        // Create directory if needed
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0777, true)) {
                return ['success' => false, 'message' => 'Failed to create upload directory.'];
            }
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

        // Move file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'message' => 'Failed to move uploaded file.'];
        }

        // Return relative path for storage
        $relativePath = $this->uploadDir . '/' . ($subfolder ? $subfolder . '/' : '') . $filename;

        return [
            'success' => true,
            'path' => $relativePath,
            'filename' => $filename,
            'fullPath' => $targetPath,
            'size' => $file['size'],
            'type' => $file['type']
        ];
    }

    /**
     * Validate uploaded file
     */
    public function validate(array $file): array
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => $this->getUploadErrorMessage($file['error'])];
        }

        // Check file size
        if ($file['size'] > $this->maxSize) {
            $maxMB = $this->maxSize / 1048576;
            return ['valid' => false, 'message' => "File too large. Maximum size is {$maxMB}MB."];
        }

        // Check file type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $this->allowedTypes)) {
            return ['valid' => false, 'message' => 'File type not allowed. Allowed: PDF, JPG, PNG, GIF, WebP.'];
        }

        return ['valid' => true];
    }

    /**
     * Delete a file
     */
    public function delete(string $relativePath): bool
    {
        $fullPath = $this->getBasePath() . DIRECTORY_SEPARATOR . $relativePath;

        if (file_exists($fullPath) && is_file($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

    /**
     * Check if file exists
     */
    public function exists(string $relativePath): bool
    {
        $fullPath = $this->getBasePath() . DIRECTORY_SEPARATOR . $relativePath;
        return file_exists($fullPath) && is_file($fullPath);
    }

    /**
     * Get full path for subfolder
     */
    private function getFullPath(string $subfolder = ''): string
    {
        $basePath = $this->getBasePath() . DIRECTORY_SEPARATOR . $this->uploadDir;

        if ($subfolder) {
            $basePath .= DIRECTORY_SEPARATOR . $subfolder;
        }

        return $basePath;
    }

    /**
     * Get application base path
     */
    private function getBasePath(): string
    {
        return dirname(__DIR__); // Project root when Services/ is at root
    }

    /**
     * Get human-readable upload error message
     */
    private function getUploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds server maximum size.',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form maximum size.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by extension.',
            default => 'Unknown upload error.'
        };
    }

    /**
     * Set allowed file types
     */
    public function setAllowedTypes(array $types): void
    {
        $this->allowedTypes = $types;
    }

    /**
     * Set maximum file size
     */
    public function setMaxSize(int $bytes): void
    {
        $this->maxSize = $bytes;
    }
}
