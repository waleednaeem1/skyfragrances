<?php
defined('SKYFR') || exit;

const PRICING_DEFAULT_SHIPPING_FEE = '250.00';
const PRICING_DEFAULT_FREE_THRESHOLD = '3000.00';

function pricing_settings(): array
{
    return [
        'shipping_fee_paisa' => money_paisa(setting_money('shipping_fee', PRICING_DEFAULT_SHIPPING_FEE)),
        'threshold_paisa' => money_paisa(setting_money('free_shipping_threshold', PRICING_DEFAULT_FREE_THRESHOLD)),
    ];
}

function pricing_unit(string $price, ?string $salePrice): array
{
    $pricePaisa = money_paisa($price);
    $salePaisa = $salePrice === null || $salePrice === '' ? 0 : money_paisa($salePrice);
    if ($salePaisa > 0 && $salePaisa < $pricePaisa) {
        return ['unit_price_paisa' => $salePaisa, 'compare_at_paisa' => $pricePaisa];
    }
    return ['unit_price_paisa' => $pricePaisa, 'compare_at_paisa' => null];
}

function pricing_percent_discount(int $subtotalPaisa, int $percent): int
{
    $hundredthsOfPaisa = $subtotalPaisa * $percent;
    return intdiv($hundredthsOfPaisa + 5000, 10000) * 100;
}

function pricing_percent_value(array $coupon): ?int
{
    $paisa = money_paisa((string) $coupon['value']);
    if ($paisa % 100 !== 0 || $paisa < 0 || $paisa > 10000) {
        return null;
    }
    return intdiv($paisa, 100);
}

function pricing_discount(int $subtotalPaisa, ?array $coupon): int
{
    if ($coupon === null || $subtotalPaisa <= 0) {
        return 0;
    }
    if ($coupon['type'] === 'percent') {
        $percent = pricing_percent_value($coupon);
        if ($percent === null) {
            return 0;
        }
        $discount = pricing_percent_discount($subtotalPaisa, $percent);
    } else {
        $discount = money_paisa((string) $coupon['value']);
    }
    return max(0, min($discount, $subtotalPaisa));
}

function pricing_shipping(int $subtotalPaisa, array $settings): array
{
    if ($subtotalPaisa <= 0) {
        return ['shipping_paisa' => 0, 'free_shipping' => false];
    }
    if ($subtotalPaisa >= $settings['threshold_paisa']) {
        return ['shipping_paisa' => 0, 'free_shipping' => true];
    }
    return ['shipping_paisa' => $settings['shipping_fee_paisa'], 'free_shipping' => false];
}

function pricing_progress(int $subtotalPaisa, int $thresholdPaisa): array
{
    if ($thresholdPaisa <= 0 || $subtotalPaisa <= 0) {
        return ['visible' => false, 'percent' => 0, 'remaining_paisa' => max(0, $thresholdPaisa - $subtotalPaisa), 'message' => ''];
    }
    if ($subtotalPaisa >= $thresholdPaisa) {
        return ['visible' => true, 'percent' => 100, 'remaining_paisa' => 0, 'message' => "You've unlocked free delivery."];
    }
    $remaining = $thresholdPaisa - $subtotalPaisa;
    $percent = max(2, min(99, intdiv($subtotalPaisa * 100, $thresholdPaisa)));
    return ['visible' => true, 'percent' => $percent, 'remaining_paisa' => $remaining, 'message' => "You're " . money(money_from_paisa($remaining)) . ' away from free delivery.'];
}

function pricing_apportion_discount(array $lineTotals, int $discountPaisa): array
{
    $total = array_sum($lineTotals);
    $shares = [];
    if ($total <= 0 || $discountPaisa <= 0) {
        return array_fill_keys(array_keys($lineTotals), 0);
    }
    $assigned = 0;
    $remainders = [];
    foreach ($lineTotals as $key => $lineTotal) {
        $exact = $discountPaisa * $lineTotal;
        $shares[$key] = intdiv($exact, $total);
        $remainders[$key] = $exact % $total;
        $assigned += $shares[$key];
    }
    arsort($remainders);
    foreach (array_keys($remainders) as $key) {
        if ($assigned >= $discountPaisa) {
            break;
        }
        $shares[$key]++;
        $assigned++;
    }
    return $shares;
}

function pricing_reprice_token(int $grandTotalPaisa, string $fingerprint): string
{
    return hash_hmac('sha256', $grandTotalPaisa . '|' . $fingerprint, (string) config('security.app_key', ''));
}

function pricing_fingerprint(array $pairs): string
{
    ksort($pairs, SORT_NUMERIC);
    $parts = [];
    foreach ($pairs as $sizeId => $qty) {
        $parts[] = (int) $sizeId . ':' . (int) $qty;
    }
    return hash('sha256', implode(',', $parts));
}
