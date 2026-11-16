<?php

namespace App\Http\Controllers\Monolito\Admin;

use Inertia\Inertia;

use Inertia\Response;
use Illuminate\Http\Request;
use App\Models\PlatformSetting;
use App\Http\Controllers\BaseController;
use App\Http\Resources\Api\V1\PlatformSetting\PlatformSettingResource;

class PlatformSettingController extends BaseController
{

    public function index(): Response
    {
        $this->breadcrumbsManager->add('Dashboard', route('admin.dashboard'))
            ->add('Configurações da Plataforma');

        // Pega a primeira (e única) linha de configurações
        $settings = PlatformSetting::firstOrFail();

        return Inertia::render('Admin/Settings/Index', [
            'settings' => new PlatformSettingResource($settings),
        ]);
    }
    public function edit(): Response
    {
        $this->breadcrumbsManager->add('Dashboard', route('admin.dashboard'))
            ->add('Configurações da Plataforma');

        // Pega a primeira (e única) linha de configurações
        $settings = PlatformSetting::firstOrFail();

        return Inertia::render('Admin/Settings/Index', [
            'settings' => new PlatformSettingResource($settings),
        ]);
    }

    public function update(Request $request)
    {
        // Validação manual por simplicidade
        $validated = $request->validate([
            'platform_name' => 'required|string|max:255',
            'contact_info' => 'required|array',
            'social_links' => 'required|array',
            'physical_address' => 'nullable|string',

            'logo_web' => 'nullable|image|max:10240',
            'logo_mobile' => 'nullable|image|max:10240',
        ]);

        $settings = PlatformSetting::firstOrFail();
        $settings->update($validated);

        if ($request->hasFile('logo_web')) {
            $settings->clearMediaCollection('logo_web');
            $settings->addMediaFromRequest('logo_web')->toMediaCollection('logo_web');
        }
        if ($request->hasFile('logo_mobile')) {
            $settings->clearMediaCollection('logo_mobile');
            $settings->addMediaFromRequest('logo_mobile')->toMediaCollection('logo_mobile');
        }

        return redirect()->back()->with(
            'flash',
            [
                'type' => 'success', // ou 'error'
                'message' => 'Ação executada com sucesso.',
            ]
        );
    }
}
