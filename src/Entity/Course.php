<?php

namespace App\Entity;

class Course
{
    public function __construct(
        public ?int $id,
        public string $title,
        public string $slug,
        public string $level,
        public int $duration_hours,
        public int $is_published = 0,
        public int $is_deleted = 0,
        public ?string $created_at = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            title: (string) $data['title'],
            slug: (string) $data['slug'],
            level: (string) $data['level'],
            duration_hours: (int) $data['duration_hours'],
            is_published: (int) ($data['is_published'] ?? 0),
            is_deleted: (int) ($data['is_deleted'] ?? 0),
            created_at: $data['created_at'] ?? null
        );
    }
}