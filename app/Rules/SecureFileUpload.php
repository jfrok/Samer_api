<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SecureFileUpload implements ValidationRule
{
    protected $allowedMimeTypes;
    protected $maxSize;

    public function __construct(array $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], int $maxSize = 5120)
    {
        $this->allowedMimeTypes = $allowedMimeTypes;
        $this->maxSize = $maxSize; // in KB
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if it's a valid uploaded file
        if (!$value instanceof \Illuminate\Http\UploadedFile) {
            $fail('The :attribute must be a valid file.');
            return;
        }

        // Check file size
        $fileSizeKB = $value->getSize() / 1024;
        if ($fileSizeKB > $this->maxSize) {
            $fail("The :attribute must not be larger than {$this->maxSize}KB.");
            return;
        }

        // Verify actual MIME type using finfo (not just extension)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actualMimeType = finfo_file($finfo, $value->getRealPath());
        finfo_close($finfo);

        if (!in_array($actualMimeType, $this->allowedMimeTypes)) {
            $fail('The :attribute has an invalid file type. Detected: ' . $actualMimeType);
            return;
        }

        // Additional check: verify extension matches MIME type
        $extension = strtolower($value->getClientOriginalExtension());
        $mimeToExtension = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
            'image/webp' => ['webp'],
        ];

        $validExtensions = $mimeToExtension[$actualMimeType] ?? [];
        if (!in_array($extension, $validExtensions)) {
            $fail('The :attribute extension does not match its actual file type.');
            return;
        }

        // Check for PHP code in image files (basic protection)
        $fileContent = file_get_contents($value->getRealPath());
        if (preg_match('/<\?php|<script|eval\(|base64_decode/i', $fileContent)) {
            $fail('The :attribute contains suspicious content.');
            return;
        }
    }
}
