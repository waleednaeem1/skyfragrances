<?php
defined('SKYFR') || exit;
$bodyClasses = is_array($bodyClasses ?? null) ? $bodyClasses : [];
if (array_intersect(['home', 'listing', 'collections', 'product', 'quiz'], $bodyClasses) === []) {
    return;
}
?>
<div class="sf-veil" data-motion="transitions" aria-hidden="true"></div>
