<?php

namespace App\Http\Middleware;

use App\Models\NotFoundLog;
use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class HandleSeoRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin/system/git-repository', 'admin/system/git-repository/*')) {
            return $next($request);
        }

        $path = ltrim($request->getPathInfo(), '/');

        $redirect = null;

        if ($this->tableExists('redirects')) {
            $redirect = Cache::remember('redirect_'.md5($path), 3600, function () use ($path) {
                return Redirect::where('is_active', true)
                    ->where(function ($query) use ($path): void {
                        $query->where('old_url', $path)
                            ->orWhere('old_url', '/'.$path);
                    })
                    ->first();
            });
        }

        if ($redirect) {
            $redirect->increment('hits');

            return redirect($redirect->new_url, $redirect->status_code);
        }

        $response = $next($request);

        if ($response->getStatusCode() === 404 && $this->tableExists('not_found_logs') && ! $request->is('admin/*') && ! $request->is('build/*') && ! $request->ajax()) {
            $url = $request->fullUrl();

            $url = substr($url, 0, 700);

            $log = NotFoundLog::where('url', $url)->first();

            if ($log) {
                $log->increment('hits');
                $log->update(['last_hit_at' => now()]);
            } else {
                NotFoundLog::create([
                    'url' => $url,
                    'hits' => 1,
                    'last_hit_at' => now(),
                ]);
            }
        }

        return $response;
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }
}
