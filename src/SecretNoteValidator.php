<?php

declare(strict_types=1);

require_once __DIR__ . '/ValidatorInterface.php';

/**
 * Validates secret confession form data.
 */
final class SecretNoteValidator implements ValidatorInterface
{
    private const CATEGORIES = ['study', 'work', 'life', 'fun'];
    private const MOODS = ['calm', 'happy', 'sad', 'chaotic'];

    /**
     * @param array<string, string> $data
     * @return string[]
     */
    public function validate(array $data): array
    {
        $errors = [];

        if (mb_strlen($data['title'] ?? '') < 3) {
            $errors[] = 'Заголовок должен содержать минимум 3 символа.';
        }

        if (mb_strlen($data['author'] ?? '') < 2) {
            $errors[] = 'Имя автора должно содержать минимум 2 символа.';
        }

        if (!in_array($data['category'] ?? '', self::CATEGORIES, true)) {
            $errors[] = 'Выберите корректную категорию.';
        }

        if (!in_array($data['mood'] ?? '', self::MOODS, true)) {
            $errors[] = 'Выберите корректное настроение.';
        }

        if (mb_strlen($data['confession'] ?? '') < 10) {
            $errors[] = 'Текст признания должен содержать минимум 10 символов.';
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $data['created_at'] ?? '');
        if (!$date || $date->format('Y-m-d') !== ($data['created_at'] ?? '')) {
            $errors[] = 'Дата должна быть указана в формате YYYY-MM-DD.';
        }

        if (($data['visibility'] ?? '') !== 'public' && ($data['visibility'] ?? '') !== '') {
            $errors[] = 'Некорректное значение публичности.';
        }

        return $errors;
    }
}
