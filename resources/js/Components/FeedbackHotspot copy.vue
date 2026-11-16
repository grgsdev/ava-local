<script setup lang="ts">
import { computed } from "vue";
import { Move } from "lucide-vue-next";

const props = defineProps<{
    data: any;
    editable?: boolean;
}>();

const emit = defineEmits(["dragStart", "resizeStart"]);

const boxStyle = computed(() => {
    const c = props.data?.coordinates;
    return {
        top: `${c?.top || 50}%`,
        left: `${c?.left || 50}%`,
        width: `${c?.width || 40}%`,
        height: `${c?.height || 20}%`,
        borderRadius: `${c?.radius ?? 12}px`
    };
});

const backgroundStyle = computed(() => {
    return {
        backgroundColor: props.data?.background_color || 'transparent',
        opacity: props.data?.background_opacity ?? 1,
    };
});

const textStyle = computed(() => {
    return {
        color: props.data?.text_color || '#000000',
        fontSize: `${props.data?.text_font_size || 20}px`,
        fontFamily: props.data?.text_font_family || 'Arial, sans-serif',
        textAlign: (props.data?.text_align || 'center') as any,
    };
});

const textContainerClass = computed(() => {
    const align = props.data?.vertical_align || 'center';
    if (align === 'top') return 'justify-start';
    if (align === 'bottom') return 'justify-end';
    return 'justify-center';
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
    <div class="absolute z-[60] overflow-hidden flex flex-col shadow-2xl group transition-all"
        :class="[editable ? 'cursor-move hover:ring-2 hover:ring-blue-400 border border-dashed border-slate-400' : '']"
        :style="boxStyle" @mousedown.stop="onDragStart">

        <div class="absolute inset-0 pointer-events-none -z-10" :style="backgroundStyle"></div>

        <img v-if="props.data?.background_image" :src="props.data.background_image"
            class="absolute inset-0 z-0 object-cover w-full h-full pointer-events-none"
            :style="{ opacity: props.data?.background_opacity ?? 1 }" />

        <div v-if="editable"
            class="absolute z-30 p-1 text-white rounded opacity-0 pointer-events-none top-1 right-1 bg-black/50 group-hover:opacity-100">
            <Move class="w-4 h-4" />
        </div>

        <div class="relative z-20 flex flex-col w-full h-full p-4 overflow-hidden pointer-events-none"
            :class="textContainerClass">
            <span class="font-bold whitespace-pre-wrap drop-shadow-md" :style="textStyle">
                {{ props.data?.text || 'Exemplo de Mensagem' }}
            </span>
        </div>

        <div v-if="editable"
            class="absolute bottom-0 right-0 z-30 hidden w-5 h-5 rounded-tl-lg bg-blue-500/80 cursor-nwse-resize group-hover:block"
            @mousedown.stop="onResizeStart">
        </div>
    </div>
</template>
