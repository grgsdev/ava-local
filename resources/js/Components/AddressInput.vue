<script setup lang="ts">
import { reactive, watch } from 'vue';
import axios from 'axios';
import { debounce } from 'lodash';
import type { Address } from '@/types';

const props = defineProps<{
    modelValue: Address;
    index?: number;
    errors?: Record<string, string | undefined>;
}>();

const errorFor = (field: string) => {
    return props.errors?.[field] ?? '';
};
// const errorFor = (field: string) => {
//     return props.errors?.[props.index ?? 0]?.[field] ?? '';
// };
const emit = defineEmits(['update:modelValue', 'remove']);

// Reactive para sincronização
const address = reactive({ ...props.modelValue });

// Loaders
const loadingCep = reactive({ value: false });
const loadingGeo = reactive({ value: false });

// Google Maps API key
const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY || '';

// Buscar endereço via CEP (ViaCEP)
const buscarEnderecoPorCep = async (cep: string) => {
    const cepLimpo = cep.replace(/\D/g, '');
    if (cepLimpo.length !== 8) return null;

    loadingCep.value = true;
    try {
        const { data } = await axios.get(`https://viacep.com.br/ws/${cepLimpo}/json/`);
        if (data.erro) return null;
        return data;
    } catch {
        return null;
    } finally {
        loadingCep.value = false;
    }
};

// Preencher endereço via CEP
const preencherEndereco = async () => {
    if (!address.zip_code) return;
    const dados = await buscarEnderecoPorCep(address.zip_code);
    if (dados) {
        address.street = dados.logradouro ?? '';
        address.neighborhood = dados.bairro ?? '';
        address.city = dados.localidade ?? '';
        address.state = dados.uf ?? '';
        emit('update:modelValue', address);

        if (address.number) {
            debouncedGeocode();
        }
    }
};

// Debounced CEP (800ms)
const debouncedPreencherEndereco = debounce(preencherEndereco, 800);

// Geocoding Google Maps
const geocodeAddress = async () => {
    console.log('Geocoding address:', address);
    if (!address.number || address.number.trim() === '') {
        address.number = 's/n';
    }
    if (!address.street || !address.number || !address.city || !address.state) return;

    const fullAddress = `${address.street}, ${address.number}${address.complement ? ', ' + address.complement : ''
        }, ${address.neighborhood ?? ''}, ${address.city}, ${address.state}, Brazil`;

    loadingGeo.value = true;
    try {

        const response = await axios.get(
            `https://maps.googleapis.com/maps/api/geocode/json`,
            {
                params: { address: fullAddress, key: GOOGLE_MAPS_API_KEY },
                headers: {
                    // Explicitamente remove/garante que x-requested-with não será enviado
                    'x-requested-with': null as any
                }
            }
        );


        // const response = await axios.get(
        //     `https://maps.googleapis.com/maps/api/geocode/json`,
        //     { params: { address: fullAddress, key: GOOGLE_MAPS_API_KEY } }
        // );

        if (response.data.status === 'OK' && response.data.results.length) {
            const location = response.data.results[0].geometry.location;
            address.latitude = location.lat;
            address.longitude = location.lng;
            emit('update:modelValue', address);
        } else {
            console.warn('Geocoding falhou:', response.data.status);
        }
    } catch (error) {
        console.error('Erro geocoding:', error);
    } finally {
        loadingGeo.value = false;
    }
};

// Debounced geocoding
const debouncedGeocode = debounce(geocodeAddress, 800);

// Watch no endereço

watch(
    address,
    (newVal, oldVal) => {
        // Criar uma cópia profunda do oldVal para comparação correta
        const oldValCopy = JSON.parse(JSON.stringify(oldVal));
        console.log('Old address:', oldValCopy);
        console.log('Address changed:', newVal);

        emit('update:modelValue', newVal);

        // if (
        //     newVal.street === oldValCopy.street &&
        //     newVal.number === oldValCopy.number &&
        //     newVal.city === oldValCopy.city &&
        //     newVal.state === oldValCopy.state
        // ) {
        //     console.log('Address fields unchanged, skipping geocode.');
        //     return;
        // } else {
        console.log('Address fields changed, triggering geocode.');
        debouncedGeocode();
        //        }
    },
    { deep: true }
);


// Watch no CEP para debounce
watch(
    () => address.zip_code,
    () => {
        debouncedPreencherEndereco();
    }
);

// Modal mapa
const isMapOpen = reactive({ value: false });

const openMap = () => {
    if (address.latitude && address.longitude) {
        isMapOpen.value = true;
    } else {
        alert('Endereço ainda não possui coordenadas válidas.');
    }
};

const closeMap = () => {
    isMapOpen.value = false;
};
</script>

<template>
    <div class="p-4 space-y-4 border rounded-md">
        <!-- Campos de endereço -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="space-y-1.5">
                <label>Tipo de Endereço</label>
                <select v-model="address.type" class="input" disabled>
                    <option value="main">Principal</option>
                    <option value="comercial">Comercial</option>
                    <option value="financial">Financeiro</option>
                </select>
            </div>

            <!-- CEP -->
            <div class="space-y-1.5 relative">
                <label>CEP</label>
                <input type="text" v-model="address.zip_code" class="input" placeholder="12345-678" />
                <div v-if="loadingCep.value" class="absolute -translate-y-1/2 right-2 top-1/2">
                    <div class="spinner"></div>
                </div>
            </div>

            <!-- Rua -->
            <div class="space-y-1.5">
                <label>Rua</label>
                <input type="text" v-model="address.street" class="input" />
            </div>

            <!-- Número -->
            <div class="space-y-1.5">
                <label>Número</label>
                <input type="text" v-model="address.number" class="input" />
                <p v-if="errorFor('number')" class="mt-1 text-sm text-red-600">{{ errorFor('number') }}</p>
            </div>

            <!-- Complemento -->
            <div class="space-y-1.5">
                <label>Complemento</label>
                <input type="text" v-model="address.complement" class="input" placeholder="Apto, Bloco, etc." />
            </div>

            <!-- Bairro -->
            <div class="space-y-1.5">
                <label>Bairro</label>
                <input type="text" v-model="address.neighborhood" class="input" />
            </div>

            <!-- Cidade -->
            <div class="space-y-1.5">
                <label>Cidade</label>
                <input type="text" v-model="address.city" class="input" />
            </div>

            <!-- UF -->
            <div class="space-y-1.5">
                <label>UF</label>
                <input type="text" maxlength="2" v-model="address.state" class="input" />
            </div>
        </div>

        <!-- Botão mapa -->
        <button type="button" class="mt-4 btn btn-primary" @click="openMap"
            :disabled="!address.latitude || !address.longitude || loadingGeo.value">
            <div v-if="loadingGeo.value" class="flex items-center justify-center gap-2">
                <div class="spinner-small"></div> Carregando mapa...
            </div>
            <span v-else>🗺️ Mostrar no Mapa</span>
        </button>

        <!-- Modal mapa -->
        <div v-if="isMapOpen.value" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75">
            <div class="relative w-full max-w-3xl p-4 bg-white rounded shadow-lg">
                <button class="absolute text-xl font-bold top-2 right-2" @click="closeMap">×</button>
                <iframe
                    :src="`https://www.google.com/maps?q=${address.latitude},${address.longitude}&hl=pt-BR&z=16&output=embed`"
                    width="100%" height="400" style="border:0;" allowfullscreen loading="lazy" />
            </div>
        </div>

        <!-- Remover endereço -->
        <!-- <button type="button" class="w-full mt-4 btn btn-danger" @click="$emit('remove')">
            Remover Endereço
        </button> -->
    </div>
</template>

<style scoped>
.input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ccc;
    border-radius: 0.375rem;
}

.btn-primary {
    background-color: #3b82f6;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    font-weight: 600;
}

.btn-primary:disabled {
    background-color: #93c5fd;
    cursor: not-allowed;
}

.btn-danger {
    background-color: #ef4444;
    color: white;
    padding: 0.5rem;
    border: none;
    border-radius: 0.375rem;
}

.btn-danger:hover {
    background-color: #dc2626;
}

/* Spinner grande (CEP) */
.spinner {
    width: 20px;
    height: 20px;
    border: 3px solid #ccc;
    border-top: 3px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

/* Spinner pequeno (Mapa) */
.spinner-small {
    width: 16px;
    height: 16px;
    border: 2px solid #ccc;
    border-top: 2px solid #fff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}
</style>
