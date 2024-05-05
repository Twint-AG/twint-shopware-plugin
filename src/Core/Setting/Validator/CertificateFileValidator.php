<?php

declare(strict_types=1);

namespace Twint\Core\Setting\Validator;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class CertificateFileValidator
{
    public const ALLOWED_EXTENSIONS = ['.p12'];

    public const MAX_SIZE = 1024 * 1024; // 1MB

    public function validate(UploadedFile $file): bool
    {
        // Validate file extension
        $originalExtension = $file->getClientOriginalExtension();
        if (!in_array(strtolower($originalExtension), static::ALLOWED_EXTENSIONS, true)) {
            return false;
        }
        // Validate file size
        return $file->getSize() <= static::MAX_SIZE;
    }
}
