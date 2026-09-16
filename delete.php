<?php

require __DIR__ . '/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die('ID không hợp lệ.');
}

try {
    $courseService->softDelete($id);

    header('Location: index.php?deleted=1');
    exit;
} catch (Throwable $e) {
    die('Lỗi: ' . $e->getMessage());
}