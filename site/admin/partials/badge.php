<?php
defined('SKYFR') || exit;
$status = (string) ($status ?? '');
$tones = [
    'pending' => 'gold', 'confirmed' => 'green', 'packing' => 'slate', 'shipped' => 'champagne', 'delivered' => 'forest', 'cancelled' => 'maroon',
    'unpaid' => 'neutral', 'awaiting_verification' => 'gold', 'paid' => 'green', 'failed' => 'red', 'refunded' => 'blue',
    'approved' => 'green', 'rejected' => 'maroon',
    'new' => 'gold', 'read' => 'neutral', 'replied' => 'green', 'archived' => 'muted',
    'subscribed' => 'green', 'unsubscribed' => 'muted',
    'live' => 'green', 'hidden' => 'muted', 'active' => 'green', 'inactive' => 'muted', 'expired' => 'muted', 'low' => 'red', 'out' => 'maroon',
    'cod' => 'neutral', 'bank' => 'slate', 'jazzcash' => 'red', 'easypaisa' => 'green',
];
$tone = (string) ($tone ?? ($tones[$status] ?? 'neutral'));
$label = (string) ($label ?? ucwords(str_replace('_', ' ', $status)));
$pulse = !empty($pulse) || $status === 'awaiting_verification';
?>
<span class="adm-badge adm-badge--<?= e($tone) ?><?= $pulse ? ' adm-badge--pulse' : '' ?><?= !empty($small) ? ' adm-badge--sm' : '' ?>"><?= e($label) ?></span>
