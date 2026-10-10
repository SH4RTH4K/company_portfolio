<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;

class HomeController extends Controller
{
    public function index($locale = 'en')
    {
        if (! in_array($locale, ['en', 'bn'])) {
            abort(404);
        }

        App::setLocale($locale);

        return view('home', compact('locale'));
    }
}
