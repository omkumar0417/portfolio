<?php
/**
 * AJAX Notes Specifications API
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/Note.php';

// Access protection
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthenticated access.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$noteModel = new Note();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $noteId = (int)($_GET['id'] ?? 0);
    if ($noteId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid note ID.']);
        exit;
    }

    try {
        $note = $noteModel->findById($noteId, $userId);
        if (!$note) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Note not found.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'note' => $note
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
