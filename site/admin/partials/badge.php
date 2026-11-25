<?php
defined('SKYFR') || exit;
$status = (string) ($status ?? '');
$tones = [
    'pending' => 'gray', 'confirmed' => 'blue', 'packing' => 'violet', 'shipped' => 'blue', 'delivered' => 'green', 'cancelled' => 'red',
    'unpaid' => 'amber', 'awaiting_verification' => 'amber', 'partial' => 'amber', 'paid' => 'green', 'failed' => 'red', 'refunded' => 'gray',
    'approved' => 'green', 'rejected' => 'red',
    'new' => 'amber', 'read' => 'gray', 'replied' => 'blue', 'archived' => 'gray',
    'subscribed' => 'green', 'unsubscribed' => 'gray',
    'live' => 'green', 'hidden' => 'gray', 'active' => 'green', 'inactive' => 'gray', 'expired' => 'gray', 'low' => 'amber', 'out' => 'red',
    'cod' => 'gray', 'bank' => 'gray', 'jazzcash' => 'gray', 'easypaisa' => 'gray', 'phone' => 'violet',
];
$aliases = ['gold' => 'amber', 'champagne' => 'violet', 'slate' => 'blue', 'forest' => 'green', 'maroon' => 'red', 'muted' => 'gray', 'neutral' => 'gray'];
$tone = (string) ($tone ?? ($tones[$status] ?? 'gray'));
$tone = $aliases[$tone] ?? $tone;
$tone = in_array($tone, ['green', 'gray', 'blue', 'violet', 'amber', 'red', 'ink'], true) ? $tone : 'gray';
$label = (string) ($label ?? ucwords(str_replace('_', ' ', $status)));
$pulse = !empty($pulse) || $status === 'awaiting_verification';
?>
<span class="adm-badge adm-badge--<?= e($tone) ?><?= $pulse ? ' adm-badge--pulse' : '' ?><?= !empty($small) ? ' adm-badge--sm' : '' ?>"><?= e($label) ?></span>
