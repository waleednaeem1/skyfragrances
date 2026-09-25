<?php
defined('SKYFR') || exit;
$name = (string) ($name ?? '');
$size = (int) ($size ?? 24);
$label = (string) ($label ?? '');
$class = trim((string) ($class ?? ''));
$paths = [
    'cart' => '<path d="M6 7h12l1 13H5L6 7z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/>',
    'search' => '<circle cx="11" cy="11" r="6.5"/><path d="M16 16l5 5"/>',
    'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
    'close' => '<path d="M6 6l12 12M18 6L6 18"/>',
    'package' => '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path d="M4 7.5l8 4.5 8-4.5M12 12v9"/>',
    'chevron-down' => '<path d="M6 9l6 6 6-6"/>',
    'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
    'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    'check' => '<path d="M5 12l5 5L20 7"/>',
    'star' => '<path d="M12 2.5l2.9 6.2 6.8.8-5 4.7 1.3 6.8L12 17.7 6 21l1.3-6.8-5-4.7 6.8-.8z"/>',
    'droplet' => '<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z"/><path d="M9.5 14.5a2.5 2.5 0 0 0 2 2.4"/>',
    'banknote' => '<rect x="3" y="6" width="18" height="12" rx="1.5"/><circle cx="12" cy="12" r="2.5"/><path d="M6.5 9.5h.01M17.5 14.5h.01"/>',
    'truck' => '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="17.5" r="1.5"/><circle cx="17" cy="17.5" r="1.5"/>',
    'refresh' => '<path d="M20 12a8 8 0 0 1-14.5 4.6M4 12A8 8 0 0 1 18.5 7.4"/><path d="M18 3v4.5h-4.5M6 21v-4.5h4.5"/>',
    'shield' => '<path d="M12 3l7 3v5.5c0 4.5-3 7.8-7 9.5-4-1.7-7-5-7-9.5V6l7-3z"/><path d="M9 12l2 2 4-4"/>',
    'sparkle' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3z"/><path d="M19 16l.7 2 2 .7-2 .7-.7 2-.7-2-2-.7 2-.7.7-2z"/>',
    'compass' => '<circle cx="12" cy="12" r="8.5"/><path d="M14.8 9.2l-1.6 4-4 1.6 1.6-4 4-1.6z"/>',
    'quote' => '<path d="M7 7h4v4c0 2.5-1.5 4-4 4.5V14a2 2 0 0 0 2-2H7V7zM14 7h4v4c0 2.5-1.5 4-4 4.5V14a2 2 0 0 0 2-2h-2V7z"/>',
    'mail' => '<rect x="3" y="5" width="18" height="14" rx="1.5"/><path d="M3 7l9 6 9-6"/>',
    'external' => '<path d="M14 4h6v6M20 4l-9 9"/><path d="M19 14v5H5V5h5"/>',
    'whatsapp' => '<path d="M12 3a9 9 0 0 0-7.8 13.5L3 21l4.7-1.2A9 9 0 1 0 12 3z"/><path d="M9.5 8.5c0 3 2.5 5.5 5.5 6l1.5-1.5-2-1-1 1a4.5 4.5 0 0 1-2-2l1-1-1-2L9.5 8.5z"/>',
    'instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="4.5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="0.8" fill="currentColor" stroke="none"/>',
    'facebook' => '<path d="M14 21v-7h3l.5-3.5H14V8.5c0-1 .4-1.7 1.8-1.7h1.9V3.7A22 22 0 0 0 15 3.5C12.3 3.5 10.5 5.1 10.5 8v2.5h-3V14h3v7"/>',
    'tiktok' => '<path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3c.5 3 2.5 5 5.5 5"/>',
    'youtube' => '<rect x="3" y="6" width="18" height="12" rx="3"/><path d="M10 9.5v5l4.5-2.5L10 9.5z" fill="currentColor" stroke="none"/>',
];
if (!isset($paths[$name])) {
    return;
}
$classes = trim('icon icon--' . $name . ' ' . $class);
?>
<svg class="<?= e($classes) ?>" width="<?= e((string) $size) ?>" height="<?= e((string) $size) ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"<?php if ($label === ''): ?> aria-hidden="true" focusable="false"<?php else: ?> role="img" aria-label="<?= e($label) ?>"<?php endif; ?>><?php echo $paths[$name]; ?></svg>
