<?php

namespace App\Controllers;

use App\Services\UploadService;
use App\Utilities\Request;
use App\Utilities\Response;

declare(strict_types=1);

class DocumentController
{
    private UploadService $uploads;

    public function __construct()
    {
        $this->uploads = new UploadService();
    }

    public function upload(): void
    {
        $file = Request::file('document_file');
        $result = $this->uploads->handleUpload($file);

        if ($result['success']) {
            Response::redirect('/documents.php');
        }

        echo $result['message'];
    }
}
