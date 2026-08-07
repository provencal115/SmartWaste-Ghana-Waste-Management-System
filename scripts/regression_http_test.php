<?php
/**
 * HTTP smoke test for public routes and API endpoints.
 */
$base = getenv('SMARTWASTE_TEST_URL') ?: 'http://localhost/finalyearproject/index.php?url=';

$routes = [
    'home' => 200,
    'about' => 200,
    'faq' => 200,
    'contact' => 200,
    'privacy' => 200,
    'terms' => 200,
    'auth/login' => 200,
    'auth/register' => 200,
    'auth/forgot' => 200,
    'api/pricing&plan_id=3&bin_size=medium&zone_id=1' => 200,
    'does-not-exist-route' => 404,
];

$failed = 0;
foreach ($routes as $path => $expected) {
    $url = $base . $path;
    $ctx = stream_context_create(['http' => ['timeout' => 15, 'ignore_errors' => true]]);
    $body = @file_get_contents($url, false, $ctx);
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $status = (int) $m[1];
    }
    $ok = $status === $expected;
    echo ($ok ? 'PASS' : 'FAIL') . ": {$path} (HTTP {$status}, expected {$expected})\n";
    if (!$ok) {
        $failed++;
    }
    if ($path === 'auth/register' && $body !== false) {
        if (str_contains($body, 'password-strength-panel')) {
            echo "FAIL: register page contains static password-strength-panel\n";
            $failed++;
        }
        if (!str_contains($body, 'Address/GPS')) {
            echo "FAIL: register page missing Address/GPS label\n";
            $failed++;
        }
    }
}

echo $failed === 0 ? "\nALL HTTP TESTS PASSED\n" : "\n{$failed} HTTP TEST(S) FAILED\n";
exit($failed > 0 ? 1 : 0);
