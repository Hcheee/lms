<?php

require __DIR__ . '/db.php';

$keyword = trim($_GET['keyword'] ?? '');
$level = $_GET['level'] ?? '';

$allowedLevels = ['co-ban', 'trung-cap', 'nang-cao'];

if (!in_array($level, $allowedLevels, true)) {
    $level = '';
}

$sql = "
    SELECT id, title, slug, level, duration_hours, is_published, created_at
    FROM courses
    WHERE is_deleted = 0
";

$params = [];

if ($keyword !== '') {
    $sql .= " AND title LIKE :keyword";
    $params['keyword'] = '%' . $keyword . '%';
}

if ($level !== '') {
    $sql .= " AND level = :level";
    $params['level'] = $level;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$courses = $stmt->fetchAll();

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách khóa học</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
            background: #f5f5f5;
        }

        h1 {
            margin-bottom: 25px;
        }

        .search-box {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        input, select, button {
            padding: 10px;
            margin-right: 8px;
        }

        button {
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #eee;
        }

        .empty {
            background: white;
            padding: 20px;
        }
        .success {
            color: green;
            background: #e8f5e9;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <?php if (isset($_GET['success'])): ?>
    <div class="success">
        Thêm khóa học thành công!
    </div>
<?php endif; ?>

<h1>Danh sách khóa học - Main</h1>

<div class="search-box">

    <form method="get">

        <input
            type="text"
            name="keyword"
            placeholder="Tìm theo tiêu đề..."
            value="<?= e($keyword) ?>"
        >

        <select name="level">
            <option value="">-- Tất cả level --</option>

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

        <button type="submit">Tìm kiếm</button>

    </form>

</div>

<?php if (count($courses) > 0): ?>

<table>

    <thead>
        <tr>
            <th>ID</th>
            <th>Tiêu đề</th>
            <th>Slug</th>
            <th>Level</th>
            <th>Số giờ</th>
            <th>Xuất bản</th>
            <th>Ngày tạo</th>
            <th>Thao tác</th>
        </tr>
    </thead>

    <tbody>

    <?php foreach ($courses as $course): ?>

        <tr>
            <td><?= e($course['id']) ?></td>

            <td><?= e($course['title']) ?></td>

            <td><?= e($course['slug']) ?></td>

            <td><?= e($course['level']) ?></td>

            <td><?= e($course['duration_hours']) ?></td>

            <td>
                <?= $course['is_published'] ? 'Có' : 'Chưa' ?>
            </td>

            <td><?= e($course['created_at']) ?></td>
            <td>
                <a href="edit.php?id=<?= (int)$course['id'] ?>">
                Sửa
                </a>

                |

                <a
                href="delete.php?id=<?= (int)$course['id'] ?>"
                onclick="return confirm('Bạn có chắc muốn xóa khóa học này?');"
                >
                Xóa
                </a>
                </td>
        </tr>

    <?php endforeach; ?>

    </tbody>

</table>

<?php else: ?>

    <div class="empty">
        Không tìm thấy khóa học nào.
    </div>

<?php endif; ?>

</body>
</html>