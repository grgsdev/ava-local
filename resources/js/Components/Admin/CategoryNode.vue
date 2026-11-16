<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';

import { PlusCircle, Edit, Trash2, ChevronRight } from 'lucide-vue-next';

const props = defineProps<{
    node: any;
    level: number;
}>();

const emit = defineEmits(['addChild', 'editNode', 'deleteNode']);
const isExpanded = ref(false);
function toggle() {
    isExpanded.value = !isExpanded.value;
}
</script>

<template>
    <div>
        <div class="flex items-center gap-2 p-2 rounded-md cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 group"
            @click.self="toggle">
            <div :style="{ 'padding-left': `${level * 24}px` }"></div>

            <div class="w-4">
                <ChevronRight v-if="node.children && node.children.length > 0"
                    class="w-4 h-4 transition-transform text-muted-foreground" :class="{ 'rotate-90': isExpanded }" />
            </div>

            <div class="flex-grow" @click="toggle">
                <div class="flex items-center gap-2">
                    <p class="font-semibold">{{ node.name }}</p>
                </div>
            </div>

            <div class="flex items-center gap-1 transition-opacity opacity-0 group-hover:opacity-100">
                <Button @click="emit('addChild', node)" variant="ghost" size="icon" class="h-7 w-7">
                    <PlusCircle class="w-4 h-4" />
                </Button>
                <Button @click="emit('editNode', node)" variant="ghost" size="icon" class="h-7 w-7">
                    <Edit class="w-4 h-4" />
                </Button>
                <Button @click="emit('deleteNode', node)" variant="ghost" size="icon" class="h-7 w-7 text-destructive">
                    <Trash2 class="w-4 h-4" />
                </Button>
            </div>
        </div>

        <div v-if="isExpanded && node.children && node.children.length > 0" class="pl-1 border-l-2 border-dashed ml-7">
            <CategoryNode v-for="childNode in node.children" :key="childNode.id" :node="childNode" :level="level + 1"
                @add-child="emit('addChild', $event)" @edit-node="emit('editNode', $event)"
                @delete-node="emit('deleteNode', $event)" />
        </div>
    </div>
</template>
