<?php
defined('SKYFR') || exit;
$announcementText = trim((string) setting('announcement_text', ''));
if (!setting_bool('announcement_enabled', false) || $announcementText === '') {
    return;
}
$announcementLink = trim((string) setting('announcement_link', ''));
$announcementHash = substr(sha1($announcementText), 0, 12);
$announcementParts = preg_split('/\s+[—–·|•✦]\s+/u', $announcementText) ?: [$announcementText];
?>
<div class="announcement js-announcement" role="region" aria-label="Announcement" data-announcement-hash="<?= e($announcementHash) ?>">
  <div class="announcement__inner">
    <<?= $announcementLink !== '' ? 'a href="' . e(url($announcementLink)) . '"' : 'p' ?> class="announcement__text"><?php foreach ($announcementParts as $announcementPart): ?><span class="announcement__part"><?= e($announcementPart) ?></span><?php endforeach; ?></<?= $announcementLink !== '' ? 'a' : 'p' ?>>
    <button class="announcement__dismiss js-announcement-dismiss" type="button" aria-label="Dismiss announcement">
      <?php partial('icon.php', ['name' => 'close', 'size' => 16]); ?>
    </button>
  </div>
</div>
