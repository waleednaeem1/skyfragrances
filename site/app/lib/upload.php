<?php
defined('SKYFR') || exit;

const UPLOAD_MAX_PRODUCT_BYTES = 6291456;
const UPLOAD_MAX_PROOF_BYTES = 5242880;
const UPLOAD_MIN_SIDE = 50;
const UPLOAD_MAX_SIDE = 10000;
const UPLOAD_MAX_PIXELS = 40000000;
const UPLOAD_FILES_PER_REQUEST = 1;
const UPLOAD_BYTES_PER_PIXEL = 5;
const UPLOAD_RESIZE_OVERHEAD_BYTES = 50331648;
const UPLOAD_ALLOWED_MIMES = [
    'image/jpeg' => IMAGETYPE_JPEG,
    'image/png' => IMAGETYPE_PNG,
    'image/webp' => IMAGETYPE_WEBP,
];

function upload_validate(array $file, int $maxBytes): array
{
    $fail = static fn (string $error): array => ['ok' => false, 'error' => $error, 'mime' => '', 'width' => 0, 'height' => 0];

    $code = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($code !== UPLOAD_ERR_OK) {
        return $fail(upload_error_message($code));
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return $fail('The file did not arrive as an upload. Please try again.');
    }

    $bytes = (int) filesize($tmp);
    if ($bytes <= 0) {
        return $fail('The file is empty.');
    }
    if ($bytes > $maxBytes) {
        return $fail('The file is larger than ' . upload_human_size($maxBytes) . '. Please upload a smaller image.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? (string) finfo_file($finfo, $tmp) : '';
    if ($finfo) {
        finfo_close($finfo);
    }
    if (!isset(UPLOAD_ALLOWED_MIMES[$mime])) {
        return $fail('Only JPG, PNG or WEBP images are accepted.');
    }

    $info = @getimagesize($tmp);
    if (!is_array($info) || !isset($info[0], $info[1], $info[2])) {
        return $fail('The file is not a readable image.');
    }
    if ((int) $info[2] !== UPLOAD_ALLOWED_MIMES[$mime]) {
        return $fail('The file contents do not match its image type.');
    }

    $width = (int) $info[0];
    $height = (int) $info[1];
    if ($width < UPLOAD_MIN_SIDE || $height < UPLOAD_MIN_SIDE) {
        return $fail('The image is too small. It must be at least ' . UPLOAD_MIN_SIDE . ' pixels on each side.');
    }
    if ($width > UPLOAD_MAX_SIDE || $height > UPLOAD_MAX_SIDE || $width * $height > UPLOAD_MAX_PIXELS) {
        return $fail('The image is too large. It must be no more than ' . UPLOAD_MAX_SIDE . ' pixels on each side.');
    }
    if ($width * $height > upload_pixel_budget()) {
        return $fail('This photo is too large for the server to process (' . number_format($width * $height / 1000000, 1) . ' megapixels; the limit is ' . number_format(upload_pixel_budget() / 1000000, 1) . '). Choose "Medium" or "Large" when sharing it from your gallery, or take it at a lower resolution.');
    }

    return ['ok' => true, 'error' => '', 'mime' => $mime, 'width' => $width, 'height' => $height];
}

function upload_memory_limit_bytes(): int
{
    return request_ini_bytes((string) ini_get('memory_limit'));
}

function upload_pixel_budget(): int
{
    $limit = upload_memory_limit_bytes();
    if ($limit === PHP_INT_MAX) {
        return UPLOAD_MAX_PIXELS;
    }
    $available = $limit - UPLOAD_RESIZE_OVERHEAD_BYTES - memory_get_usage();
    return max(1000000, min(UPLOAD_MAX_PIXELS, intdiv($available, UPLOAD_BYTES_PER_PIXEL)));
}

function upload_validate_product(array $file): array
{
    return upload_validate($file, UPLOAD_MAX_PRODUCT_BYTES);
}

function upload_validate_proof(array $file): array
{
    $result = upload_validate($file, UPLOAD_MAX_PROOF_BYTES);
    if ($result['ok'] && ($result['width'] > 4000 || $result['height'] > 4000)) {
        return ['ok' => false, 'error' => 'The screenshot is too large. Please upload one no bigger than 4000 pixels on each side.', 'mime' => '', 'width' => 0, 'height' => 0];
    }
    return $result;
}

function upload_error_message(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file is too large to upload.',
        UPLOAD_ERR_PARTIAL => 'The upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_FILE => 'Please choose an image to upload.',
        UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'The server could not store the upload. Please try again later.',
        UPLOAD_ERR_EXTENSION => 'The upload was blocked by the server.',
        default => 'The upload failed. Please try again.',
    };
}

function upload_human_size(int $bytes): string
{
    if ($bytes >= 1048576) {
        return rtrim(rtrim(number_format($bytes / 1048576, 1), '0'), '.') . ' MB';
    }
    return (string) (int) ceil($bytes / 1024) . ' KB';
}

function upload_extension_for_mime(string $mime): string
{
    return match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => '',
    };
}

function upload_files_from_request(string $field): array
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return [];
    }
    $raw = $_FILES[$field];
    if (!is_array($raw['name'] ?? null)) {
        return [$raw];
    }
    $files = [];
    foreach (array_keys($raw['name']) as $i) {
        $files[] = [
            'name' => $raw['name'][$i] ?? '',
            'type' => $raw['type'][$i] ?? '',
            'tmp_name' => $raw['tmp_name'][$i] ?? '',
            'error' => $raw['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $raw['size'][$i] ?? 0,
        ];
    }
    return array_slice($files, 0, UPLOAD_FILES_PER_REQUEST);
}
