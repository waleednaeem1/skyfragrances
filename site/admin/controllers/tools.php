<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/catalogue.php';

const TOOLS_PROOF_RETENTION_DAYS = 90;
const TOOLS_REGEN_BUDGET_SECONDS = 20;
const TOOLS_REGEN_BATCH_MAX = 60;
const TOOLS_REGEN_SESSION_KEY = 'tools_regen';
const TOOLS_STOCK_AUDIT_LIMIT = 20;

function tools_log(string $action, string $summary, ?array $after = null): void
{
    catalogue_log('tool', null, $action, $summary, null, $after);
}

function tools_manifest(): array
{
    $file = APP_ROOT . '/db/sample-manifest.php';
    $manifest = is_file($file) ? require $file : [];
    return is_array($manifest) ? $manifest + ['products' => [], 'collections' => [], 'coupons' => [], 'settings' => []] : ['products' => [], 'collections' => [], 'coupons' => [], 'settings' => []];
}

function tools_in_list(string $column, array $values, string $prefix): array
{
    $names = [];
    $bind = [];
    foreach (array_values($values) as $i => $value) {
        $names[] = ':' . $prefix . $i;
        $bind[$prefix . $i] = (string) $value;
    }
    return [$names === [] ? '1 = 0' : $column . ' IN (' . implode(', ', $names) . ')', $bind];
}

function tools_storage(): array
{
    $used = housekeeping_storage_usage_bytes();
    $total = @disk_total_space(APP_ROOT . '/storage');
    $free = @disk_free_space(APP_ROOT . '/storage');
    $percent = null;
    if (is_float($total) && $total > 0 && is_float($free)) {
        $percent = (int) round((($total - $free) / $total) * 100);
    }
    $proofs = 0;
    $proofBytes = 0;
    foreach (glob(APP_ROOT . '/storage/proofs/[0-9][0-9][0-9][0-9]/[0-9][0-9]/*.*') ?: [] as $file) {
        $proofs++;
        $proofBytes += (int) @filesize($file);
    }
    return [
        'used' => $used,
        'used_label' => upload_human_size($used),
        'percent' => $percent,
        'red' => $percent !== null && $percent >= 80,
        'proof_files' => $proofs,
        'proof_label' => upload_human_size($proofBytes),
    ];
}

function tools_cutoff(int $days): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('Asia/Karachi')))->modify('-' . $days . ' days')->format('Y-m-d H:i:s');
}

function tools_purge_preview(): array
{
    $proofCutoff = tools_cutoff(TOOLS_PROOF_RETENTION_DAYS);
    $attemptCutoff = tools_cutoff(HOUSEKEEPING_ATTEMPT_RETENTION_DAYS);
    $bodyCutoff = tools_cutoff(MAIL_OUTBOX_BODY_RETENTION_DAYS);
    $sessions = 0;
    foreach (HOUSEKEEPING_SESSION_LIFETIMES as $dir => $lifetime) {
        foreach (glob(APP_ROOT . '/storage/sessions/' . $dir . '/sess_*') ?: [] as $file) {
            if ((int) @filemtime($file) < time() - $lifetime) {
                $sessions++;
            }
        }
    }
    return [
        'proofs' => (int) db_fetch_column(
            "SELECT COUNT(*) FROM payment_proofs pp JOIN orders o ON o.id = pp.order_id
             WHERE pp.file_path IS NOT NULL AND pp.purged_at IS NULL AND o.status IN ('delivered', 'cancelled')
               AND COALESCE(o.delivered_at, o.cancelled_at, o.updated_at) < :cutoff",
            ['cutoff' => $proofCutoff]
        ),
        'outbox_bodies' => (int) db_fetch_column("SELECT COUNT(*) FROM email_outbox WHERE status = 'sent' AND sent_at < :cutoff AND body_html <> ''", ['cutoff' => $bodyCutoff]),
        'rate_limits' => (int) db_fetch_column('SELECT COUNT(*) FROM rate_limits WHERE attempted_at < :cutoff', ['cutoff' => $attemptCutoff]),
        'login_attempts' => (int) db_fetch_column('SELECT COUNT(*) FROM admin_login_attempts WHERE attempted_at < :cutoff', ['cutoff' => $attemptCutoff]),
        'sessions' => $sessions,
        'proof_days' => TOOLS_PROOF_RETENTION_DAYS,
        'body_days' => MAIL_OUTBOX_BODY_RETENTION_DAYS,
        'attempt_days' => HOUSEKEEPING_ATTEMPT_RETENTION_DAYS,
    ];
}

function tools_purge_proofs(): array
{
    $rows = db_fetch_all(
        "SELECT pp.id, pp.file_path FROM payment_proofs pp JOIN orders o ON o.id = pp.order_id
         WHERE pp.file_path IS NOT NULL AND pp.purged_at IS NULL AND o.status IN ('delivered', 'cancelled')
           AND COALESCE(o.delivered_at, o.cancelled_at, o.updated_at) < :cutoff
         ORDER BY pp.id ASC LIMIT 500",
        ['cutoff' => tools_cutoff(TOOLS_PROOF_RETENTION_DAYS)]
    );
    $files = 0;
    $records = 0;
    $now = now_karachi();
    foreach ($rows as $row) {
        $full = image_proof_path((string) $row['file_path']);
        if ($full !== null && @unlink($full)) {
            $files++;
        }
        $records += db_query(
            'UPDATE payment_proofs SET file_path = NULL, purged_at = :now WHERE id = :id AND purged_at IS NULL',
            ['now' => $now, 'id' => (int) $row['id']]
        )->rowCount();
    }
    return ['files' => $files, 'records' => $records];
}

function tools_purge_run(): array
{
    $proofs = tools_purge_proofs();
    $report = housekeeping_run();
    return [
        'proof_files' => $proofs['files'],
        'proof_records' => $proofs['records'],
        'outbox_bodies' => (int) $report['outbox_bodies'],
        'rate_limits' => (int) $report['rate_limits'],
        'login_attempts' => (int) $report['login_attempts'],
        'sessions' => (int) $report['sessions'],
        'proof_orphans' => (int) $report['proof_orphans'],
    ];
}

function tools_sample_preview(): array
{
    $manifest = tools_manifest();
    $preview = ['reviews' => 0, 'products' => [], 'collections' => [], 'coupons' => [], 'settings' => [], 'any' => false];
    $preview['reviews'] = (int) db_fetch_column('SELECT COUNT(*) FROM reviews WHERE is_sample = 1');
    [$where, $bind] = tools_in_list('slug', $manifest['products'], 's');
    foreach (db_fetch_all('SELECT id, name, slug, collection_id FROM products WHERE deleted_at IS NULL AND ' . $where . ' ORDER BY name ASC', $bind) as $product) {
        $product['sold'] = db_exists('SELECT 1 FROM order_items WHERE product_id = :id LIMIT 1', ['id' => (int) $product['id']]);
        $preview['products'][] = $product;
    }
    $sampleProductIds = array_map(static fn (array $p): int => (int) $p['id'], array_filter($preview['products'], static fn (array $p): bool => !$p['sold']));
    [$where, $bind] = tools_in_list('slug', $manifest['collections'], 'c');
    foreach (db_fetch_all('SELECT id, name, slug, image FROM collections WHERE ' . $where . ' ORDER BY name ASC', $bind) as $collection) {
        [$idWhere, $idBind] = tools_in_list('id', $sampleProductIds, 'p');
        $collection['others'] = (int) db_fetch_column('SELECT COUNT(*) FROM products WHERE collection_id = :cid AND deleted_at IS NULL AND NOT (' . $idWhere . ')', ['cid' => (int) $collection['id']] + $idBind);
        $preview['collections'][] = $collection;
    }
    [$where, $bind] = tools_in_list('code', $manifest['coupons'], 'k');
    foreach (db_fetch_all('SELECT id, code, used_count, is_active FROM coupons WHERE ' . $where . ' ORDER BY code ASC', $bind) as $coupon) {
        $coupon['used'] = (int) $coupon['used_count'] > 0
            || db_exists('SELECT 1 FROM coupon_redemptions WHERE coupon_id = :id LIMIT 1', ['id' => (int) $coupon['id']])
            || db_exists('SELECT 1 FROM orders WHERE coupon_id = :id LIMIT 1', ['id' => (int) $coupon['id']]);
        if ($coupon['used'] && (int) $coupon['is_active'] === 0) {
            continue;
        }
        $preview['coupons'][] = $coupon;
    }
    foreach ($manifest['settings'] as $key) {
        if (trim((string) setting((string) $key, '')) !== '') {
            $preview['settings'][] = (string) $key;
        }
    }
    $preview['any'] = $preview['reviews'] > 0 || $preview['products'] !== [] || $preview['collections'] !== [] || $preview['coupons'] !== [];
    return $preview;
}

function tools_legacy_image_files(string $filename, int $productId): array
{
    $relative = str_contains($filename, '/') ? $filename : $productId . '/' . $filename;
    if (!preg_match('#^[a-z0-9_/-]+\.(webp|jpe?g|png)$#i', $relative) || str_contains($relative, '..')) {
        return [];
    }
    $dir = dirname($relative);
    $base = pathinfo($relative, PATHINFO_FILENAME);
    $files = [];
    foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
        $files[] = APP_ROOT . '/uploads/products/' . $dir . '/' . $base . '.' . $ext;
    }
    foreach (array_keys(IMAGE_PRODUCT_SIZES) as $size) {
        foreach (['webp', 'jpg'] as $ext) {
            $files[] = APP_ROOT . '/uploads/products/' . $dir . '/' . $base . '-' . $size . '.' . $ext;
        }
    }
    $files[] = APP_ROOT . '/uploads/og/' . $dir . '/' . $base . '-og.jpg';
    return $files;
}

function tools_collection_image_files(string $image): array
{
    $base = pathinfo(basename($image), PATHINFO_FILENAME);
    if (!preg_match('/^[a-z0-9_-]+$/i', $base)) {
        return [];
    }
    $files = [];
    foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
        $files[] = APP_ROOT . '/uploads/collections/' . $base . '.' . $ext;
    }
    foreach (array_keys(IMAGE_COLLECTION_SIZES) as $size) {
        foreach (['webp', 'jpg'] as $ext) {
            $files[] = APP_ROOT . '/uploads/collections/' . $base . '-' . $size . '.' . $ext;
        }
    }
    return $files;
}

function tools_unlink_all(array $files): int
{
    $removed = 0;
    foreach (array_unique($files) as $file) {
        if (is_file($file) && @unlink($file)) {
            $removed++;
        }
    }
    foreach ([APP_ROOT . '/uploads/products/sample', APP_ROOT . '/uploads/og/sample'] as $dir) {
        if (is_dir($dir) && count(scandir($dir) ?: []) <= 2) {
            @rmdir($dir);
        }
    }
    return $removed;
}

function tools_sample_remove(): array
{
    $preview = tools_sample_preview();
    $report = ['reviews' => 0, 'products_deleted' => [], 'products_kept' => [], 'collections_deleted' => [], 'collections_kept' => [], 'coupons_deleted' => [], 'coupons_kept' => [], 'files' => 0];
    $files = [];
    $now = now_karachi();
    db_transaction(static function () use ($preview, &$report, &$files, $now): void {
        $report['reviews'] = db_query('DELETE FROM reviews WHERE is_sample = 1')->rowCount();
        foreach ($preview['products'] as $product) {
            $id = (int) $product['id'];
            if ($product['sold']) {
                db_update('products', ['is_active' => 0, 'deleted_at' => $now, 'updated_at' => $now], ['id' => $id]);
                $report['products_kept'][] = (string) $product['name'];
                continue;
            }
            foreach (db_fetch_all('SELECT filename FROM product_images WHERE product_id = :id', ['id' => $id]) as $image) {
                $filename = (string) $image['filename'];
                $stem = catalogue_image_stem(basename($filename));
                if ($stem !== null) {
                    foreach (array_merge(array_keys(IMAGE_PRODUCT_SIZES), ['']) as $size) {
                        foreach (['webp', 'jpg'] as $ext) {
                            $files[] = APP_ROOT . '/uploads/products/' . $id . '/' . $stem . ($size === '' ? '' : '-' . $size) . '.' . $ext;
                        }
                    }
                    $files[] = APP_ROOT . '/uploads/og/' . $stem . '-og.jpg';
                } else {
                    $files = array_merge($files, tools_legacy_image_files($filename, $id));
                }
            }
            db_delete('products', ['id' => $id]);
            $report['products_deleted'][] = (string) $product['name'];
        }
        foreach ($preview['collections'] as $collection) {
            $id = (int) $collection['id'];
            $left = (int) db_fetch_column('SELECT COUNT(*) FROM products WHERE collection_id = :id AND deleted_at IS NULL', ['id' => $id]);
            if ($left > 0) {
                $report['collections_kept'][] = (string) $collection['name'];
                continue;
            }
            $image = (string) ($collection['image'] ?? '');
            if ($image !== '') {
                $stem = catalogue_image_stem(basename($image));
                $files = array_merge($files, $stem !== null ? array_map(static fn (string $f): string => APP_ROOT . '/uploads/collections/' . $f, [$stem . '.webp', $stem . '.jpg', $stem . '-card.webp', $stem . '-card.jpg', $stem . '-zoom.webp', $stem . '-zoom.jpg']) : tools_collection_image_files($image));
            }
            db_delete('collections', ['id' => $id]);
            $report['collections_deleted'][] = (string) $collection['name'];
        }
        foreach ($preview['coupons'] as $coupon) {
            $id = (int) $coupon['id'];
            if ($coupon['used']) {
                db_update('coupons', ['is_active' => 0, 'updated_at' => $now], ['id' => $id]);
                $report['coupons_kept'][] = (string) $coupon['code'];
                continue;
            }
            db_delete('coupons', ['id' => $id]);
            $report['coupons_deleted'][] = (string) $coupon['code'];
        }
        db_query(
            "UPDATE products p SET
               rating_count = (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id AND r.status = 'approved'),
               rating_avg = COALESCE((SELECT ROUND(AVG(r2.rating), 2) FROM reviews r2 WHERE r2.product_id = p.id AND r2.status = 'approved'), 0)"
        );
    });
    foreach (array_unique(array_filter($files, static fn (string $f): bool => str_starts_with($f, APP_ROOT . '/uploads/'))) as $file) {
        $stillUsed = false;
        if (str_contains($file, '/uploads/products/')) {
            $relative = substr($file, strlen(APP_ROOT . '/uploads/products/'));
            $stillUsed = db_exists('SELECT 1 FROM product_images WHERE filename = :f OR filename LIKE :like LIMIT 1', ['f' => $relative, 'like' => preg_replace('/-(thumb|card|zoom)\.(webp|jpg)$/', '', $relative) . '.%']);
        }
        if (!$stillUsed) {
            $report['files'] += tools_unlink_all([$file]);
        }
    }
    foreach (array_filter($preview['products'], static fn (array $p): bool => !$p['sold']) as $product) {
        $dir = APP_ROOT . '/uploads/products/' . (int) $product['id'];
        if (is_dir($dir)) {
            @rmdir($dir);
        }
    }
    return $report;
}

function tools_sample_summary(array $report): string
{
    $parts = [];
    $parts[] = $report['reviews'] . ' sample review' . ($report['reviews'] === 1 ? '' : 's') . ' deleted';
    $parts[] = count($report['products_deleted']) . ' product' . (count($report['products_deleted']) === 1 ? '' : 's') . ' deleted';
    if ($report['products_kept'] !== []) {
        $parts[] = count($report['products_kept']) . ' kept (sold): ' . implode(', ', $report['products_kept']);
    }
    $parts[] = count($report['collections_deleted']) . ' collection' . (count($report['collections_deleted']) === 1 ? '' : 's') . ' deleted';
    if ($report['collections_kept'] !== []) {
        $parts[] = count($report['collections_kept']) . ' collection' . (count($report['collections_kept']) === 1 ? '' : 's') . ' kept (still has products)';
    }
    $parts[] = count($report['coupons_deleted']) . ' coupon' . (count($report['coupons_deleted']) === 1 ? '' : 's') . ' deleted';
    if ($report['coupons_kept'] !== []) {
        $parts[] = count($report['coupons_kept']) . ' coupon' . (count($report['coupons_kept']) === 1 ? '' : 's') . ' switched off (already used)';
    }
    return implode('; ', $parts) . '.';
}

function tools_regen_total(): array
{
    $images = (int) db_fetch_column('SELECT COUNT(*) FROM product_images');
    $collections = (int) db_fetch_column("SELECT COUNT(*) FROM collections WHERE image IS NOT NULL AND image <> ''");
    return ['images' => $images, 'collections' => $collections, 'total' => $images + $collections];
}

function tools_regen_rows(int $offset, int $limit): array
{
    $counts = tools_regen_total();
    $rows = [];
    if ($offset < $counts['images']) {
        foreach (db_fetch_all('SELECT id, product_id, filename FROM product_images ORDER BY id ASC LIMIT :lim OFFSET :off', ['lim' => $limit, 'off' => $offset]) as $row) {
            $rows[] = ['kind' => 'product', 'id' => (int) $row['id'], 'product_id' => (int) $row['product_id'], 'file' => (string) $row['filename']];
        }
    }
    $remaining = $limit - count($rows);
    if ($remaining > 0) {
        $collectionOffset = max(0, $offset - $counts['images']);
        foreach (db_fetch_all("SELECT id, image FROM collections WHERE image IS NOT NULL AND image <> '' ORDER BY id ASC LIMIT :lim OFFSET :off", ['lim' => $remaining, 'off' => $collectionOffset]) as $row) {
            $rows[] = ['kind' => 'collection', 'id' => (int) $row['id'], 'product_id' => 0, 'file' => (string) $row['image']];
        }
    }
    return $rows;
}

function tools_regen_locate(array $row): ?array
{
    $file = $row['file'];
    $stem = catalogue_image_stem(basename($file));
    if ($row['kind'] === 'collection') {
        $base = $stem ?? pathinfo(basename($file), PATHINFO_FILENAME);
        if (!preg_match('/^[a-z0-9_-]+$/i', $base)) {
            return null;
        }
        return ['dir' => APP_ROOT . '/uploads/collections', 'base' => $base, 'sizes' => IMAGE_COLLECTION_SIZES, 'og' => null];
    }
    if ($stem !== null) {
        return ['dir' => APP_ROOT . '/uploads/products/' . $row['product_id'], 'base' => $stem, 'sizes' => IMAGE_PRODUCT_SIZES, 'og' => APP_ROOT . '/uploads/og/' . $stem . '-og.jpg'];
    }
    $relative = str_contains($file, '/') ? $file : $row['product_id'] . '/' . $file;
    if (!preg_match('#^[a-z0-9_/-]+\.(webp|jpe?g|png)$#i', $relative) || str_contains($relative, '..')) {
        return null;
    }
    $dir = dirname($relative);
    $base = pathinfo($relative, PATHINFO_FILENAME);
    return ['dir' => APP_ROOT . '/uploads/products/' . $dir, 'base' => $base, 'sizes' => IMAGE_PRODUCT_SIZES, 'og' => APP_ROOT . '/uploads/og/' . $dir . '/' . $base . '-og.jpg'];
}

function tools_regen_source(array $target, string $originalExt): ?string
{
    $candidates = [
        $target['dir'] . '/' . $target['base'] . '-zoom.jpg',
        $target['dir'] . '/' . $target['base'] . '-zoom.webp',
        $target['dir'] . '/' . $target['base'] . '.' . $originalExt,
        $target['dir'] . '/' . $target['base'] . '.jpg',
        $target['dir'] . '/' . $target['base'] . '.webp',
        $target['dir'] . '/' . $target['base'] . '.png',
    ];
    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }
    return null;
}

function tools_regen_write(GdImage $img, string $path, string $ext, int $quality): bool
{
    $temp = $path . '.' . bin2hex(random_bytes(3)) . '.tmp';
    $ok = $ext === 'webp' ? image_write_webp($img, $temp, $quality) : image_write_jpeg($img, $temp, $quality);
    if (!$ok || !is_file($temp)) {
        @unlink($temp);
        return false;
    }
    if (!@rename($temp, $path)) {
        @unlink($temp);
        return false;
    }
    return true;
}

function tools_regen_one(array $row): bool
{
    $target = tools_regen_locate($row);
    if ($target === null) {
        return false;
    }
    $source = tools_regen_source($target, strtolower(pathinfo($row['file'], PATHINFO_EXTENSION)));
    if ($source === null) {
        return false;
    }
    $src = image_decode($source);
    if ($src === null) {
        return false;
    }
    $ok = true;
    try {
        image_ensure_dir($target['dir']);
        foreach ($target['sizes'] as $key => [$w, $h, $jpegQ, $webpQ]) {
            $derived = image_cover($src, $w, $h);
            $base = $target['dir'] . '/' . $target['base'] . '-' . $key;
            $ok = tools_regen_write($derived, $base . '.jpg', 'jpg', $jpegQ) && $ok;
            if (image_webp_supported()) {
                $ok = tools_regen_write($derived, $base . '.webp', 'webp', $webpQ) && $ok;
            }
            imagedestroy($derived);
        }
        if ($target['og'] !== null) {
            [$ogW, $ogH, $ogQ] = IMAGE_OG_SIZE;
            image_ensure_dir(dirname($target['og']));
            $og = image_cover($src, $ogW, $ogH);
            $ok = tools_regen_write($og, $target['og'], 'jpg', $ogQ) && $ok;
            imagedestroy($og);
        }
    } catch (Throwable $e) {
        log_write('warning', 'tools: regenerate failed', ['kind' => $row['kind'], 'id' => $row['id'], 'error' => $e->getMessage()]);
        $ok = false;
    } finally {
        imagedestroy($src);
    }
    return $ok;
}

function tools_stock_audit(): array
{
    return [
        'pending' => db_fetch_all(
            "SELECT id, order_number, customer_name, city, grand_total, item_count, cancelled_at, cancel_reason, shipped_at
             FROM orders WHERE status = 'cancelled' AND stock_restored_at IS NULL
             ORDER BY cancelled_at DESC, id DESC LIMIT " . TOOLS_STOCK_AUDIT_LIMIT
        ),
        'pending_total' => (int) db_fetch_column("SELECT COUNT(*) FROM orders WHERE status = 'cancelled' AND stock_restored_at IS NULL"),
        'restored_total' => (int) db_fetch_column("SELECT COUNT(*) FROM orders WHERE status = 'cancelled' AND stock_restored_at IS NOT NULL"),
    ];
}

function tools_regen_job(): ?array
{
    $job = $_SESSION[TOOLS_REGEN_SESSION_KEY] ?? null;
    return is_array($job) && isset($job['token']) ? $job : null;
}

function tools_regen_handle(array $head): never
{
    if (request_method() === 'POST') {
        $counts = tools_regen_total();
        if ($counts['total'] === 0) {
            flash('info', 'There are no images to regenerate yet.');
            redirect('/admin/tools', 303);
        }
        $_SESSION[TOOLS_REGEN_SESSION_KEY] = ['token' => bin2hex(random_bytes(12)), 'started' => now_karachi(), 'done' => 0, 'failed' => 0, 'total' => $counts['total']];
        redirect('/admin/tools/regenerate-images?offset=0&job=' . $_SESSION[TOOLS_REGEN_SESSION_KEY]['token'], 303);
    }
    $job = tools_regen_job();
    $token = (string) request_query('job', '');
    $offset = max(0, (int) request_query('offset', 0));
    if ($job === null || $token === '' || !hash_equals((string) $job['token'], $token)) {
        render_admin('tools-progress.php', ['mode' => 'idle', 'counts' => tools_regen_total(), 'webp' => image_webp_supported()], ['title' => 'Regenerate images', 'back' => '/admin/tools'] + $head);
    }
    $started = microtime(true);
    $processed = 0;
    $failed = 0;
    while ((microtime(true) - $started) < TOOLS_REGEN_BUDGET_SECONDS && $processed < TOOLS_REGEN_BATCH_MAX) {
        $rows = tools_regen_rows($offset + $processed, 5);
        if ($rows === []) {
            break;
        }
        foreach ($rows as $row) {
            if (!tools_regen_one($row)) {
                $failed++;
            }
            $processed++;
            if ((microtime(true) - $started) >= TOOLS_REGEN_BUDGET_SECONDS) {
                break;
            }
        }
    }
    $job['done'] = (int) $job['done'] + $processed;
    $job['failed'] = (int) $job['failed'] + $failed;
    $job['total'] = tools_regen_total()['total'];
    $next = $offset + $processed;
    if ($processed === 0 || $next >= $job['total']) {
        unset($_SESSION[TOOLS_REGEN_SESSION_KEY]);
        $summary = 'Regenerated ' . ($job['done'] - $job['failed']) . ' of ' . $job['total'] . ' images' . ($job['failed'] > 0 ? ', ' . $job['failed'] . ' could not be rebuilt (source missing)' : '') . '.';
        tools_log('tool.regenerate_images', $summary, ['done' => $job['done'], 'failed' => $job['failed'], 'total' => $job['total'], 'started' => $job['started']]);
        flash($job['failed'] > 0 ? 'info' : 'success', $summary . ($job['failed'] > 0 ? ' The app log lists which ones.' : ''));
        redirect('/admin/tools', 303);
    }
    $_SESSION[TOOLS_REGEN_SESSION_KEY] = $job;
    render_admin('tools-progress.php', [
        'mode' => 'running',
        'done' => (int) $job['done'],
        'failed' => (int) $job['failed'],
        'total' => (int) $job['total'],
        'nextUrl' => '/admin/tools/regenerate-images?offset=' . $next . '&job=' . $job['token'],
        'webp' => image_webp_supported(),
    ], ['title' => 'Regenerating images', 'back' => '/admin/tools'] + $head);
}

$routeName = (string) ($route['name'] ?? 'admin.tools');

if ($routeName === 'admin.tools.regenerate_images') {
    tools_regen_handle($head);
}

if ($routeName === 'admin.tools.remove_sample_data') {
    if (request_method() === 'POST') {
        if ((string) request_post('confirm_word', '') !== 'DELETE') {
            flash('error', 'Type DELETE in the box to remove the sample data.');
            redirect('/admin/tools/remove-sample-data', 303);
        }
        $preview = tools_sample_preview();
        if (!$preview['any']) {
            flash('info', 'There is no sample data left to remove.');
            redirect('/admin/tools', 303);
        }
        $report = tools_sample_remove();
        settings_cache_clear();
        $summary = tools_sample_summary($report);
        tools_log('tool.remove_sample_data', $summary, $report);
        flash('success', 'Sample data removed. ' . $summary . ($report['files'] > 0 ? ' ' . $report['files'] . ' image files deleted.' : ''));
        if ($preview['settings'] !== []) {
            flash('info', 'Sample bank, JazzCash and Easypaisa details were left in place — replace them under Settings › Payments before you go live.');
        }
        redirect('/admin/tools', 303);
    }
    render_admin('tools-confirm.php', ['kind' => 'sample', 'preview' => tools_sample_preview()], ['title' => 'Remove sample data', 'back' => '/admin/tools'] + $head);
}

if ($routeName === 'admin.tools.purge') {
    if (request_method() === 'POST') {
        $report = tools_purge_run();
        $summary = sprintf(
            '%d proof file%s deleted (%d record%s cleared), %d email bod%s stubbed, %d rate-limit row%s and %d login attempt%s pruned, %d expired session%s removed, %d orphan proof file%s removed.',
            $report['proof_files'], $report['proof_files'] === 1 ? '' : 's',
            $report['proof_records'], $report['proof_records'] === 1 ? '' : 's',
            $report['outbox_bodies'], $report['outbox_bodies'] === 1 ? 'y' : 'ies',
            $report['rate_limits'], $report['rate_limits'] === 1 ? '' : 's',
            $report['login_attempts'], $report['login_attempts'] === 1 ? '' : 's',
            $report['sessions'], $report['sessions'] === 1 ? '' : 's',
            $report['proof_orphans'], $report['proof_orphans'] === 1 ? '' : 's'
        );
        tools_log('tool.purge', $summary, $report);
        flash('success', 'Privacy purge finished. ' . $summary);
        redirect('/admin/tools', 303);
    }
    render_admin('tools-confirm.php', ['kind' => 'purge', 'preview' => tools_purge_preview()], ['title' => 'Privacy purge', 'back' => '/admin/tools'] + $head);
}

if ($routeName !== 'admin.tools' || request_method() !== 'GET') {
    abort(404);
}

$outbox = mail_outbox_counts();
$sample = tools_sample_preview();
render_admin('tools.php', [
    'storage' => tools_storage(),
    'outbox' => $outbox,
    'outboxFailed' => $outbox['failed'] > 0 ? db_fetch_all("SELECT id, recipient, subject, last_error, attempts, next_try_at FROM email_outbox WHERE status = 'failed' ORDER BY id DESC LIMIT 5") : [],
    'mailTransport' => mail_transport_available(),
    'sample' => $sample,
    'sampleCount' => $sample['reviews'] + count($sample['products']) + count($sample['collections']) + count($sample['coupons']),
    'purge' => tools_purge_preview(),
    'regen' => tools_regen_total(),
    'webp' => image_webp_supported(),
    'httpsPermanent' => https_redirect_is_permanent(APP_ROOT),
    'httpsLocal' => in_array(request_host(), ['localhost', '127.0.0.1', '::1'], true) || !request_is_https(),
    'stock' => tools_stock_audit(),
    'installerPresent' => is_file(APP_ROOT . '/install.php'),
    'maintenanceFlag' => maintenance_flag_present(),
    'maintenanceSetting' => maintenance_setting_on(),
    'lastRuns' => db_fetch_all("SELECT action, summary, created_at FROM admin_activity_log WHERE entity_type = 'tool' ORDER BY id DESC LIMIT 5"),
], ['title' => 'Tools'] + $head);
