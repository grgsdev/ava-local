<script setup lang="ts">
import { computed } from "vue";
import { Move } from "lucide-vue-next";

const props = defineProps<{
    data: any;
    editable?: boolean;
}>();

const emit = defineEmits(["confirm", "dragStart", "resizeStart"]);

const boxStyle = computed(() => {
    const c = props.data?.coordinates;
    return {
        top: `${c?.top || 30}%`,
        left: `${c?.left || 35}%`,
        width: `${c?.width || 30}%`,
        height: `${c?.height || 18}%`,
        borderRadius: `${c?.radius ?? 12}px`
    };
});

const buttonStyle = computed(() => {
    const c = props.data?.button?.coordinates;
    return {
        top: `${c?.top || 50}%`,
        left: `${c?.left || 40}%`,
        width: `${c?.width || 20}%`,
        height: `${c?.height || 8}%`,
        borderRadius: `${c?.radius ?? 8}px`,
    };
});

const backgroundStyle = computed(() => {
    return {
        backgroundColor: props.data?.background_color || 'transparent',
        opacity: props.data?.background_opacity ?? 0.9,
    };
});

const textStyleBox = computed(() => {
    return {
        color: props.data?.text_color || '#000000',
        fontSize: `${props.data?.text_font_size || 16}px`,
        fontFamily: props.data?.text_font_family || 'Arial, sans-serif',
        textAlign: (props.data?.text_align || 'center') as any,
    };
});

const textStyleButton = computed(() => {
    return {
        color: props.data?.button?.text_color || '#000000',
        fontSize: `${props.data?.button?.text_font_size || 14}px`,
        fontFamily: props.data?.button?.text_font_family || 'Arial, sans-serif',
        textAlign: (props.data?.button?.text_align || 'center') as any,
    };
});

const getJustify = (align: string) => {
    if (align === 'left') return 'flex-start';
    if (align === 'right') return 'flex-end';
    return 'center';
};

const getAlign = (align: string) => {
    if (align === 'top') return 'flex-start';
    if (align === 'bottom') return 'flex-end';
    return 'center';
};
</script>

<template>
    <div class="absolute z-[55] overflow-hidden flex flex-col p-4 shadow-2xl group transition-all"
        :class="[editable ? 'cursor-move hover:ring-2 hover:ring-amber-400 border border-dashed border-amber-400' : 'pointer-events-auto']"
        :style="boxStyle" @mousedown.stop="emit('dragStart', $event, 'box')">

        <div class="absolute inset-0 pointer-events-none -z-10" :style="backgroundStyle"></div>

        <img v-if="props.data?.background_image" :src="props.data.background_image"
            class="absolute inset-0 z-0 object-cover w-full h-full pointer-events-none"
            :style="{ opacity: props.data?.background_opacity ?? 1 }" />

        <div v-if="editable"
            class="absolute z-30 p-1 text-white rounded opacity-0 pointer-events-none top-1 right-1 bg-black/50 group-hover:opacity-100">
            <Move class="w-4 h-4" />
        </div>

        <div class="relative z-20 flex w-full h-full pointer-events-none" :style="{
            justifyContent: getJustify(props.data?.text_align),
            alignItems: getAlign(props.data?.vertical_align)
        }">
            <span v-if="props.data?.text" class="font-bold whitespace-pre-wrap drop-shadow-md" :style="textStyleBox">
                {{ props.data.text }}
            </span>
        </div>

        <div v-if="editable"
            class="absolute bottom-0 right-0 z-30 hidden w-5 h-5 rounded-tl-lg bg-amber-500/80 cursor-nwse-resize group-hover:block"
            @mousedown.stop="emit('resizeStart', $event, 'box')">
        </div>
    </div>

    <div class="absolute z-[56] flex overflow-hidden transition-transform shadow group p-2" :class="[
        editable ? 'cursor-move hover:ring-2 hover:ring-blue-400 border border-dashed border-blue-400' : (props.data?.disabled ? 'opacity-50 cursor-not-allowed grayscale' : 'cursor-pointer hover:scale-105 active:scale-95')
    ]" :style="{
            ...buttonStyle,
            justifyContent: getJustify(props.data?.button?.text_align),
            alignItems: getAlign(props.data?.button?.vertical_align)
        }" @mousedown.stop="emit('dragStart', $event, 'button')"
        @click="!editable && !props.data?.disabled && emit('confirm')">

        <div class="absolute inset-0 pointer-events-none -z-10"
            :style="{ backgroundColor: props.data?.button?.background_color || '#f59e0b', opacity: props.data?.button?.background_opacity ?? 1 }">
        </div>

        <img v-if="props.data?.button_image" :src="props.data.button_image"
            class="absolute inset-0 z-0 object-cover w-full h-full pointer-events-none"
            :style="{ opacity: props.data?.button?.background_opacity ?? 1 }" />

        <div v-if="editable"
            class="absolute z-30 p-0.5 text-white rounded opacity-0 pointer-events-none top-1 left-1 bg-black/50 group-hover:opacity-100">
            <Move class="w-3 h-3" />
        </div>

        <span class="relative z-10 font-bold whitespace-pre-wrap select-none drop-shadow-sm" :style="textStyleButton">
            {{ props.data?.button?.text || 'Confirmar' }}
        </span>

        <div v-if="editable"
            class="absolute bottom-0 right-0 z-30 hidden w-4 h-4 rounded-tl-lg bg-blue-500/80 cursor-nwse-resize group-hover:block"
            @mousedown.stop="emit('resizeStart', $event, 'button')">
        </div>
    </div>
</template>
