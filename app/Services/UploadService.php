<?php

namespace App\Services;

use App\Utilities\Database;
use App\Utilities\Logger;

declare(strict_types=1);

class UploadService
{
    public function handleUpload(array $file): array
    {
        // Basic wrapper that uses existing helpers; validate file exists
        if (!isset($file) || !is_array($file)) {
            return ['success' => false, 'message' => 'No file provided.'];
        }

        // For now defer to existing upload_helpers functions
        try {
            require_once __DIR__ . '/../../includes/upload_helpers.php';

            $uploadDir = __DIR__ . '/../../uploads';
            $info = prepareUploadTemp($file, $uploadDir, 'doc');
            // The actual DB insert and finalize logic is in documents.php; here we only move file

            return ['success' => true, 'message' => 'Uploaded to temp location', 'info' => $info];
        } catch (\Throwable $e) {
            Logger::error('UploadService error', ['exception' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Upload failed.'];
        }
    }
}
