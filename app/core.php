<?php
declare(strict_types=1);

const APP_ROOT = __DIR__ . '/..';

$sessionDir = APP_ROOT . '/data/sessions';
if (!is_dir($sessionDir)) {
    mkdir($sessionDir, 0775, true);
}
session_save_path($sessionDir);
session_start();

function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $_ENV[$key] = trim($value, "\"'");
    }
}

function data_dir(): string
{
    return APP_ROOT . '/data';
}

function users_file(): string
{
    return data_dir() . '/users.json';
}

function init_storage(): void
{
    if (!is_dir(data_dir())) {
        mkdir(data_dir(), 0775, true);
    }

    if (!is_file(users_file())) {
        save_users([]);
    }
}

function load_users(): array
{
    $contents = is_file(users_file()) ? file_get_contents(users_file()) : '[]';
    $users = json_decode($contents ?: '[]', true);

    return is_array($users) ? $users : [];
}

function save_users(array $users): void
{
    if (!is_dir(data_dir())) {
        mkdir(data_dir(), 0775, true);
    }

    file_put_contents(
        users_file(),
        json_encode(array_values($users), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

function next_user_id(array $users): int
{
    $ids = array_map(fn (array $user): int => (int) ($user['id'] ?? 0), $users);
    return $ids ? max($ids) + 1 : 1;
}

function find_user_by_username(string $username): ?array
{
    foreach (load_users() as $user) {
        if (strtolower((string) $user['username']) === strtolower($username)) {
            return $user;
        }
    }

    return null;
}

function find_user_by_id(int $id): ?array
{
    foreach (load_users() as $user) {
        if ((int) $user['id'] === $id) {
            return $user;
        }
    }

    return null;
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return find_user_by_id((int) $_SESSION['user_id']);
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('Ju lutem kyçuni për të vazhduar.', 'error');
        redirect_to('login');
    }

    return $user;
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

function flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect_to(string $page): never
{
    header('Location: ?page=' . urlencode($page));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function render_header(string $title): void
{
    ?>
    <!doctype html>
    <html lang="sq">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= h($title) ?></title>
        <link rel="stylesheet" href="static/style.css">
    </head>
    <body>
    <main class="auth-card">
        <h2><?= h($title) ?></h2>
        <?php foreach (flashes() as $flash): ?>
            <p class="message <?= h($flash['type']) ?>"><?= h($flash['message']) ?></p>
        <?php endforeach; ?>
    <?php
}

function render_footer(): void
{
    echo '</main></body></html>';
}