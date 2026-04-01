<?php
declare(strict_types=1);

function log_user_interaction(PDO $pdo, ?int $userId, string $actionType, ?string $entityType = null, ?int $entityId = null, ?array $meta = null): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO user_interactions (user_id, action_type, entity_type, entity_id, meta_json) VALUES (:u, :a, :t, :i, :m)'
    );
    $stmt->execute([
        ':u' => $userId,
        ':a' => $actionType,
        ':t' => $entityType,
        ':i' => $entityId,
        ':m' => $meta !== null ? json_encode($meta) : null,
    ]);
}
