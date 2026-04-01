<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (current_user_id() === null) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Please log in first.']);
    exit;
}

require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

$email = trim((string) ($_POST['email'] ?? ''));
$first = trim((string) ($_POST['first_name'] ?? ''));
$last = trim((string) ($_POST['last_name'] ?? ''));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Please enter a valid email.']);
    exit;
}

try {
    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO newsletter_signups (email, first_name, last_name) VALUES (:e, :f, :l)'
    );
    $stmt->execute([
        ':e' => $email,
        ':f' => $first !== '' ? $first : null,
        ':l' => $last !== '' ? $last : null,
    ]);
    echo json_encode(['ok' => true, 'message' => 'Thanks — you are signed up for updates.']);
} catch (PDOException $e) {
    if ((int) $e->errorInfo[1] === 1062) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'message' => 'This email is already on our list.']);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Could not save — try again later.']);
    }
}
