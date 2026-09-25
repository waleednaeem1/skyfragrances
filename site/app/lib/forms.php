<?php
defined('SKYFR') || exit;

const FORM_TRAP_MIN_SECONDS = 3;
const FORM_TRAP_MAX_SECONDS = 7200;
const FORM_HONEYPOT_FIELD = 'website';
const FORM_TRAP_FIELD = 'ts';

function form_trap_sign(int $timestamp): string
{
    return hash_hmac('sha256', 'form-trap|' . $timestamp, (string) config('security.app_key', ''));
}

function form_trap_parse(string $token): ?int
{
    if (!preg_match('/^(\d{9,11})\.([a-f0-9]{64})$/', $token, $m)) {
        return null;
    }
    $timestamp = (int) $m[1];
    return hash_equals(form_trap_sign($timestamp), $m[2]) ? $timestamp : null;
}

function form_trap_carried_timestamp(): ?int
{
    if (request_method() !== 'POST') {
        return null;
    }
    $timestamp = form_trap_parse(request_string(FORM_TRAP_FIELD, ''));
    if ($timestamp === null || time() - $timestamp > FORM_TRAP_MAX_SECONDS) {
        return null;
    }
    return $timestamp;
}

function form_trap_token(): string
{
    $timestamp = form_trap_carried_timestamp() ?? time();
    return $timestamp . '.' . form_trap_sign($timestamp);
}

function form_trap_field(): string
{
    return '<input type="hidden" name="' . FORM_TRAP_FIELD . '" value="' . e(form_trap_token()) . '">'
        . '<div class="field__honeypot" aria-hidden="true"><label for="hp-' . FORM_HONEYPOT_FIELD . '">Website</label>'
        . '<input id="hp-' . FORM_HONEYPOT_FIELD . '" type="text" name="' . FORM_HONEYPOT_FIELD . '" value="" tabindex="-1" autocomplete="off"></div>';
}

function form_trap_status(): string
{
    if (trim(request_string(FORM_HONEYPOT_FIELD, '')) !== '') {
        return 'bot';
    }
    $timestamp = form_trap_parse(request_string(FORM_TRAP_FIELD, ''));
    if ($timestamp === null) {
        return 'bot';
    }
    $age = time() - $timestamp;
    if ($age < FORM_TRAP_MIN_SECONDS) {
        return 'bot';
    }
    if ($age > FORM_TRAP_MAX_SECONDS) {
        return 'expired';
    }
    return 'ok';
}

function form_trap_log(string $form, string $status): void
{
    log_write('warning', 'form trap: ' . $form . ' ' . $status, ['ip_hash' => request_ip_hash()]);
}
