<?php
namespace App\Helpers;

class FileUpload {
    public static function uploadProductImage(array $file): string|false {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        // Limit size to 2MB
        if ($file['size'] > 2 * 1024 * 1024) {
            return false;
        }

        // Check mime type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedTypes)) {
            return false;
        }

        // Create directory if it doesn't exist
        $uploadDir = dirname(dirname(__DIR__)) . '/public/assets/uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Unique name
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('prod_', true) . '.' . $ext;
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            return '/assets/uploads/products/' . $fileName;
        }

        return false;
    }
}
