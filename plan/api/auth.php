<?php
/**
 * AJAX Authentication Validation API
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/User.php';

$userModel = new User();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check session login state
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'authenticated' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email']
            ]
        ]);
    } else {
        echo json_encode(['authenticated' => false]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'check_email') {
        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            echo json_encode(['available' => false, 'error' => 'Email parameter is empty.']);
            exit;
        }
        
        $user = $userModel->findByEmail($email);
        echo json_encode([
            'available' => ($user === null),
            'email' => $email
        ]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Invalid authentication request.']);
exit;
