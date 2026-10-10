<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    public function index()
    {
        return view('admin.seo.dashboard');
    }

    public function robots()
    {
        $robotsTxt = SiteSetting::where('key', 'robots_txt')->first();

        return view('admin.seo.robots', compact('robotsTxt'));
    }

    public function updateRobots(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        SiteSetting::updateOrCreate(
            ['key' => 'robots_txt'],
            ['value_en' => $request->content, 'value_bn' => $request->content]
        );

        return redirect()->back()->with('success', 'Robots.txt updated successfully.');
    }

    public function globalScripts()
    {
        $headerScripts = SiteSetting::where('key', 'header_scripts')->first();
        $footerScripts = SiteSetting::where('key', 'footer_scripts')->first();

        return view('admin.seo.scripts', compact('headerScripts', 'footerScripts'));
    }

    public function updateGlobalScripts(Request $request)
    {
        SiteSetting::updateOrCreate(
            ['key' => 'header_scripts'],
            ['value_en' => $request->header_scripts, 'value_bn' => $request->header_scripts]
        );

        SiteSetting::updateOrCreate(
            ['key' => 'footer_scripts'],
            ['value_en' => $request->footer_scripts, 'value_bn' => $request->footer_scripts]
        );

        return redirect()->back()->with('success', 'Global scripts updated successfully.');
    }
}
