<?php
/**
 * Security, sanitization, and protection helpers
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate CSRF Token and store in session
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF Token matching
 */
function validateCsrfToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Helper to echo hidden CSRF input field
 */
function csrfInput(): void {
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

/**
 * Escape HTML output for rendering (XSS Protection)
 */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Recursively sanitize arrays / parameters
 */
function sanitizeArray(array $data): array {
    $sanitized = [];
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $sanitized[$key] = sanitizeArray($value);
        } else {
            $sanitized[$key] = htmlspecialchars(trim((string)$value), ENT_NOQUOTES, 'UTF-8');
        }
    }
    return $sanitized;
}

/**
 * Basic session-based rate-limiting helper.
 * Returns false if the limit is exceeded.
 */
function checkRateLimit(string $key, int $limit = 10, int $seconds = 60): bool {
    $now = time();
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = [];
    }

    // Filter out historical timestamps outside of range
    $_SESSION['rate_limit'][$key] = array_filter(
        $_SESSION['rate_limit'][$key],
        fn($timestamp) => $timestamp > ($now - $seconds)
    );

    if (count($_SESSION['rate_limit'][$key]) >= $limit) {
        return false;
    }

    $_SESSION['rate_limit'][$key][] = $now;
    return true;
}

/**
 * Verifies that the request is an AJAX request
 */
function isAjaxRequest(): bool {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}
