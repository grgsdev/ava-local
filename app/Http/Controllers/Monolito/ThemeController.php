<?php

namespace App\Http\Controllers\Monolito;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cookie;

class ThemeController extends Controller
{
    public function switch(Request $request)
    {
        $validated = $request->validate(['theme' => 'required|in:light,dark']);

        // Guarda a preferência num cookie que dura 1 ano
        Cookie::queue('theme', $validated['theme'], 60 * 24 * 365);

        return back();
    }
}
