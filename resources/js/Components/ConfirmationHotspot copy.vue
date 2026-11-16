<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
    data: {
        text?: string;
        text_color?: string;
        text_font_size?: number;
        text_font_family?: string;
        text_align?: string;
        background_color?: string;
        background_opacity?: number;
        background_image?: string | null;
        button_image?: string | null; // 👈 Nova imagem do botão
        coordinates: {
            top: number;
            left: number;
            width: number;
            height: number;
        };
        button?: {
            text: string;
            text_color: string;
            text_font_size?: number;
            text_font_family?: string;
            background_color: string;
            background_opacity?: number; // 👈 Opacidade própria do botão
            width: number;
            height: number;
        };
    };
    editable?: boolean;
    disabled?: boolean;
}>();

const shouldRender = computed(() => {
    const hasText = !!props.data.text?.trim();
    const hasImage = !!props.data.background_image;
    const hasButton = !!props.data.button;
    return hasText || hasImage || hasButton;
});

// Deixamos APENAS a posição e o tamanho aqui. Tiramos cor e imagem para não afetar os filhos.
const containerStyle = computed(() => ({
    top: props.data.coordinates.top + "%",
    left: props.data.coordinates.left + "%",
    width: props.data.coordinates.width + "%",
    height: props.data.coordinates.height + "%",
}));

const emit = defineEmits<{
    (e: "drag-start", ev: MouseEvent): void;
    (e: "resize-start", ev: MouseEvent): void;
    (e: "confirm"): void;
}>();
</script>

<template>
    <div v-if="shouldRender" class="absolute z-[55] rounded-lg shadow-lg"
        :class="{ 'cursor-move border-2 border-dashed border-amber-400': editable }" :style="containerStyle"
        @mousedown.stop="editable && emit('drag-start', $event)">

        <div class="absolute inset-0 rounded-lg pointer-events-none" :style="{
            backgroundColor: data.background_color || 'transparent',
            opacity: data.background_opacity ?? 1
        }">
        </div>
        <img v-if="data.background_image" :src="data.background_image"
            class="absolute inset-0 object-cover w-full h-full rounded-lg pointer-events-none"
            :style="{ opacity: data.background_opacity ?? 1 }" />

        <div
            class="relative z-10 flex flex-col items-center justify-center w-full h-full gap-3 p-4 pointer-events-auto">

            <div v-if="data.text" class="w-full font-bold break-words drop-shadow-sm" :style="{
                color: data.text_color || '#000000',
                fontSize: (data.text_font_size || 16) + 'px',
                fontFamily: data.text_font_family || 'Arial, sans-serif',
                textAlign: (data.text_align as any) || 'center'
            }">
                {{ data.text }}
            </div>

            <button v-if="data.button"
                class="relative flex items-center justify-center overflow-hidden font-bold transition-transform rounded-full shadow-md hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                :style="{
                    width: data.button.width + 'px',
                    height: data.button.height + 'px',
                }" :disabled="disabled" @click.stop="!disabled && emit('confirm')">

                <div class="absolute inset-0 pointer-events-none" :style="{
                    backgroundColor: data.button.background_color || '#f59e0b',
                    opacity: data.button.background_opacity ?? 1
                }">
                </div>

                <img v-if="data.button_image" :src="data.button_image"
                    class="absolute inset-0 object-cover w-full h-full pointer-events-none"
                    :style="{ opacity: data.button.background_opacity ?? 1 }" />

                <span class="relative z-10 drop-shadow-sm" :style="{
                    color: data.button.text_color || '#000000',
                    fontSize: (data.button.text_font_size || 14) + 'px',
                    fontFamily: data.button.text_font_family || 'Arial, sans-serif',
                }">
                    {{ data.button.text || "Confirmar" }}
                </span>
            </button>
        </div>

        <div v-if="editable" class="absolute bottom-0 right-0 z-20 w-4 h-4 rounded-tl bg-amber-500 cursor-nwse-resize"
            @mousedown.stop="emit('resize-start', $event)" />
    </div>
</template>