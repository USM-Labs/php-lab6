<?php

declare(strict_types=1);

require_once __DIR__ . '/src/SecretNoteRepository.php';
require_once __DIR__ . '/src/SecretNoteValidator.php';

$repository = new SecretNoteRepository(__DIR__ . '/data/notes.json');
$validator = new SecretNoteValidator();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = noteDataFromArray(readPutPayload());
    $errors = $validator->validate($data);

    header('Content-Type: application/json; charset=utf-8');

    if ($errors === []) {
        try {
            $note = $repository->add($data);
            http_response_code(200);
            echo json_encode(['success' => true, 'note' => $note], JSON_UNESCAPED_UNICODE);
        } catch (RuntimeException $exception) {
            http_response_code(500);
            echo json_encode(['success' => false, 'errors' => [$exception->getMessage()]], JSON_UNESCAPED_UNICODE);
        }
    } else {
        http_response_code(422);
        echo json_encode(['success' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
    }

    exit;
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    http_response_code(405);
    header('Allow: GET, POST, PUT');
    exit('Method not allowed.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = noteDataFromArray($_POST);
    $errors = $validator->validate($data);

    if ($errors === []) {
        try {
            $repository->add($data);
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }
    }

    if ($errors === []) {
        $success = true;
        $_POST = [];
    }
}

$sort = (string) ($_GET['sort'] ?? 'created_at');
$notes = $repository->all($sort);

function oldValue(string $field): string
{
    return htmlspecialchars((string) ($_POST[$field] ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * @param array<string, mixed> $source
 * @return array<string, string>
 */
function noteDataFromArray(array $source): array
{
    return [
        'title' => trim((string) ($source['title'] ?? '')),
        'author' => trim((string) ($source['author'] ?? '')),
        'category' => trim((string) ($source['category'] ?? '')),
        'mood' => trim((string) ($source['mood'] ?? '')),
        'confession' => trim((string) ($source['confession'] ?? '')),
        'created_at' => trim((string) ($source['created_at'] ?? '')),
        'visibility' => normalizeVisibility($source['visibility'] ?? ''),
    ];
}

/**
 * @return array<string, mixed>
 */
function readPutPayload(): array
{
    $rawBody = file_get_contents('php://input');
    if ($rawBody === false || trim($rawBody) === '') {
        return [];
    }

    $decoded = json_decode($rawBody, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
    }

    parse_str($rawBody, $parsed);
    return $parsed;
}

function normalizeVisibility(mixed $visibility): string
{
    if ($visibility === true || $visibility === 1 || $visibility === '1' || $visibility === 'yes') {
        return 'public';
    }

    return trim((string) $visibility);
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Лабораторная работа №6</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; color: #202124; background: #f6f8f7; line-height: 1.5; }
        header, main { padding: 28px 40px; }
        header { background: #243b40; color: #fff; }
        form { display: grid; gap: 14px; max-width: 820px; }
        label { font-weight: 700; }
        input, select, textarea, button { width: 100%; box-sizing: border-box; padding: 10px; border: 1px solid #b8c4c0; border-radius: 6px; font: inherit; }
        textarea { min-height: 120px; resize: vertical; }
        button { background: #2f6f73; color: white; border: 0; cursor: pointer; font-weight: 700; }
        table { border-collapse: collapse; width: 100%; margin-top: 18px; background: white; }
        th, td { border: 1px solid #cbd5d1; padding: 10px; text-align: left; vertical-align: top; }
        th { background: #edf2f0; }
        .errors { background: #fff1f0; border-left: 4px solid #c53030; padding: 12px 16px; max-width: 820px; }
        .success { background: #eef7f2; border-left: 4px solid #2f855a; padding: 12px 16px; max-width: 820px; }
        .sort a { margin-right: 14px; color: #2f6f73; font-weight: 700; }
    </style>
</head>
<body>
    <header>
        <h1>Лабораторная работа №6. Мини-система тайных признаний</h1>
        <p>Форма, серверная валидация, сохранение в JSON и вывод записей с сортировкой.</p>
    </header>

    <main>
        <?php if ($success): ?>
            <p class="success">Запись успешно сохранена.</p>
        <?php endif; ?>

        <?php if ($errors !== []): ?>
            <div class="errors">
                <strong>Исправьте ошибки:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <section>
            <h2>Новое признание</h2>
            <form method="post" action="index.php">
                <div>
                    <label for="title">Заголовок</label>
                    <input id="title" name="title" type="text" minlength="3" maxlength="80" required value="<?= oldValue('title') ?>">
                </div>
                <div>
                    <label for="author">Автор</label>
                    <input id="author" name="author" type="text" minlength="2" maxlength="60" required value="<?= oldValue('author') ?>">
                </div>
                <div>
                    <label for="category">Категория</label>
                    <select id="category" name="category" required>
                        <?php foreach (['study' => 'Учеба', 'work' => 'Работа', 'life' => 'Жизнь', 'fun' => 'Забавное'] as $value => $label): ?>
                            <option value="<?= $value ?>" <?= oldValue('category') === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="mood">Настроение</label>
                    <select id="mood" name="mood" required>
                        <?php foreach (['calm' => 'Спокойное', 'happy' => 'Радостное', 'sad' => 'Грустное', 'chaotic' => 'Хаотичное'] as $value => $label): ?>
                            <option value="<?= $value ?>" <?= oldValue('mood') === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="confession">Текст признания</label>
                    <textarea id="confession" name="confession" minlength="10" maxlength="1000" required><?= oldValue('confession') ?></textarea>
                </div>
                <div>
                    <label for="created_at">Дата создания</label>
                    <input id="created_at" name="created_at" type="date" required value="<?= oldValue('created_at') ?>">
                </div>
                <div>
                    <label>
                        <input type="checkbox" name="visibility" value="public" <?= oldValue('visibility') === 'public' ? 'checked' : '' ?>>
                        Можно показывать публично
                    </label>
                </div>
                <button type="submit">Сохранить</button>
            </form>
        </section>

        <section>
            <h2>Сохраненные записи</h2>
            <p class="sort">
                Сортировка:
                <a href="?sort=created_at">по дате</a>
                <a href="?sort=category">по категории</a>
                <a href="?sort=author">по автору</a>
            </p>
            <table>
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Заголовок</th>
                        <th>Автор</th>
                        <th>Категория</th>
                        <th>Настроение</th>
                        <th>Публичность</th>
                        <th>Текст</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notes as $note): ?>
                        <tr>
                            <td><?= htmlspecialchars($note['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($note['title'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($note['author'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($note['category'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($note['mood'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($note['visibility'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= nl2br(htmlspecialchars($note['confession'], ENT_QUOTES, 'UTF-8')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
