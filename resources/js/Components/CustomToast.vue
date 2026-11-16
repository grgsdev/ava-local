<script setup lang="ts">
import { useToast } from '@/Composables/useToast';
import { X, CheckCircle, AlertCircle, Info } from 'lucide-vue-next';

const { toasts, remove } = useToast();
</script>

<template>
    <div class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 w-full max-w-sm pointer-events-none">
        <TransitionGroup enter-active-class="transition duration-300 ease-out transform"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition duration-100 ease-in" leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-for="toast in toasts" :key="toast.id"
                class="flex items-center w-full p-4 overflow-hidden bg-white border rounded-lg shadow-lg pointer-events-auto dark:bg-slate-800 dark:border-slate-700"
                :class="{
                    'border-l-4 border-l-green-500': toast.type === 'success',
                    'border-l-4 border-l-red-500': toast.type === 'error',
                    'border-l-4 border-l-blue-500': toast.type === 'info',
                }">
                <div class="flex-shrink-0">
                    <CheckCircle v-if="toast.type === 'success'" class="w-5 h-5 text-green-500" />
                    <AlertCircle v-else-if="toast.type === 'error'" class="w-5 h-5 text-red-500" />
                    <Info v-else class="w-5 h-5 text-blue-500" />
                </div>

                <div class="flex-1 w-0 ml-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ toast.type === 'success' ? 'Sucesso' : (toast.type === 'error' ? 'Erro' : 'Info') }}
                    </p>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        {{ toast.message }}
                    </p>
                </div>

                <div class="flex flex-shrink-0 ml-4">
                    <button @click="remove(toast.id)"
                        class="inline-flex text-gray-400 bg-transparent rounded-md hover:text-gray-500 focus:outline-none">
                        <X class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>
