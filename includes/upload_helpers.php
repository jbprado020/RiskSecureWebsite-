<?php

declare(strict_types=1);

/**
 * Validate uploaded file MIME type by checking actual file content (magic bytes).
 * Prevents uploading executable files renamed with document extensions.
 * 
 * @param string $filePath Path to the uploaded file
 * @param array $allowedMimeTypes Allowed MIME types
 * @return bool True if MIME type is allowed, false otherwise
 */
function validateUploadMimeType(string $filePath, array $allowedMimeTypes): bool
{
    if (!is_file($filePath)) {
        return false;
    }

    // Use finfo to detect actual MIME type (checks magic bytes, not just extension)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    return in_array($mimeType, $allowedMimeTypes, true);
}

/**
 * Prepare upload paths and move incoming upload to a temporary location.
 * Returns array with keys: tempPath, finalPath, relativePath
 * Throws RuntimeException on failure.
 */
function prepareUploadTemp(array $file, string $uploadDir, string $prefix = ''): array
{
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Unable to create upload directory.');
        }
    }

    $tmpDir = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . 'tmp';
    if (!is_dir($tmpDir)) {
        if (!mkdir($tmpDir, 0755, true) && !is_dir($tmpDir)) {
            throw new RuntimeException('Unable to create temp upload directory.');
        }
    }

    $originalName = (string) ($file['name'] ?? 'upload');
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $safeBaseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
    $unique = bin2hex(random_bytes(4));
    $timestamp = date('YmdHis');

    $finalName = ($prefix !== '' ? $prefix . '_' : '') . $timestamp . '_' . $unique . '_' . $safeBaseName . ($ext !== '' ? '.' . $ext : '');

    $tempPath = $tmpDir . DIRECTORY_SEPARATOR . $finalName;
    $finalPath = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $finalName;
    $relativePath = 'uploads/' . $finalName;

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Upload missing or invalid temporary file.');
    }

    if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
        throw new RuntimeException('Unable to move uploaded file to temp location.');
    }

    return [
        'tempPath' => $tempPath,
        'finalPath' => $finalPath,
        'relativePath' => $relativePath,
        'finalName' => $finalName,
    ];
}


/**
 * Process an uploaded file with validations: extension, MIME type, size, and simple content checks.
 * Returns same array as prepareUploadTemp on success.
 * Throws RuntimeException on validation failure.
 *
 * @param array $file The $_FILES entry
 * @param string $uploadDir Absolute uploads directory
 * @param string $prefix Filename prefix (eg. user id)
 * @param array $allowedExt Allowed file extensions (lowercase, without dot)
 * @param array $allowedMimeTypes Allowed MIME types
 * @param int $maxBytes Maximum allowed file size in bytes
 */
function processUpload(array $file, string $uploadDir, string $prefix, array $allowedExt, array $allowedMimeTypes, int $maxBytes): array
{
    // Basic PHP upload error
    if (!isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload failed (PHP error).');
    }

    // Extension check
    $originalName = (string) ($file['name'] ?? 'upload');
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($ext === '') {
        throw new RuntimeException('Uploaded file missing extension.');
    }
    if (!in_array($ext, $allowedExt, true)) {
        throw new RuntimeException('Unsupported file type.');
    }

    // Size check (before moving)
    if (isset($file['size']) && $file['size'] > 0) {
        if ($file['size'] > $maxBytes) {
            throw new RuntimeException('File exceeds maximum allowed size.');
        }
    }

    // Move to temp then validate content
    $uploadInfo = prepareUploadTemp($file, $uploadDir, $prefix);

    // Validate MIME type using finfo on the temp file
    if (!validateUploadMimeType($uploadInfo['tempPath'], $allowedMimeTypes)) {
        // cleanup temp
        if (is_file($uploadInfo['tempPath'])) {
            @unlink($uploadInfo['tempPath']);
        }
        throw new RuntimeException('Uploaded file MIME type is not allowed.');
    }

    // Re-check filesize from temp file (server-side reliable)
    $actualSize = @filesize($uploadInfo['tempPath']);
    if ($actualSize === false || $actualSize > $maxBytes) {
        if (is_file($uploadInfo['tempPath'])) {
            @unlink($uploadInfo['tempPath']);
        }
        throw new RuntimeException('Uploaded file is too large.');
    }

    // Simple content scanning for executable PHP code
    $handle = @fopen($uploadInfo['tempPath'], 'rb');
    if ($handle !== false) {
        $firstBytes = fread($handle, 4096);
        fclose($handle);
        if (strpos($firstBytes, '<?php') !== false || stripos($firstBytes, '<script') !== false) {
            @unlink($uploadInfo['tempPath']);
            throw new RuntimeException('Uploaded file contains disallowed content.');
        }
    }

    // Harden permissions for temp file
    @chmod($uploadInfo['tempPath'], 0640);

    return $uploadInfo;
}

/**
 * Finalize upload after DB commit: move temp file to final location and
 * perform cleanup on failure (delete DB record using provided delete callback).
 *
 * @param string $tempPath
 * @param string $finalPath
 * @param callable $onFailure function(int|null $insertId = null): void used to undo DB row
 * @param int|null $insertId optional id of inserted record
 */
function finalizeUploadMove(string $tempPath, string $finalPath, callable $onFailure, ?int $insertId = null): void
{
    // Attempt atomic rename
    if (!@rename($tempPath, $finalPath)) {
        // try to cleanup DB if provided
        try {
            $onFailure($insertId);
        } catch (Throwable $_) {
            // continue to throw original error
        }

        if (is_file($tempPath)) {
            @unlink($tempPath);
        }

        throw new RuntimeException('Failed to finalize uploaded file to final location. Changes rolled back.');
    }
}
