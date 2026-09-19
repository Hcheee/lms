<?php

require __DIR__ . '/../bootstrap.php';

$csvFile = __DIR__ . '/../courses_5000.csv';
$logFile = __DIR__ . '/../storage/import-errors.log';

if (!file_exists($csvFile)) {
    die("Không tìm thấy file courses_5000.csv\n");
}

$logHandle = fopen($logFile, 'w');

if ($logHandle === false) {
    die("Không thể tạo file log.\n");
}

$file = fopen($csvFile, 'r');

if ($file === false) {
    die("Không thể mở file CSV.\n");
}

// Bỏ dòng header
fgetcsv($file);

$processed = 0;
$inserted = 0;
$skipped = 0;

$batch = [];
$batchSize = 500;

$seenSlugs = [];

$startTime = microtime(true);

$pdo = $connection->getPdo();

try {

    // Bắt đầu transaction
    $pdo->beginTransaction();

    while (($row = fgetcsv($file)) !== false) {

        $processed++;
        

        $title = trim($row[0] ?? '');
        $slug = trim($row[1] ?? '');
        $level = trim($row[2] ?? '');
        $duration = trim($row[3] ?? '');
        $isPublished = (int) ($row[4] ?? 0);

        $error = '';

        // Kiểm tra title
        if ($title === '') {
            $error = 'Thiếu title';
        }

        // Kiểm tra level
        elseif (!in_array(
            $level,
            ['co-ban', 'trung-cap', 'nang-cao'],
            true
        )) {
            $error = 'Level không hợp lệ';
        }

        // Kiểm tra duration
        elseif (
            filter_var($duration, FILTER_VALIDATE_INT) === false
        ) {
            $error = 'Duration phải là số nguyên';
        }

        elseif ((int) $duration < 1) {
            $error = 'Duration phải lớn hơn hoặc bằng 1';
        }

        elseif ((int) $duration > 500) {
            $error = 'Duration không được lớn hơn 500';
        }

        // Kiểm tra slug trùng trong file
        elseif (
            isset($seenSlugs[$slug])
            || $slug === 'khoa-hoc-php-1'
        ) {
            $error = 'Slug bị trùng trong file CSV';
        }

        // Kiểm tra slug trong database
        elseif ($repository->existsBySlug($slug)) {
            $error = 'Slug đã tồn tại trong database';
        }

        if ($error !== '') {

            $skipped++;

            fwrite(
                $logHandle,
                "Dòng {$processed}: {$error}\n"
            );

            continue;
        }

        $course = new \App\Entity\Course(
            id: null,
            title: $title,
            slug: $slug,
            level: $level,
            duration_hours: (int) $duration,
            is_published: $isPublished,
            is_deleted: 0
        );

        $batch[] = $course;
        $seenSlugs[$slug] = true;

        // Đủ 500 dòng thì INSERT một lần
        if (count($batch) >= $batchSize) {

            $inserted += $repository->createBatch($batch);

            $batch = [];
        }
    }

    // INSERT phần còn lại
    if ($batch !== []) {
        $inserted += $repository->createBatch($batch);
    }

    // Hoàn tất transaction
    $pdo->commit();

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fclose($file);
    fclose($logHandle);

    die(
        "Import thất bại: "
        . $e->getMessage()
        . "\n"
    );
}

fclose($file);
fclose($logHandle);

$endTime = microtime(true);
$time = $endTime - $startTime;

echo "===== KẾT QUẢ IMPORT TỐI ƯU =====\n";
echo "Số dòng xử lý: {$processed}\n";
echo "Số dòng thêm thành công: {$inserted}\n";
echo "Số dòng bỏ qua: {$skipped}\n";
echo "Kích thước batch: {$batchSize}\n";
echo "Thời gian: " . number_format($time, 4) . " giây\n";