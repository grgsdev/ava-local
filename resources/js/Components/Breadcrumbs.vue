<script setup lang="ts">
import { usePage, Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';

interface Crumb {
    label: string;
    url: string | null;
}

const breadcrumbs = usePage().props.breadcrumbs as Crumb[];
</script>

<template>
    <nav v-if="breadcrumbs && breadcrumbs.length > 0"
        class="flex items-center space-x-2 text-sm font-medium text-muted-foreground">
        <template v-for="(crumb, index) in breadcrumbs" :key="crumb.label">
            <span v-if="index > 0" class="text-muted-foreground">
                <ChevronRight class="w-4 h-4" />
            </span>
            <Link v-if="crumb.url" :href="crumb.url" class="transition-colors hover:text-primary">
            {{ $t(crumb.label) }}
            </Link>
            <span v-else class="font-semibold text-foreground">
                {{ $t(crumb.label) }}
            </span>
        </template>
    </nav>
</template>
