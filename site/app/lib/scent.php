<?php
defined('SKYFR') || exit;

function product_split_list(?string $raw): array
{
    if ($raw === null || trim($raw) === '') {
        return [];
    }
    return array_values(array_filter(array_map('trim', explode(',', $raw)), static fn (string $v): bool => $v !== ''));
}

function scent_tone(?string $family): string
{
    $key = mb_strtolower(trim((string) $family), 'UTF-8');
    foreach (['citrus' => ['citrus', 'fresh', 'aquatic'], 'floral' => ['floral', 'rose', 'flower'], 'amber' => ['amber', 'spice', 'oriental', 'gourmand'], 'oud' => ['oud', 'smoke', 'leather', 'wood'], 'green' => ['green', 'earth', 'herb', 'aromatic']] as $tone => $words) {
        foreach ($words as $word) {
            if (str_contains($key, $word)) {
                return $tone;
            }
        }
    }
    return 'gold';
}

function product_notes_flat(array $product, int $limit = 0): array
{
    $notes = [];
    foreach (['notes_top', 'notes_heart', 'notes_base'] as $tier) {
        foreach (product_split_list($product[$tier] ?? null) as $note) {
            $notes[mb_strtolower($note, 'UTF-8')] ??= $note;
        }
    }
    $notes = array_values($notes);
    return $limit > 0 ? array_slice($notes, 0, $limit) : $notes;
}
