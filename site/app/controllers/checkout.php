<?php
defined('SKYFR') || exit;

require_once APP_ROOT . '/app/lib/orders.php';

if (cart_get()['items'] === []) {
    flash('info', 'Your cart is empty.');
    redirect('/cart', 302);
}

$data = checkout_view_data();
if ($data['cart']['is_empty']) {
    flash('info', 'Your cart is empty.');
    redirect('/cart', 302);
}

$head['title'] = 'Checkout | Sky Fragrances';
$head['canonical'] = canonical('/checkout');
header_no_store();

render($route['view'], $data, $head);
