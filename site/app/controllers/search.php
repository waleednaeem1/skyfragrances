<?php
defined('SKYFR') || exit;

$searchQuery = request_query_string('q', '');
$searchQuery = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $searchQuery) ?? '';
$searchQuery = trim(preg_replace('/\s+/u', ' ', $searchQuery) ?? '');
$searchQuery = mb_substr($searchQuery, 0, 60);

require APP_ROOT . '/app/controllers/listing.php';
