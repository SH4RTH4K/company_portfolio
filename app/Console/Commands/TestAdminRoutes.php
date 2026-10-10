<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class TestAdminRoutes extends Command
{
    protected $signature = 'test:admin';

    protected $description = 'Test all admin routes internally';

    public function handle()
    {
        $user = User::where('email', 'admin@zerogravities.com')->first();
        if (! $user) {
            $this->error('Admin user not found');

            return;
        }

        auth()->login($user);

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
            '/admin/not-found-logs',
            '/admin/audit-logs',
            '/admin/recycle-bin',
            '/admin/users',
            '/admin/users/create',
            '/admin/users/1/edit',
            '/admin/settings',
        ];

        $passed = 0;
        $failed = 0;

        foreach ($routesToTest as $uri) {
            $request = Request::create($uri, 'GET');

            // Re-bind the request to the container so that helpers like request() work
            app()->instance('request', $request);

            try {
                $response = app()->handle($request);
                $status = $response->getStatusCode();
                $body = $response->getContent();

                $hasError = false;
                if ($status !== 200) {
                    $hasError = true;
                }

                if (strpos($body, 'ErrorException') !== false || strpos($body, 'QueryException') !== false || strpos($body, 'Whoops!') !== false || strpos($body, 'Error rendering') !== false) {
                    $hasError = true;
                }

                if (! $hasError) {
                    $this->info("✓ [$status] $uri");
                    $passed++;
                } else {
                    $this->error("✗ [$status] $uri");

                    // Extract error detail
                    if (preg_match('/(ErrorException|QueryException|Error|Exception)[^<]*<\/(?:span|p|h\d)>(.*?)(?:<|$)/is', $body, $em)) {
                        $this->line('   Error: '.substr(strip_tags($em[2]), 0, 150));
                    } elseif (preg_match('/message.*?"([^"]{10,200})"/i', $body, $mm)) {
                        $this->line('   Message: '.$mm[1]);
                    } elseif (preg_match('/<title>(.*?)<\/title>/s', $body, $tm)) {
                        $this->line('   Title: '.trim(strip_tags($tm[1])));
                    }
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->error("✗ [EXC] $uri");
                $this->line('   Exception: '.get_class($e).' - '.$e->getMessage());
                $failed++;
            }
        }

        $this->line("\nTotal: ".count($routesToTest));
        $this->info("Passed: $passed");
        if ($failed > 0) {
            $this->error("Failed: $failed");
        } else {
            $this->info('All admin routes passed!');
        }
    }
}
