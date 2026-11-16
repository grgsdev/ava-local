<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PlatformSettingResource;
use App\Models\PlatformSetting;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * @group Configurações Públicas
 * @version V1
 * @description Endpoints para obter as configurações globais da plataforma.
 */
/**
 * @version V1
 *
 * @group API-MOBILE
 *
 * @subgroup Configurações Públicas
 * @description Endpoints para obter as configurações globais da plataforma.
 */
class PlatformSettingController extends Controller
{
    use ApiResponse;

    /**
     * Obter Configurações da Plataforma
     *
     * Retorna um objeto com todas as configurações públicas da plataforma.
     * @unauthenticated
     */
    public function index()
    {
        // Pega a primeira (e única) linha de configurações, ou falha se não existir.
        $settings = PlatformSetting::firstOrFail();

        return new PlatformSettingResource($settings);
    }
}
