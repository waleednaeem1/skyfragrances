<?php
defined('SKYFR') || exit;

function view_file_name(string $view): string
{
    return str_ends_with($view, '.php') ? $view : $view . '.php';
}

function view_capture(string $file, array $data): string
{
    $capture = static function (string $__file, array $__data): void {
        extract($__data, EXTR_SKIP);
        require $__file;
    };
    ob_start();
    try {
        $capture($file, $data);
    } catch (Throwable $error) {
        ob_end_clean();
        throw $error;
    }
    return (string) ob_get_clean();
}

function view_head_merge(array $head): array
{
    return $head + [
        'title' => '',
        'meta_description' => '',
        'canonical' => '',
        'robots' => 'index,follow',
        'body_class' => '',
        'og' => [],
        'jsonld' => [],
    ];
}

function view_emit(string $viewsDir, string $view, array $data, array $head): never
{
    $viewFile = $viewsDir . '/' . view_file_name($view);
    if (!is_file($viewFile)) {
        throw new RuntimeException('View not found: ' . $view);
    }
    $head = view_head_merge($head);
    $content = view_capture($viewFile, $data);
    $layoutFile = $viewsDir . '/layout.php';
    $page = is_file($layoutFile)
        ? view_capture($layoutFile, ['content' => $content, 'head' => $head] + $data)
        : $content;
    response_clear_buffers();
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    echo $page;
    exit;
}

function render(string $view, array $data = [], array $head = []): never
{
    view_emit(APP_ROOT . '/app/views', $view, $data, $head);
}

function render_admin(string $view, array $data = [], array $head = []): never
{
    view_emit(APP_ROOT . '/admin/views', $view, $data, $head);
}

function partial(string $file, array $data = []): void
{
    $partialFile = APP_ROOT . '/app/partials/' . view_file_name($file);
    if (!is_file($partialFile)) {
        return;
    }
    echo view_capture($partialFile, $data);
}

function partial_admin(string $file, array $data = []): void
{
    $partialFile = APP_ROOT . '/admin/partials/' . view_file_name($file);
    if (!is_file($partialFile)) {
        return;
    }
    echo view_capture($partialFile, $data);
}
