<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/interactions.php';

header('Content-Type: application/json; charset=utf-8');

if (current_user_id() === null) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'You must be logged in to submit.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

$ct = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
$ctLower = strtolower(trim(explode(';', $ct, 2)[0]));
if ($ctLower !== 'application/json') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Content-Type must be application/json']);
    exit;
}

$data = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid JSON']);
    exit;
}

$title = trim((string) ($data['title'] ?? ''));
$body = trim((string) ($data['recipe_body'] ?? ''));
$tips = trim((string) ($data['cooking_tips'] ?? ''));
$notes = trim((string) ($data['experience_notes'] ?? ''));

if ($title === '' || strlen($title) < 3) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Title must be at least 3 characters.']);
    exit;
}
if ($body === '' || strlen($body) < 20) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Recipe / story body should be at least 20 characters.']);
    exit;
}

$pdo = db();
$uid = current_user_id();
$stmt = $pdo->prepare(
    'INSERT INTO community_recipes (user_id, title, recipe_body, cooking_tips, experience_notes) VALUES (:u, :t, :b, :tips, :notes)'
);
$stmt->execute([
    ':u' => $uid,
    ':t' => $title,
    ':b' => $body,
    ':tips' => $tips !== '' ? $tips : null,
    ':notes' => $notes !== '' ? $notes : null,
]);
$newId = (int) $pdo->lastInsertId();

log_user_interaction($pdo, $uid, 'community_submit', 'community_recipe', $newId, ['title' => $title]);

echo json_encode([
    'ok' => true,
    'message' => 'Your submission was published to the Community Cookbook.',
    'id' => $newId,
]);
