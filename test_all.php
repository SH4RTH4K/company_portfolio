<?php

/**
 * Comprehensive Admin + Frontend Audit Script
 * Tests all pages and reports any errors
 */
$base = 'http://127.0.0.1:8000';
$results = [];
$errors = [];

function request($url, $sessionCookie = null)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Accept: text/html'],
        CURLOPT_COOKIE => $sessionCookie ?? '',
        CURLOPT_COOKIEJAR => sys_get_temp_dir().'/zg_cookies.txt',
        CURLOPT_COOKIEFILE => sys_get_temp_dir().'/zg_cookies.txt',
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    return [$status, $body, $finalUrl];
}

function loginAdmin($base)
{
    // Get CSRF token
    $ch = curl_init("$base/admin/login");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_COOKIEJAR => sys_get_temp_dir().'/zg_cookies.txt',
        CURLOPT_COOKIEFILE => sys_get_temp_dir().'/zg_cookies.txt',
    ]);
    $body = curl_exec($ch);
    curl_close($ch);

    // Extract CSRF token
    preg_match('/<input[^>]+name="_token"[^>]+value="([^"]+)"/', $body, $m);
    $token = $m[1] ?? '';

    if (! $token) {
        echo "  !! Could not extract CSRF token\n";

        return false;
    }

    // Login
    $ch = curl_init("$base/admin/login");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            '_token' => $token,
            'email' => 'admin@zerogravities.com',
            'password' => 'password',
        ]),
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_COOKIEJAR => sys_get_temp_dir().'/zg_cookies.txt',
        CURLOPT_COOKIEFILE => sys_get_temp_dir().'/zg_cookies.txt',
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    $loggedIn = strpos($finalUrl, '/admin/dashboard') !== false || strpos($body, 'Dashboard') !== false;
    echo "  Login status: $status, Final URL: $finalUrl, Success: ".($loggedIn ? 'YES' : 'NO')."\n";
    if (! $loggedIn) {
        if (preg_match('/<div[^>]*class="[^"]*invalid-feedback[^"]*"[^>]*>(.*?)<\/div>/is', $body, $m)) {
            echo '  Login Error: '.trim(strip_tags($m[1]))."\n";
        } elseif (preg_match('/alert-danger.*?>(.*?)</is', $body, $m)) {
            echo '  Login Error: '.trim(strip_tags($m[1]))."\n";
        }
    }

    return $loggedIn;
}

function test($label, $url, $expectedStatus = 200, $checkFor = null)
{
    global $results, $errors;
    [$status, $body, $finalUrl] = request($url);

    $ok = $status === $expectedStatus;
    $hasError = $body && (
        strpos($body, 'Whoops!') !== false ||
        strpos($body, 'ErrorException') !== false ||
        strpos($body, 'Error rendering') !== false ||
        strpos($body, 'syntax error') !== false ||
        strpos($body, 'Undefined variable') !== false ||
        strpos($body, 'Call to a member function') !== false ||
        strpos($body, 'Class not found') !== false ||
        strpos($body, 'SQLSTATE') !== false ||
        strpos($body, 'QueryException') !== false
    );

    $checkOk = true;
    if ($checkFor && $body) {
        $checkOk = strpos($body, $checkFor) !== false;
    }

    $icon = ($ok && ! $hasError && $checkOk) ? '✓' : '✗';

    $result = [
        'label' => $label,
        'url' => $url,
        'status' => $status,
        'ok' => $ok && ! $hasError && $checkOk,
        'hasError' => $hasError,
        'checkOk' => $checkOk,
    ];

    if (! $result['ok']) {
        // Extract error details
        if ($hasError) {
            preg_match('/<title>(.*?)<\/title>/s', $body, $tm);
            $errorTitle = trim(strip_tags($tm[1] ?? 'Unknown error'));
            // Try to get first meaningful error message
            if (preg_match('/(ErrorException|QueryException|Error)[^<]*<\/(?:span|p|h\d)>(.*?)(?:<|$)/is', $body, $em)) {
                $result['error_detail'] = substr(strip_tags($em[2]), 0, 200);
            } elseif (preg_match('/message.*?"([^"]{10,200})"/i', $body, $mm)) {
                $result['error_detail'] = $mm[1];
            } else {
                $result['error_detail'] = $errorTitle;
            }
        }
        $errors[] = $result;
    }

    $results[] = $result;
    $statusStr = str_pad($status, 3);
    $label = str_pad($label, 55);
    echo "  $icon [$statusStr] $label ".($hasError ? '(PHP ERROR)' : '').(! $checkOk ? '(CONTENT CHECK FAIL)' : '')."\n";

    return $result['ok'];
}

// Clear old cookies
@unlink(sys_get_temp_dir().'/zg_cookies.txt');

echo "\n=== Zero Gravities Comprehensive Audit ===\n";
echo "Base URL: $base\n\n";

// ===== STEP 1: Test Frontend Routes =====
echo "--- FRONTEND ROUTES ---\n";
test('Home EN', "$base/en");
test('Home BN', "$base/bn");
test('About EN', "$base/en/about");
test('Services List EN', "$base/en/services");
test('Services List BN', "$base/bn/services");
test('Service Detail EN', "$base/en/services/managed-it-annual-maintenance-amc");
test('Products List EN', "$base/en/products");
test('Product Detail EN', "$base/en/products/dell-poweredge-r750-rack-server");
test('Portfolio List EN', "$base/en/portfolio");
test('Portfolio Detail EN', "$base/en/portfolio/garments-group-erp-infra");
test('Industries EN', "$base/en/industries");
test('Managed IT EN', "$base/en/managed-it");
test('Blog List EN', "$base/en/blog");
test('Blog Post EN', "$base/en/blog/why-bangladesh-corporates-need-proactive-it-amc");
test('Contact EN', "$base/en/contact");
test('Robots.txt', "$base/robots.txt");
test('Sitemap XML', "$base/sitemap.xml");

echo "\n--- ADMIN LOGIN ---\n";
$loggedIn = loginAdmin($base);

if (! $loggedIn) {
    echo "\n!! Admin login failed. Cannot test admin pages.\n";
    // Still report frontend results
} else {
    echo "\n--- ADMIN ROUTES ---\n";

    // Dashboard
    test('Dashboard', "$base/admin/dashboard", 200, 'Dashboard');

    // Content Management
    test('Business Divisions Index', "$base/admin/business-divisions");
    test('Business Divisions Create', "$base/admin/business-divisions/create");
    test('Services Index', "$base/admin/services");
    test('Services Create', "$base/admin/services/create");
    test('Industries Index', "$base/admin/industries");
    test('Industries Create', "$base/admin/industries/create");
    test('Portfolios Index', "$base/admin/portfolios");
    test('Portfolios Create', "$base/admin/portfolios/create");

    // Products
    test('Product Categories Index', "$base/admin/product-categories");
    test('Product Categories Create', "$base/admin/product-categories/create");
    test('Product Brands Index', "$base/admin/product-brands");
    test('Product Brands Create', "$base/admin/product-brands/create");
    test('Products Index', "$base/admin/products");
    test('Products Create', "$base/admin/products/create");

    // AMC
    test('AMC Packages Index', "$base/admin/amc-packages");
    test('AMC Packages Create', "$base/admin/amc-packages/create");

    // CRM
    test('Clients Index', "$base/admin/clients");
    test('Clients Create', "$base/admin/clients/create");
    test('Testimonials Index', "$base/admin/testimonials");
    test('Testimonials Create', "$base/admin/testimonials/create");
    test('Partners Index', "$base/admin/partners");
    test('Partners Create', "$base/admin/partners/create");
    test('Teams Index', "$base/admin/teams");
    test('Teams Create', "$base/admin/teams/create");

    // Blog
    test('Post Categories Index', "$base/admin/post-categories");
    test('Post Categories Create', "$base/admin/post-categories/create");
    test('Posts Index', "$base/admin/posts");
    test('Posts Create', "$base/admin/posts/create");

    // Leads
    test('Leads Index', "$base/admin/leads");

    // SEO
    test('SEO Dashboard', "$base/admin/seo/dashboard");
    test('SEO Robots', "$base/admin/seo/robots");
    test('SEO Scripts', "$base/admin/seo/scripts");

    // System
    test('Redirects Index', "$base/admin/redirects");
    test('Redirects Create', "$base/admin/redirects/create");
    test('Not Found Logs', "$base/admin/not-found-logs");
    test('Audit Logs', "$base/admin/audit-logs");
    test('Users Index', "$base/admin/users");
    test('Users Create', "$base/admin/users/create");
    test('Site Settings', "$base/admin/settings");

    // Test edit pages with first records
    echo "\n--- ADMIN EDIT PAGES (Record ID=1) ---\n";
    test('Business Division Edit', "$base/admin/business-divisions/1/edit");
    test('Service Edit', "$base/admin/services/1/edit");
    test('Industry Edit', "$base/admin/industries/1/edit");
    test('Portfolio Edit', "$base/admin/portfolios/1/edit");
    test('Product Category Edit', "$base/admin/product-categories/1/edit");
    test('Product Brand Edit', "$base/admin/product-brands/1/edit");
    test('Product Edit', "$base/admin/products/1/edit");
    test('AMC Package Edit', "$base/admin/amc-packages/1/edit");
    test('Client Edit', "$base/admin/clients/1/edit");
    test('Testimonial Edit', "$base/admin/testimonials/1/edit");
    test('Partner Edit', "$base/admin/partners/1/edit");
    test('Team Edit', "$base/admin/teams/1/edit");
    test('Post Category Edit', "$base/admin/post-categories/1/edit");
    test('Post Edit', "$base/admin/posts/1/edit");
    test('Redirect Edit', "$base/admin/redirects/1/edit");
    test('User Edit', "$base/admin/users/1/edit");
}

// ===== Summary =====
echo "\n=== SUMMARY ===\n";
$total = count($results);
$passed = count(array_filter($results, fn ($r) => $r['ok']));
$failed = $total - $passed;

echo "Total tests: $total\n";
echo "Passed: $passed ✓\n";
echo "Failed: $failed ✗\n\n";

if ($errors) {
    echo "=== FAILED ROUTES ===\n";
    foreach ($errors as $e) {
        echo "  ✗ [{$e['status']}] {$e['label']}\n";
        echo "    URL: {$e['url']}\n";
        if (isset($e['error_detail'])) {
            echo "    Error: {$e['error_detail']}\n";
        }
        if (isset($e['hasError']) && $e['hasError']) {
            echo "    → Has PHP/Blade rendering error\n";
        }
        if (isset($e['checkOk']) && ! $e['checkOk']) {
            echo "    → Content check failed\n";
        }
        echo "\n";
    }
}
