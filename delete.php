<?php

require __DIR__ . '/db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die('ID không hợp lệ.');
}

$stmt = $pdo->prepare(
    'UPDATE courses
     SET is_deleted = 1
     WHERE id = :id'
);

$stmt->execute([
    'id' => $id
]);

header('Location: index.php?deleted=1');
exit;