<?php
defined('SKYFR') || exit;
$value = (string) ($value ?? '0.00');
$isFree = !empty($free) || (!empty($free_when_zero) && money_paisa($value) === 0);
$classes = 'adm-money' . (!empty($strong) ? ' adm-money--strong' : '') . (!empty($muted) ? ' adm-money--muted' : '') . (!empty($low) ? ' adm-money--low' : '') . ($isFree ? ' adm-money--free' : '') . (!empty($negative) ? ' adm-money--negative' : '');
?>
<span class="<?= e($classes) ?>" data-money="<?= e(money_attr($value)) ?>"><?= $isFree ? 'Free' : (!empty($negative) ? '&minus; ' : '') . e(money($value, !empty($decimals))) ?></span>
