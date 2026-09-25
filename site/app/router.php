<?php
defined('SKYFR') || exit;

const ROUTER_PATTERNS = [
    'slug'  => '[a-z0-9]+(?:-[a-z0-9]+)*',
    'order' => 'sf-[0-9]{6}-[a-hj-np-z2-9]{4}',
    'num'   => '[1-9][0-9]{0,5}',
    'any'   => '[^/]+',
];

function router_compile(array $routes): array
{
    $compiled = ['buckets' => [], 'notfound' => null, 'names' => []];
    foreach ($routes as $route) {
        $route += ['method' => 'GET', 'where' => [], 'preset' => [], 'robots' => 'index,follow', 'body_class' => '', 'view' => null];
        $route['methods'] = array_map('strtoupper', explode('|', $route['method']));
        $compiled['names'][$route['name']] = $route;
        if ($route['path'] === '*') {
            $compiled['notfound'] = $route;
            continue;
        }
        foreach ((array) $route['path'] as $path) {
            $entry = $route;
            $entry['path'] = $path;
            $entry['segments'] = router_compile_segments($path, $route['where']);
            $compiled['buckets'][count($entry['segments'])][] = $entry;
        }
    }
    return $compiled;
}

function router_compile_segments(string $path, array $where): array
{
    $segments = [];
    foreach (explode('/', trim($path, '/')) as $segment) {
        if ($segment === '') {
            continue;
        }
        if (preg_match('/^\{([a-z_]+)\}$/', $segment, $m)) {
            $patternName = $where[$m[1]] ?? 'slug';
            $segments[] = ['param' => $m[1], 'regex' => '/^' . ROUTER_PATTERNS[$patternName] . '$/i'];
            continue;
        }
        $segments[] = ['literal' => strtolower($segment)];
    }
    return $segments;
}

function router_match(array $compiled, string $method, string $path): array
{
    $method = strtoupper($method);
    $parts = array_values(array_filter(explode('/', trim($path, '/')), static fn ($p) => $p !== ''));
    $allow = [];
    foreach ($compiled['buckets'][count($parts)] ?? [] as $route) {
        $params = router_match_segments($route['segments'], $parts);
        if ($params === null) {
            continue;
        }
        if (in_array($method, $route['methods'], true) || ($method === 'HEAD' && in_array('GET', $route['methods'], true))) {
            return ['route' => $route, 'params' => $params, 'status' => 200, 'allow' => $route['methods']];
        }
        $allow = array_values(array_unique(array_merge($allow, $route['methods'])));
    }
    if ($allow !== []) {
        return ['route' => $compiled['notfound'], 'params' => [], 'status' => 405, 'allow' => $allow];
    }
    return ['route' => $compiled['notfound'], 'params' => [], 'status' => 404, 'allow' => []];
}

function router_match_segments(array $segments, array $parts): ?array
{
    $params = [];
    foreach ($segments as $i => $segment) {
        if (isset($segment['literal'])) {
            if ($segment['literal'] !== strtolower($parts[$i])) {
                return null;
            }
            continue;
        }
        if (!preg_match($segment['regex'], $parts[$i])) {
            return null;
        }
        $params[$segment['param']] = $parts[$i];
    }
    return $params;
}

function router_head_defaults(array $route): array
{
    return [
        'title' => '',
        'meta_description' => '',
        'canonical' => '',
        'robots' => $route['robots'] ?? 'index,follow',
        'body_class' => $route['body_class'] ?? '',
        'og' => [],
        'jsonld' => [],
    ];
}
