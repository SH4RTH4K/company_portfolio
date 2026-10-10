<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class CacheController extends Controller
{
    public function index()
    {
        return view('admin.cache.index');
    }

    public function clear(Request $request)
    {
        $type = $request->input('type');

        try {
            switch ($type) {
                case 'application':
                    Artisan::call('cache:clear');
                    $message = 'Application cache cleared successfully.';
                    break;
                case 'views':
                    Artisan::call('view:clear');
                    $message = 'View cache cleared successfully.';
                    break;
                case 'routes':
                    Artisan::call('route:clear');
                    $message = 'Route cache cleared successfully.';
                    break;
                case 'config':
                    Artisan::call('config:clear');
                    $message = 'Configuration cache cleared successfully.';
                    break;
                case 'all':
                    Artisan::call('optimize:clear');
                    $message = 'All caches cleared successfully.';
                    break;
                default:
                    return back()->with('error', 'Invalid cache type specified.');
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to clear cache: '.$e->getMessage());
        }
    }
}
