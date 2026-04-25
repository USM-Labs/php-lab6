<?php

declare(strict_types=1);

/**
 * Saves and reads secret notes from a JSON file.
 */
final class SecretNoteRepository
{
    public function __construct(
        private string $filePath
    ) {
        $directory = dirname($this->filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Adds a new note to storage.
     *
     * @param array<string, string> $data
     */
    public function add(array $data): void
    {
        $items = $this->read();
        $items[] = [
            'id' => uniqid('note_', true),
            'title' => $data['title'],
            'author' => $data['author'],
            'category' => $data['category'],
            'mood' => $data['mood'],
            'confession' => $data['confession'],
            'created_at' => $data['created_at'],
            'visibility' => ($data['visibility'] ?? '') === 'public' ? 'public' : 'private',
            'updated_at' => date('Y-m-d'),
        ];

        file_put_contents($this->filePath, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Returns all notes sorted by selected field.
     *
     * @return array<int, array<string, string>>
     */
    public function all(string $sort = 'created_at'): array
    {
        $allowedSorts = ['created_at', 'category', 'author'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'created_at';
        $items = $this->read();

        usort(
            $items,
            static fn (array $left, array $right): int => strcmp((string) $left[$sort], (string) $right[$sort])
        );

        return $items;
    }

    /**
     * Reads JSON storage into an array.
     *
     * @return array<int, array<string, string>>
     */
    private function read(): array
    {
        $contents = file_get_contents($this->filePath);
        $decoded = json_decode($contents === false ? '[]' : $contents, true);

        return is_array($decoded) ? $decoded : [];
    }
}
