<?php
defined('SKYFR') || exit;

require_once APP_ROOT . '/app/lib/orders.php';

$priced = cart_priced(true);
$cart = cart_present($priced);
$topSizeId = 0;
$topPaisa = -1;
foreach ($cart['lines'] as $line) {
    if ($line['in_stock'] && $line['unit_price_paisa'] > $topPaisa) {
        $topPaisa = $line['unit_price_paisa'];
        $topSizeId = $line['size_id'];
    }
}

$head['title'] = 'Your Cart | Sky Fragrances';
$head['canonical'] = canonical('/cart');
header_no_store();

render($route['view'], [
    'heading' => 'Your Cart',
    'cart' => $cart,
    'topSizeId' => $topSizeId,
    'paymentMethods' => payment_methods_for_view(),
    'trapField' => form_trap_field(),
], $head);
