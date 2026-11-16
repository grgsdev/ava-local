<?php

namespace App\Http\Controllers\Monolito\Admin; // Ou o seu namespace Monolito/Admin

use App\Http\Controllers\BaseController;
use App\Http\Resources\System\RoleResource; // Use o seu resource de Role
use App\Models\System\Role; // Use o seu modelo de Role
use Illuminate\Http\Request;
// ... outros imports

class RoleController extends BaseController
{
    // ... (os seus métodos index, create, store, etc.)

    /**
     * Retorna uma lista de papéis para seletores.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function selection(Request $request)
    {
        $query = Role::query();

        // Filtra pelos papéis específicos que queremos nos seletores
        $query->whereIn('name', ['Admin', 'Affiliate', 'App User', 'Premium']);

        // Se houver um termo de busca, filtra pelo nome
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $roles = $query->orderBy('name')->get();

        // Usamos um Resource para manter o padrão da nossa API
        return RoleResource::collection($roles);
    }
}
