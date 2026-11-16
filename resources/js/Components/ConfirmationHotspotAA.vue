<script setup lang="ts">
import { computed } from "vue";
import { Move } from "lucide-vue-next";
import { Button } from "@/components/ui/button";

const props = defineProps<{
    data: any;
    editable?: boolean;
}>();

const emit = defineEmits(["confirm", "dragStart", "resizeStart"]);

const boxStyle = computed(() => {
    const c = props.data?.coordinates;
    return {
        top: `${c?.top || 75}%`,
        left: `${c?.left || 50}%`,
        width: `${c?.width || 30}%`,
        height: `${c?.height || 18}%`,
        borderRadius: `${c?.radius ?? 12}px`,
        transform: 'translate(-50%, -50%)'
    };
});

const backgroundStyle = computed(() => {
    return {
        backgroundColor: props.data?.background_color || 'transparent',
        opacity: props.data?.background_opacity ?? 0.9,
    };
});

const textStyle = computed(() => {
    return {
        color: props.data?.text_color || '#000000',
        fontSize: `${props.data?.text_font_size || 16}px`,
        fontFamily: props.data?.text_font_family || 'Arial, sans-serif',
        textAlign: (props.data?.text_align || 'center') as any,
    };
});

const onDragStart = (e: MouseEvent) => {
    if (props.editable) emit("dragStart", e);
};

const onResizeStart = (e: MouseEvent) => {
    if (props.editable) {
        e.stopPropagation();
        e.preventDefault();
        emit("resizeStart", e);
    }
};

</script>

<template>
    <div class="absolute z-[55] overflow-hidden flex flex-col items-center justify-center p-4 shadow-2xl group transition-all"
        :class="[editable ? 'cursor-move hover:ring-2 hover:ring-amber-400 border border-dashed border-amber-400' : 'pointer-events-auto']"
        :style="boxStyle" @mousedown.stop="onDragStart">

        <div class="absolute inset-0 pointer-events-none -z-10" :style="backgroundStyle"></div>

        <img v-if="props.data?.background_image" :src="props.data.background_image"
            class="absolute inset-0 z-0 object-cover w-full h-full pointer-events-none"
            :style="{ opacity: props.data?.background_opacity ?? 1 }" />

        <div v-if="editable"
            class="absolute z-30 p-1 text-white rounded opacity-0 pointer-events-none top-1 right-1 bg-black/50 group-hover:opacity-100">
            <Move class="w-4 h-4" />
        </div>

        <div class="relative z-20 flex flex-col items-center justify-center w-full h-full gap-3">
            <span v-if="props.data?.text" class="font-bold drop-shadow-md" :style="textStyle">
                {{ props.data.text }}
            </span>

            <div class="relative flex items-center justify-center overflow-hidden transition-transform shadow hover:scale-105 active:scale-95"
                :class="props.data?.disabled ? 'opacity-50 cursor-not-allowed grayscale' : 'cursor-pointer'" :style="{
                    width: `${props.data?.button?.width || 140}px`,
                    height: `${props.data?.button?.height || 42}px`,
                    backgroundColor: props.data?.button?.background_color || '#f59e0b',
                    borderRadius: `${props.data?.button?.radius ?? 8}px`
                }" @click="!props.data?.disabled && emit('confirm')">

                <img v-if="props.data?.button_image" :src="props.data.button_image"
                    class="absolute inset-0 z-0 object-cover w-full h-full pointer-events-none"
                    :style="{ opacity: props.data?.button?.background_opacity ?? 1 }" />

                <div v-else class="absolute inset-0 pointer-events-none"
                    :style="{ backgroundColor: props.data?.button?.background_color || '#f59e0b', opacity: props.data?.button?.background_opacity ?? 1 }">
                </div>

                <span class="relative z-10 font-bold select-none drop-shadow-sm" :style="{
                    color: props.data?.button?.text_color || '#000000',
                    fontSize: `${props.data?.button?.text_font_size || 14}px`,
                    fontFamily: props.data?.button?.text_font_family || 'Arial, sans-serif'
                }">
                    {{ props.data?.button?.text || 'Confirmar' }}
                </span>
            </div>
        </div>

        <div v-if="editable"
            class="absolute bottom-0 right-0 z-30 hidden w-5 h-5 rounded-tl-lg bg-amber-500/80 cursor-nwse-resize group-hover:block"
            @mousedown.stop="onResizeStart">
        </div>
    </div>
</template>
