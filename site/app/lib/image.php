<?php
defined('SKYFR') || exit;

const IMAGE_PRODUCT_SIZES = [
    'thumb' => [200, 250, 80, 78],
    'card' => [600, 750, 82, 80],
    'zoom' => [1400, 1750, 84, 82],
];
const IMAGE_OG_SIZE = [1200, 630, 85];
const IMAGE_COLLECTION_SIZES = [
    'card' => [800, 450, 82, 80],
    'zoom' => [1600, 900, 84, 82],
];
const IMAGE_PROOF_MAX_SIDE = 2000;
const IMAGE_BACKGROUND_RGB = [10, 10, 10];

function image_webp_supported(): bool
{
    static $supported = null;
    if ($supported === null) {
        $supported = function_exists('imagewebp') && (imagetypes() & IMG_WEBP) === IMG_WEBP;
    }
    return $supported;
}

function image_decode(string $path): ?GdImage
{
    $info = @getimagesize($path);
    if (!is_array($info) || !isset($info[2])) {
        return null;
    }
    $loader = match ((int) $info[2]) {
        IMAGETYPE_JPEG => 'imagecreatefromjpeg',
        IMAGETYPE_PNG => 'imagecreatefrompng',
        IMAGETYPE_WEBP => 'imagecreatefromwebp',
        default => null,
    };
    if ($loader === null || !function_exists($loader)) {
        return null;
    }
    set_error_handler(static fn (): bool => true);
    try {
        $img = $loader($path);
    } catch (Throwable) {
        $img = false;
    } finally {
        restore_error_handler();
    }
    if (!$img instanceof GdImage) {
        return null;
    }
    imagealphablending($img, false);
    imagesavealpha($img, true);
    return $img;
}

function image_cover(GdImage $src, int $targetW, int $targetH): GdImage
{
    $srcW = imagesx($src);
    $srcH = imagesy($src);
    $targetRatio = $targetW / $targetH;
    $srcRatio = $srcW / $srcH;
    if ($srcRatio > $targetRatio) {
        $cropH = $srcH;
        $cropW = (int) round($srcH * $targetRatio);
    } else {
        $cropW = $srcW;
        $cropH = (int) round($srcW / $targetRatio);
    }
    $cropW = max(1, min($cropW, $srcW));
    $cropH = max(1, min($cropH, $srcH));
    $outW = min($targetW, $cropW);
    $outH = min($targetH, $cropH);
    if ($outW === $cropW && $outH === $cropH) {
        $outW = $cropW;
        $outH = $cropH;
    } else {
        $scale = min($outW / $cropW, $outH / $cropH);
        $outW = max(1, (int) round($cropW * $scale));
        $outH = max(1, (int) round($cropH * $scale));
    }
    $x = (int) floor(($srcW - $cropW) / 2);
    $y = (int) floor(($srcH - $cropH) / 2);
    $dst = imagecreatetruecolor($outW, $outH);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefill($dst, 0, 0, $transparent);
    imagecopyresampled($dst, $src, 0, 0, $x, $y, $outW, $outH, $cropW, $cropH);
    return $dst;
}

function image_flatten(GdImage $img): GdImage
{
    $w = imagesx($img);
    $h = imagesy($img);
    $flat = imagecreatetruecolor($w, $h);
    [$r, $g, $b] = IMAGE_BACKGROUND_RGB;
    imagefill($flat, 0, 0, imagecolorallocate($flat, $r, $g, $b));
    imagealphablending($flat, true);
    imagecopy($flat, $img, 0, 0, 0, 0, $w, $h);
    imageinterlace($flat, true);
    return $flat;
}

function image_write_jpeg(GdImage $img, string $path, int $quality): bool
{
    $flat = image_flatten($img);
    $ok = imagejpeg($flat, $path, $quality);
    imagedestroy($flat);
    return $ok;
}

function image_write_webp(GdImage $img, string $path, int $quality): bool
{
    if (!image_webp_supported()) {
        return false;
    }
    return imagewebp($img, $path, $quality);
}

function image_ensure_dir(string $dir): void
{
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Cannot create image directory');
    }
}

function image_stem(string $entity, int $entityId, string $tmpPath): string
{
    $hash = sha1($entityId . '|' . basename($tmpPath) . '|' . microtime(true) . '|' . bin2hex(random_bytes(8)));
    return $entity . '-' . $entityId . '-' . substr($hash, 0, 10);
}

function image_write_sizes(GdImage $src, array $sizes, string $dir, string $stem): void
{
    image_ensure_dir($dir);
    foreach ($sizes as $key => [$w, $h, $jpegQ, $webpQ]) {
        $derived = image_cover($src, $w, $h);
        $base = $dir . '/' . $stem . '-' . $key;
        if (!image_write_jpeg($derived, $base . '.jpg', $jpegQ)) {
            imagedestroy($derived);
            throw new RuntimeException('Cannot write ' . $key . ' jpeg');
        }
        image_write_webp($derived, $base . '.webp', $webpQ);
        imagedestroy($derived);
    }
}

function image_derive_product(string $tmpPath, int $productId): string
{
    $src = image_decode($tmpPath);
    if ($src === null) {
        throw new RuntimeException('Image could not be decoded');
    }
    $stem = image_stem('product', $productId, $tmpPath);
    try {
        image_write_sizes($src, IMAGE_PRODUCT_SIZES, APP_ROOT . '/uploads/products/' . $productId, $stem);
        [$ogW, $ogH, $ogQ] = IMAGE_OG_SIZE;
        $ogDir = APP_ROOT . '/uploads/og';
        image_ensure_dir($ogDir);
        $og = image_cover($src, $ogW, $ogH);
        $ok = image_write_jpeg($og, $ogDir . '/' . $stem . '-og.jpg', $ogQ);
        imagedestroy($og);
        if (!$ok) {
            throw new RuntimeException('Cannot write og jpeg');
        }
    } catch (Throwable $e) {
        image_delete($stem);
        throw $e;
    } finally {
        imagedestroy($src);
    }
    return $stem;
}

function image_derive_collection(string $tmpPath, int $collectionId): string
{
    $src = image_decode($tmpPath);
    if ($src === null) {
        throw new RuntimeException('Image could not be decoded');
    }
    $stem = image_stem('collection', $collectionId, $tmpPath);
    try {
        image_write_sizes($src, IMAGE_COLLECTION_SIZES, APP_ROOT . '/uploads/collections', $stem);
    } catch (Throwable $e) {
        image_delete($stem);
        throw $e;
    } finally {
        imagedestroy($src);
    }
    return $stem;
}

function image_store_proof(string $tmpPath, string $orderNumber): string
{
    $src = image_decode($tmpPath);
    if ($src === null) {
        throw new RuntimeException('Proof image could not be decoded');
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $scale = min(1, IMAGE_PROOF_MAX_SIDE / max($w, $h));
    $outW = max(1, (int) round($w * $scale));
    $outH = max(1, (int) round($h * $scale));
    $resized = imagecreatetruecolor($outW, $outH);
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    imagecopyresampled($resized, $src, 0, 0, 0, 0, $outW, $outH, $w, $h);
    imagedestroy($src);
    $now = now_karachi();
    $relDir = substr($now, 0, 4) . '/' . substr($now, 5, 2);
    $dir = APP_ROOT . '/storage/proofs/' . $relDir;
    image_ensure_dir($dir);
    $relPath = $relDir . '/' . bin2hex(random_bytes(16)) . '.jpg';
    $ok = image_write_jpeg($resized, APP_ROOT . '/storage/proofs/' . $relPath, 84);
    imagedestroy($resized);
    if (!$ok) {
        throw new RuntimeException('Cannot write proof for ' . $orderNumber);
    }
    return $relPath;
}

function image_proof_path(string $relPath): ?string
{
    if (!preg_match('~^\d{4}/\d{2}/[a-f0-9]{32}\.jpg$~', $relPath)) {
        return null;
    }
    $full = APP_ROOT . '/storage/proofs/' . $relPath;
    return is_file($full) ? $full : null;
}

function image_dir_for_stem(string $stem, string $size): ?string
{
    if ($size === 'og') {
        return '/uploads/og';
    }
    if (preg_match('/^product-(\d+)-[a-f0-9]{10}$/', $stem, $m)) {
        return '/uploads/products/' . $m[1];
    }
    if (preg_match('/^collection-\d+-[a-f0-9]{10}$/', $stem)) {
        return '/uploads/collections';
    }
    return null;
}

function image_url(string $stem, string $size, string $ext = 'webp'): string
{
    $dir = image_dir_for_stem($stem, $size);
    if ($dir === null) {
        return asset('img/placeholder-4x5.svg');
    }
    if ($size === 'og' || ($ext === 'webp' && !image_webp_supported())) {
        $ext = 'jpg';
    }
    return url($dir . '/' . $stem . '-' . $size . '.' . $ext);
}

function image_delete(string $stem): void
{
    $sizes = str_starts_with($stem, 'collection-') ? array_keys(IMAGE_COLLECTION_SIZES) : array_keys(IMAGE_PRODUCT_SIZES);
    foreach ($sizes as $size) {
        $dir = image_dir_for_stem($stem, $size);
        if ($dir === null) {
            continue;
        }
        foreach (['webp', 'jpg'] as $ext) {
            $file = APP_ROOT . $dir . '/' . $stem . '-' . $size . '.' . $ext;
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
    $og = APP_ROOT . '/uploads/og/' . $stem . '-og.jpg';
    if (is_file($og)) {
        @unlink($og);
    }
}
