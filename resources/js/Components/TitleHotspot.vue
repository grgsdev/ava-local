<script setup lang="ts">
import { computed } from 'vue'

type Coordinates = {
    top: number
    left: number
    width: number
    height: number
}

const props = defineProps<{
    data: {
        coordinates: Coordinates
        text?: string | null
        text_style?: {
            color?: string
            size?: number
            family?: string
            align?: string
            vertical?: string
        }
        background_color?: string | null
        background_opacity?: number | null
        background_image?: string | null
    }
    editable?: boolean
}>()

const emit = defineEmits<{
    (e: 'drag-start', ev: MouseEvent): void
    (e: 'resize-start', ev: MouseEvent): void
}>()

// 🎨 CAMADA DE FUNDO (Cor + Imagem + Opacidade)
const backgroundStyle = computed(() => {
    const opacity = typeof props.data.background_opacity === 'number' ? props.data.background_opacity : 1
    const color = props.data.background_color ?? 'transparent'

    return {
        backgroundColor: color,
        opacity,
        backgroundImage: props.data.background_image ? `url(${props.data.background_image})` : undefined,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        backgroundRepeat: 'no-repeat',
    }
})

// 📐 ALINHAMENTO DO TEXTO (Flexbox)
const alignmentStyle = computed(() => {
    const align = props.data.text_style?.align || 'center'
    const vertical = props.data.text_style?.vertical || 'center'

    const hAlign = align === 'left' ? 'flex-start' : (align === 'right' ? 'flex-end' : 'center')
    const vAlign = vertical === 'top' ? 'flex-start' : (vertical === 'bottom' ? 'flex-end' : 'center')

    return {
        justifyContent: hAlign,
        alignItems: vAlign
    }
})

// 🔤 ESTILO DA FONTE
const textStyle = computed(() => {
    return {
        color: props.data.text_style?.color || '#000000',
        fontSize: (props.data.text_style?.size || 20) + 'px',
        fontFamily: props.data.text_style?.family || 'Arial, sans-serif'
    }
})

const onMouseDown = (e: MouseEvent) => {
    const target = e.target as HTMLElement
    if (target.closest('[data-resize-handle]')) return
    e.stopPropagation()
    e.preventDefault()
    emit('drag-start', e)
}
</script>

<template>
    <div class="absolute z-40 overflow-hidden rounded-md cursor-move group" :style="{
        top: data.coordinates.top + '%',
        left: data.coordinates.left + '%',
        width: data.coordinates.width + '%',
        height: data.coordinates.height + '%'
    }" @mousedown="onMouseDown">

        <div class="absolute inset-0 pointer-events-none" :style="backgroundStyle"></div>

        <div class="absolute inset-0 z-20 flex p-2 pointer-events-none" :style="alignmentStyle">
            <span v-if="data.text" class="font-bold text-center drop-shadow-md" :style="textStyle">
                {{ data.text }}
            </span>
        </div>

        <div v-if="editable" data-resize-handle
            class="absolute bottom-0 right-0 z-30 hidden w-4 h-4 bg-amber-500 cursor-nwse-resize group-hover:block"
            @mousedown.stop.prevent="$emit('resize-start', $event)" />
    </div>
</template>
