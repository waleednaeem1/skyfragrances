<?php
defined('SKYFR') || exit;
$whatsappDigits = preg_replace('/\D+/', '', (string) setting('whatsapp', ''));
if ($whatsappDigits === '') {
    return;
}
$whatsappMessage = "Assalam-o-Alaikum! I have a question about Sky Fragrances.";
$whatsappHref = 'https://wa.me/' . $whatsappDigits . '?text=' . rawurlencode($whatsappMessage);
?>
<a class="whatsapp-fab js-whatsapp-fab" href="<?= e($whatsappHref) ?>" target="_blank" rel="noopener" aria-label="Order on WhatsApp">
  <?php partial('icon.php', ['name' => 'whatsapp', 'size' => 28]); ?>
</a>
