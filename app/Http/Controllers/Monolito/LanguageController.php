<?php

namespace App\Http\Controllers\Monolito;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class LanguageController extends Controller
{
    public function switch(Request $request)
    {
        // Valida se o 'locale' recebido é um dos que permitimos no nosso sistema
        $validator = Validator::make($request->all(), [
            'locale' => 'required|string|in:pt_BR,en,es,it,de,fr,de_CH',
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator);
        }

        // Se for válido, guarda a escolha na sessão do usuário
        $request->session()->put('locale', $request->locale);

        // Redireciona o usuário de volta para a página onde ele estava
        return Redirect::back();
    }
}
