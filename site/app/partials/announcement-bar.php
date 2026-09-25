<?php
defined('SKYFR') || exit;
$announcementText = trim((string) setting('announcement_text', ''));
if (!setting_bool('announcement_enabled', false) || $announcementText === '') {
    return;
}
$announcementLink = trim((string) setting('announcement_link', ''));
$announcementHash = substr(sha1($announcementText), 0, 12);
?>
<div class="announcement js-announcement" role="region" aria-label="Announcement" data-announcement-hash="<?= e($announcementHash) ?>">
  <div class="announcement__inner">
<?php if ($announcementLink !== ''): ?>
    <a class="announcement__text" href="<?= e(url($announcementLink)) ?>"><?= e($announcementText) ?></a>
<?php else: ?>
    <span class="announcement__text"><?= e($announcementText) ?></span>
<?php endif; ?>
    <button class="announcement__dismiss js-announcement-dismiss" type="button" aria-label="Dismiss announcement">
      <?php partial('icon.php', ['name' => 'close', 'size' => 16]); ?>
    </button>
  </div>
</div>
