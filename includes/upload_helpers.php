<?php

declare(strict_types=1);

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
