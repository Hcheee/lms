<?php

namespace App\Repository;

use App\Entity\Course;
use PDO;

class CourseRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * Lấy danh sách khóa học có tìm kiếm, lọc level và phân trang.
     */
    public function all(
        ?string $search = null,
        ?string $level = null,
        int $page = 1,
        int $perPage = 10
    ): array {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $where = ['is_deleted = 0'];
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $where[] = '(title LIKE :search OR slug LIKE :search)';
            $params['search'] = '%' . trim($search) . '%';
        }

        if ($level !== null && $level !== '') {
            $where[] = 'level = :level';
            $params['level'] = $level;
        }

        $whereSql = implode(' AND ', $where);

        // Đếm tổng số bản ghi
        $countSql = "
            SELECT COUNT(*)
            FROM courses
            WHERE {$whereSql}
        ";

        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Lấy dữ liệu
        $sql = "
            SELECT
                id,
                title,
                slug,
                level,
                duration_hours,
                is_published,
                is_deleted,
                created_at
            FROM courses
            WHERE {$whereSql}
            ORDER BY id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(
                ':' . $key,
                $value,
                PDO::PARAM_STR
            );
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        $items = [];

        while ($row = $stmt->fetch()) {
            $items[] = Course::fromArray($row);
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * Tìm một khóa học theo ID.
     */
    public function find(int $id): ?Course
    {
        $sql = "
            SELECT
                id,
                title,
                slug,
                level,
                duration_hours,
                is_published,
                is_deleted,
                created_at
            FROM courses
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id
        ]);

        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return Course::fromArray($row);
    }

    /**
     * Tạo khóa học mới.
     */
    public function create(Course $course): int
    {
        $sql = "
            INSERT INTO courses (
                title,
                slug,
                level,
                duration_hours,
                is_published,
                is_deleted
            ) VALUES (
                :title,
                :slug,
                :level,
                :duration_hours,
                :is_published,
                :is_deleted
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            'title' => $course->title,
            'slug' => $course->slug,
            'level' => $course->level,
            'duration_hours' => $course->duration_hours,
            'is_published' => $course->is_published,
            'is_deleted' => $course->is_deleted,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Cập nhật khóa học.
     */
    public function update(Course $course): void
    {
        if ($course->id === null) {
            throw new \InvalidArgumentException(
                'Không thể cập nhật khóa học chưa có ID.'
            );
        }

        $sql = "
            UPDATE courses
            SET
                title = :title,
                slug = :slug,
                level = :level,
                duration_hours = :duration_hours,
                is_published = :is_published
            WHERE id = :id
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'level' => $course->level,
            'duration_hours' => $course->duration_hours,
            'is_published' => $course->is_published,
        ]);
    }

    /**
     * Xóa mềm khóa học.
     */
    public function softDelete(int $id): void
    {
        $sql = "
            UPDATE courses
            SET is_deleted = 1
            WHERE id = :id
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            'id' => $id
        ]);
    }

    /**
     * Kiểm tra slug đã tồn tại hay chưa.
     *
     * $excludeId dùng khi sửa khóa học:
     * bỏ qua chính khóa học đang sửa.
     */
    public function existsBySlug(
        string $slug,
        ?int $excludeId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)
            FROM courses
            WHERE slug = :slug
              AND is_deleted = 0
        ";

        $params = [
            'slug' => $slug
        ];

        if ($excludeId !== null) {
            $sql .= " AND id <> :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }
}