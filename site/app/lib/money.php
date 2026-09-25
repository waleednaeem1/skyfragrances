<?php
defined('SKYFR') || exit;

function money(string $value, bool $withDecimals = false): string
{
    return 'Rs.' . "\u{00A0}" . number_format((float) $value, $withDecimals ? 2 : 0, '.', ',');
}

function money_attr(string $value): string
{
    return money_from_paisa(money_paisa($value));
}

function money_paisa(string $value): int
{
    $value = trim($value);
    if ($value === '' || !preg_match('/^(-)?(\d+)(?:\.(\d{1,2}))?$/', $value, $m)) {
        return 0;
    }
    $paisa = (int) $m[2] * 100 + (int) str_pad($m[3] ?? '0', 2, '0');
    return $m[1] === '-' ? -$paisa : $paisa;
}

function money_from_paisa(int $paisa): string
{
    $sign = $paisa < 0 ? '-' : '';
    $paisa = abs($paisa);
    return $sign . intdiv($paisa, 100) . '.' . str_pad((string) ($paisa % 100), 2, '0', STR_PAD_LEFT);
}

function effective_price(?string $salePrice, string $price): string
{
    if ($salePrice !== null && $salePrice !== '' && money_paisa($salePrice) > 0) {
        return $salePrice;
    }
    return $price;
}
