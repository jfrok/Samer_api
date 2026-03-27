<?php

namespace App\Traits;

trait SanitizesInput
{
    /**
     * Sanitize a search query string.
     * Removes HTML tags, special characters, and limits length.
     *
     * @param string|null $search
     * @param int $maxLength
     * @return string|null
     */
    protected function sanitizeSearch(?string $search, int $maxLength = 100): ?string
    {
        if (empty($search)) {
            return null;
        }

        // Remove HTML tags
        $search = strip_tags($search);

        // Remove special SQL characters (prevent SQL injection)
        // Keep only letters (any language), numbers, spaces, hyphens, and underscores
        $search = preg_replace('/[^\p{L}\p{N}\s\-\_]/u', '', $search);

        // Remove multiple spaces
        $search = preg_replace('/\s+/', ' ', $search);

        // Limit length
        $search = substr($search, 0, $maxLength);

        // Trim whitespace
        $search = trim($search);

        return empty($search) ? null : $search;
    }

    /**
     * Sanitize HTML content while preserving basic formatting.
     * Allows only safe HTML tags.
     *
     * @param string|null $html
     * @param int $maxLength
     * @return string|null
     */
    protected function sanitizeHtml(?string $html, int $maxLength = 2000): ?string
    {
        if (empty($html)) {
            return null;
        }

        // Allow only safe tags
        $allowedTags = '<p><br><strong><em><u><ul><ol><li><a><h1><h2><h3>';
        $html = strip_tags($html, $allowedTags);

        // Remove potential XSS in attributes
        $html = preg_replace('/<a([^>]*)>/i', '<a$1 rel="noopener noreferrer">', $html);
        $html = preg_replace('/on\w+\s*=\s*"[^"]*"/i', '', $html); // Remove event handlers
        $html = preg_replace('/javascript:/i', '', $html); // Remove javascript: protocol

        // Limit length
        $html = substr($html, 0, $maxLength);

        return trim($html);
    }

    /**
     * Sanitize email address.
     *
     * @param string|null $email
     * @return string|null
     */
    protected function sanitizeEmail(?string $email): ?string
    {
        if (empty($email)) {
            return null;
        }

        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * Sanitize phone number (remove non-numeric characters except +).
     *
     * @param string|null $phone
     * @return string|null
     */
    protected function sanitizePhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Keep only numbers, +, spaces, hyphens, and parentheses
        $phone = preg_replace('/[^0-9\+\s\-\(\)]/', '', $phone);

        return trim($phone);
    }
}
