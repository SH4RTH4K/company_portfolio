<?php

use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);

$user = User::where('email', 'admin@zerogravities.com')->first();
if (! $user) {
    exit("Admin user not found\n");
}
Auth::login($user);

$routesToTest = [
    '/admin/dashboard',
    '/admin/business-divisions',
    '/admin/business-divisions/create',
    '/admin/business-divisions/1/edit',
    '/admin/services',
    '/admin/services/create',
    '/admin/services/1/edit',
    '/admin/industries',
    '/admin/industries/create',
    '/admin/industries/1/edit',
    '/admin/portfolios',
    '/admin/portfolios/create',
    '/admin/portfolios/1/edit',
    '/admin/product-categories',
    '/admin/product-categories/create',
    '/admin/product-categories/1/edit',
    '/admin/product-brands',
    '/admin/product-brands/create',
    '/admin/product-brands/1/edit',
    '/admin/products',
    '/admin/products/create',
    '/admin/products/1/edit',
    '/admin/amc-packages',
    '/admin/amc-packages/create',
    '/admin/amc-packages/1/edit',
    '/admin/clients',
    '/admin/clients/create',
    '/admin/clients/1/edit',
    '/admin/testimonials',
    '/admin/testimonials/create',
    '/admin/testimonials/1/edit',
    '/admin/partners',
    '/admin/partners/create',
    '/admin/partners/1/edit',
    '/admin/teams',
    '/admin/teams/create',
    '/admin/teams/1/edit',
    '/admin/post-categories',
    '/admin/post-categories/create',
    '/admin/post-categories/1/edit',
    '/admin/posts',
    '/admin/posts/create',
    '/admin/posts/1/edit',
    '/admin/leads',
    '/admin/seo/dashboard',
    '/admin/seo/robots',
    '/admin/seo/scripts',
    '/admin/redirects',
    '/admin/redirects/create',
    '/admin/redirects/1/edit',
    '/admin/not-found-logs',
    '/admin/audit-logs',
    '/admin/users',
    '/admin/users/create',
    '/admin/users/1/edit',
    '/admin/settings',
];

$passed = 0;
$failed = 0;
$errors = [];

foreach ($routesToTest as $uri) {
    try {
        $request = Request::create($uri, 'GET');
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();

        // Output body if 500 to catch error
        $body = $response->getContent();

        $hasError = false;
        if ($status !== 200) {
            $hasError = true;
        }

        if (strpos($body, 'ErrorException') !== false || strpos($body, 'QueryException') !== false) {
            $hasError = true;
            $status = 'ERR';
        }

        if (! $hasError) {
            echo "✓ [$status] $uri\n";
            $passed++;
        } else {
            echo "✗ [$status] $uri\n";
            $failed++;

            // Extract error
            if (preg_match('/<title>(.*?)<\/title>/s', $body, $tm)) {
                $errorTitle = trim(strip_tags($tm[1] ?? 'Unknown error'));
            } else {
                $errorTitle = 'Unknown Error';
            }

            if (preg_match('/(ErrorException|QueryException|Error)[^<]*<\/(?:span|p|h\d)>(.*?)(?:<|$)/is', $body, $em)) {
                $errorDetail = substr(strip_tags($em[2]), 0, 200);
            } elseif (preg_match('/message.*?"([^"]{10,200})"/i', $body, $mm)) {
                $errorDetail = $mm[1];
            } else {
                $errorDetail = substr($body, 0, 100);
            }

            $errors[] = [
                'uri' => $uri,
                'status' => $status,
                'title' => $errorTitle,
                'detail' => $errorDetail,
            ];
        }
    } catch (Exception $e) {
        echo "✗ [EXC] $uri\n";
        $failed++;
        $errors[] = [
            'uri' => $uri,
            'status' => 'Exception',
            'title' => get_class($e),
            'detail' => $e->getMessage(),
        ];
    }
}

echo "\nTotal: ".count($routesToTest)."\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";

if ($failed > 0) {
    echo "\nErrors:\n";
    foreach ($errors as $e) {
        echo "{$e['uri']} - {$e['title']}: {$e['detail']}\n";
    }
}
