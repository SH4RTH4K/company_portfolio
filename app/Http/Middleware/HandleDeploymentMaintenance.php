<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class HandleDeploymentMaintenance
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Storage::disk('local')->exists('git-deployment/maintenance.json')
            && ! $request->is('admin/system/git-repository', 'admin/system/git-repository/*', 'admin/login', 'admin/logout', 'up')
            && ! ($request->isMethod('GET') && $request->is('system-health', 'system-health/*', 'build/*', 'images/*', 'storage/*', 'favicon.ico'))) {
            $response = $request->expectsJson()
                ? response()->json(['message' => 'A website update is in progress. Please try again shortly.'], 503)
                : response('A website update is in progress. Please try again shortly.', 503);

            return $response
                ->header('Retry-After', '60')
                ->header('Cache-Control', 'no-store');
        }

        return $next($request);
    }
}
