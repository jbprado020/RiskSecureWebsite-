<?php

namespace App\Validators;

declare(strict_types=1);

class FileValidator
{
    public static function allowedExtensions(): array
    {
        return ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    }

    public static function allowedMimeTypes(): array
    {
        return [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
    }
}
