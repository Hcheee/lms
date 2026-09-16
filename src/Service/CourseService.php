<?php

namespace App\Service;

use App\Entity\Course;
use App\Exception\ValidationException;
use App\Repository\CourseRepository;

class CourseService
{
    public function __construct(
        private CourseRepository $repository
    ) {
    }

    /**
     * Tạo slug từ tiêu đề tiếng Việt.
     */
    public function createSlug(string $text): string
    {
        // Xử lý riêng Đ/đ vì iconv có thể không chuyển đúng
        $text = str_replace(
            ['Đ', 'đ'],
            ['D', 'd'],
            $text
        );

        $text = iconv(
            'UTF-8',
            'ASCII//TRANSLIT//IGNORE',
            $text
        );

        $text = strtolower($text);

        // Chỉ giữ chữ cái, số và khoảng trắng
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);

        // Khoảng trắng / nhiều dấu - thành một dấu -
        $text = preg_replace('/[\s-]+/', '-', $text);

        return trim($text, '-');
    }

    /**
     * Tạo khóa học.
     */
    public function create(array $data): int
    {
        $validated = $this->validate($data);

        $slug = $this->createSlug($validated['title']);

        if ($this->repository->existsBySlug($slug)) {
            throw new ValidationException([
                'slug' => 'Slug đã tồn tại.'
            ]);
        }

        $course = new Course(
            id: null,
            title: $validated['title'],
            slug: $slug,
            level: $validated['level'],
            duration_hours: $validated['duration_hours'],
            is_published: $validated['is_published'],
            is_deleted: 0
        );

        return $this->repository->create($course);
    }

    /**
     * Cập nhật khóa học.
     */
    public function update(int $id, array $data): void
    {
        $course = $this->repository->find($id);

        if ($course === null || $course->is_deleted === 1) {
            throw new \RuntimeException(
                'Không tìm thấy khóa học.'
            );
        }

        $validated = $this->validate($data);

        $slug = $this->createSlug($validated['title']);

        if ($this->repository->existsBySlug($slug, $id)) {
            throw new ValidationException([
                'slug' => 'Slug đã tồn tại.'
            ]);
        }

        $course->title = $validated['title'];
        $course->slug = $slug;
        $course->level = $validated['level'];
        $course->duration_hours = $validated['duration_hours'];
        $course->is_published = $validated['is_published'];

        $this->repository->update($course);
    }

    /**
     * Xóa mềm khóa học.
     */
    public function softDelete(int $id): void
    {
        $course = $this->repository->find($id);

        if ($course === null || $course->is_deleted === 1) {
            throw new \RuntimeException(
                'Không tìm thấy khóa học.'
            );
        }

        $this->repository->softDelete($id);
    }

    /**
     * Validate dữ liệu đầu vào.
     */
    private function validate(array $data): array
    {
        $errors = [];

        $title = trim((string) ($data['title'] ?? ''));
        $level = trim((string) ($data['level'] ?? ''));
        $duration = $data['duration_hours'] ?? '';
        $isPublished = isset($data['is_published'])
            ? (int) $data['is_published']
            : 0;

        // Title
        if ($title === '') {
            $errors['title'] = 'Tiêu đề là bắt buộc.';
        } elseif (mb_strlen($title) > 180) {
            $errors['title'] =
                'Tiêu đề không được vượt quá 180 ký tự.';
        }

        // Level
        $allowedLevels = [
            'co-ban',
            'trung-cap',
            'nang-cao'
        ];

        if (!in_array($level, $allowedLevels, true)) {
            $errors['level'] = 'Level không hợp lệ.';
        }

        // Duration
        if (
            $duration === ''
            || filter_var($duration, FILTER_VALIDATE_INT) === false
        ) {
            $errors['duration_hours'] =
                'Số giờ phải là số nguyên.';
        } else {
            $duration = (int) $duration;

            if ($duration < 1 || $duration > 500) {
                $errors['duration_hours'] =
                    'Số giờ phải từ 1 đến 500.';
            }
        }

        // Published
        if (!in_array($isPublished, [0, 1], true)) {
            $isPublished = 0;
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return [
            'title' => $title,
            'level' => $level,
            'duration_hours' => $duration,
            'is_published' => $isPublished,
        ];
    }
}