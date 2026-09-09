<?php

require __DIR__ . '/db.php';

$errors = [];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die('ID không hợp lệ.');
}

$stmt = $pdo->prepare(
    'SELECT * FROM courses
     WHERE id = :id AND is_deleted = 0'
);

$stmt->execute(['id' => $id]);

$course = $stmt->fetch();

if (!$course) {
    die('Không tìm thấy khóa học.');
}

$title = $course['title'];
$level = $course['level'];
$duration_hours = $course['duration_hours'];

$allowedLevels = [
    'co-ban',
    'trung-cap',
    'nang-cao'
];

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function createSlug(string $text): string
{
    $text = trim($text);
    $text = mb_strtolower($text, 'UTF-8');

    $map = [
        'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a',
        'â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a',
        'ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',

        'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e',
        'ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',

        'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',

        'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o',
        'ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o',
        'ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',

        'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u',
        'ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',

        'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y',
        'đ'=>'d'
    ];

    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);

    return trim($text, '-');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $level = $_POST['level'] ?? '';
    $duration_hours = trim($_POST['duration_hours'] ?? '');

    if ($title === '') {
        $errors['title'] = 'Vui lòng nhập tiêu đề.';
    } elseif (mb_strlen($title, 'UTF-8') > 180) {
        $errors['title'] = 'Tiêu đề không được vượt quá 180 ký tự.';
    }

    if ($duration_hours === '') {
        $errors['duration_hours'] = 'Vui lòng nhập số giờ.';
    } elseif (
        filter_var($duration_hours, FILTER_VALIDATE_INT) === false ||
        (int)$duration_hours < 1 ||
        (int)$duration_hours > 500
    ) {
        $errors['duration_hours'] = 'Số giờ phải từ 1 đến 500.';
    }

    if (!in_array($level, $allowedLevels, true)) {
        $errors['level'] = 'Level không hợp lệ.';
    }

    if (!$errors) {

        $slug = createSlug($title);

        $check = $pdo->prepare(
            'SELECT COUNT(*)
             FROM courses
             WHERE slug = :slug
             AND id <> :id
             AND is_deleted = 0'
        );

        $check->execute([
            'slug' => $slug,
            'id' => $id
        ]);

        if ((int)$check->fetchColumn() > 0) {
            $errors['title'] = 'Slug đã tồn tại. Vui lòng chọn tiêu đề khác.';
        }
    }

    if (!$errors) {

        $stmt = $pdo->prepare(
            'UPDATE courses
             SET title = :title,
                 slug = :slug,
                 level = :level,
                 duration_hours = :duration_hours
             WHERE id = :id
             AND is_deleted = 0'
        );

        $stmt->execute([
            'title' => $title,
            'slug' => $slug,
            'level' => $level,
            'duration_hours' => (int)$duration_hours,
            'id' => $id
        ]);

        header('Location: index.php?updated=1');
        exit;
    }
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
            padding: 20px;
            background: #f5f5f5;
        }

        form {
            background: white;
            padding: 25px;
            border-radius: 8px;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input,
        select {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
        }

        .error {
            color: #c00;
            margin-top: 5px;
        }

        button {
            margin-top: 20px;
            padding: 10px 20px;
            cursor: pointer;
        }
    </style>
</head>

<body>

<h1>Sửa khóa học</h1>

<form method="post">

    <label for="title">Tiêu đề</label>

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


    <label for="level">Level</label>

    <select id="level" name="level">

        <option value="co-ban"
            <?= $level === 'co-ban' ? 'selected' : '' ?>>
            Cơ bản
        </option>

        <option value="trung-cap"
            <?= $level === 'trung-cap' ? 'selected' : '' ?>>
            Trung cấp
        </option>

        <option value="nang-cao"
            <?= $level === 'nang-cao' ? 'selected' : '' ?>>
            Nâng cao
        </option>

    </select>


    <label for="duration_hours">Số giờ học</label>

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


    <button type="submit">
        Lưu thay đổi
    </button>

</form>

<p>
    <a href="index.php">← Quay lại danh sách</a>
</p>

</body>
</html>