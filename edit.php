<?php

require __DIR__ . '/bootstrap.php';

use App\Exception\ValidationException;

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: index.php');
    exit;
}

$course = $repository->find($id);

if ($course === null || $course->is_deleted === 1) {
    header('Location: index.php');
    exit;
}

$errors = [];

$title = $course->title;
$level = $course->level;
$duration_hours = $course->duration_hours;
$is_published = $course->is_published;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = $_POST['title'] ?? '';
    $level = $_POST['level'] ?? '';
    $duration_hours = $_POST['duration_hours'] ?? '';
    $is_published = isset($_POST['is_published']) ? 1 : 0;

    try {

        $courseService->update($id, [
            'title' => $title,
            'level' => $level,
            'duration_hours' => $duration_hours,
            'is_published' => $is_published,
        ]);

        header('Location: index.php?success=updated');
        exit;

    } catch (ValidationException $e) {

        $errors = $e->getErrors();

    } catch (Throwable $e) {

        $errors['general'] = 'Có lỗi xảy ra. Vui lòng thử lại.';
    }
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sửa khóa học</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 700px;
            margin: 40px auto;
            padding: 0 20px;
            background: #f5f5f5;
        }

        h1 {
            margin-bottom: 25px;
        }

        .form-box {
            background: white;
            padding: 25px;
            border-radius: 8px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input,
        select {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        input[type="checkbox"] {
            width: auto;
        }

        .error {
            color: #d32f2f;
            background: #ffebee;
            padding: 8px;
            margin-top: 6px;
            border-radius: 4px;
        }

        .general-error {
            color: #d32f2f;
            background: #ffebee;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        button {
            padding: 10px 18px;
            cursor: pointer;
        }

        a {
            margin-left: 10px;
        }
    </style>
</head>

<body>

<h1>Sửa khóa học</h1>

<div class="form-box">

    <?php if (isset($errors['general'])): ?>
        <div class="general-error">
            <?= e($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form method="post">

        <div class="form-group">

            <label for="title">
                Tiêu đề
            </label>

            <input
                type="text"
                id="title"
                name="title"
                value="<?= e($title) ?>"
            >

            <?php if (isset($errors['title'])): ?>
                <div class="error">
                    <?= e($errors['title']) ?>
                </div>
            <?php endif; ?>

        </div>


        <div class="form-group">

            <label for="level">
                Level
            </label>

            <select id="level" name="level">

                <option value="">
                    -- Chọn level --
                </option>

                <option
                    value="co-ban"
                    <?= $level === 'co-ban' ? 'selected' : '' ?>
                >
                    Cơ bản
                </option>

                <option
                    value="trung-cap"
                    <?= $level === 'trung-cap' ? 'selected' : '' ?>
                >
                    Trung cấp
                </option>

                <option
                    value="nang-cao"
                    <?= $level === 'nang-cao' ? 'selected' : '' ?>
                >
                    Nâng cao
                </option>

            </select>

            <?php if (isset($errors['level'])): ?>
                <div class="error">
                    <?= e($errors['level']) ?>
                </div>
            <?php endif; ?>

        </div>


        <div class="form-group">

            <label for="duration_hours">
                Số giờ
            </label>

            <input
                type="number"
                id="duration_hours"
                name="duration_hours"
                value="<?= e($duration_hours) ?>"
            >

            <?php if (isset($errors['duration_hours'])): ?>
                <div class="error">
                    <?= e($errors['duration_hours']) ?>
                </div>
            <?php endif; ?>

        </div>


        <div class="form-group">

            <label>
                <input
                    type="checkbox"
                    name="is_published"
                    value="1"
                    <?= $is_published ? 'checked' : '' ?>
                >

                Xuất bản
            </label>

        </div>


        <button type="submit">
            Cập nhật khóa học
        </button>

        <a href="index.php">
            Quay lại
        </a>

    </form>

</div>

</body>
</html>