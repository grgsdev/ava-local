<?php

namespace App\Traits;

use App\Models\Address;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Addressable
{
    /**
     * Define a relação polimórfica com os Endereços.
     */
    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    /**
     * Adiciona um novo endereço a este modelo.
     */
    public function addAddress(array $addressData): Address
    {
        return $this->addresses()->create($addressData);
    }

    /**
     * Atualiza um endereço existente associado a este modelo.
     */
    public function updateAddress(Address $address, array $addressData): Address
    {
        // Garante que o endereço que se está a tentar atualizar pertence realmente a este modelo.
        if ($address->addressable_id !== $this->id || $address->addressable_type !== self::class) {
            abort(403, 'Ação não autorizada.');
        }

        $address->update($addressData);
        return $address;
    }

    /**
     * Apaga um endereço associado a este modelo.
     */
    public function deleteAddress(Address $address): ?bool
    {
        // Garante que o endereço que se está a tentar apagar pertence realmente a este modelo.
        if ($address->addressable_id !== $this->id || $address->addressable_type !== self::class) {
            abort(403, 'Ação não autorizada.');
        }

        return $address->delete();
    }

    /**
     * Sincroniza uma lista de endereços, criando, atualizando e apagando conforme necessário.
     * O array de entrada deve conter objetos de endereço. Se um objeto tiver 'id', ele será atualizado.
     * Se não tiver 'id', será criado. Endereços existentes que não estão na lista são apagados.
     */
    public function syncAddresses(array $addresses): void
    {
        $existingIds = $this->addresses()->pluck('id');
        $incomingIds = collect($addresses)->pluck('id')->filter();

        // 1. Apaga os endereços que foram removidos
        $idsToDelete = $existingIds->diff($incomingIds);
        if ($idsToDelete->isNotEmpty()) {
            $this->addresses()->whereIn('id', $idsToDelete)->delete();
        }

        // 2. Atualiza os existentes e cria os novos
        foreach ($addresses as $addressData) {
            if (isset($addressData['id'])) {
                // Atualiza o endereço existente
                $address = $this->addresses()->find($addressData['id']);
                if ($address) {
                    $address->update($addressData);
                }
            } else {
                // Cria um novo endereço
                $this->addresses()->create($addressData);
            }
        }
    }
}
