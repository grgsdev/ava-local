<script setup lang="ts">
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Search, BookOpen, Clock, BarChart, PlayCircle } from 'lucide-vue-next';

const props = defineProps<{
    courses: { data: any[], links: any[] };
    filters: any;
}>();

const search = ref(props.filters.filter?.title || '');
let timeout: any = null;

watch(search, (val) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('lms.index'), { 'filter[title]': val }, { preserveState: true, replace: true });
    }, 500);
});

// Helper para traduzir nível
const translateLevel = (level: string) => {
    const map: Record<string, string> = {
        'beginner': 'Iniciante',
        'intermediate': 'Intermediário',
        'advanced': 'Avançado'
    };
    return map[level] || level;
};
</script>

<template>

    <Head title="Meus Projetos" />

    <AuthenticatedLayout>
        <div class="relative py-20 overflow-hidden bg-slate-950">
            <div
                class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-20 mix-blend-soft-light">
            </div>
            <div class="container relative z-10 px-4 mx-auto text-center">
                <div
                    class="inline-flex items-center gap-2 px-3 py-1 mb-6 text-xs font-bold tracking-widest uppercase border rounded-full text-amber-400 border-amber-500/30 bg-amber-500/10">
                    AvaMovie Academy
                </div>
                <h1 class="mb-6 text-4xl font-black tracking-tight text-white md:text-6xl">
                    Evolua suas <span class="text-amber-500">Habilidades</span>
                </h1>
                <p class="max-w-2xl mx-auto mb-8 text-lg text-slate-400">
                    Domine o uso profissional de equipamentos e técnicas de manutenção com nossos especialistas.
                </p>

                <div class="relative max-w-md mx-auto">
                    <Search class="absolute left-3 top-3.5 h-5 w-5 text-slate-500" />
                    <Input v-model="search" placeholder="O que você quer aprender hoje?"
                        class="h-12 pl-10 text-white rounded-full bg-slate-900/50 border-slate-700 placeholder:text-slate-500 focus:ring-amber-500" />
                </div>
            </div>
        </div>

        <div class="container px-4 py-16 mx-auto">

            <div v-if="courses.data.length > 0" class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                <Link v-for="course in courses.data" :key="course.id" :href="route('lms.course.show', course.slug)"
                    class="flex flex-col overflow-hidden transition-all duration-300 bg-white border group dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl hover:shadow-xl hover:-translate-y-1">
                    <div class="relative overflow-hidden aspect-video bg-slate-100">
                        <img v-if="course.thumbnail_url" :src="course.thumbnail_url"
                            class="object-cover w-full h-full transition-transform duration-700 group-hover:scale-105" />
                        <div v-else class="flex items-center justify-center w-full h-full bg-slate-200 text-slate-400">
                            <BookOpen class="w-12 h-12 opacity-50" />
                        </div>

                        <div
                            class="absolute inset-0 flex items-center justify-center transition-opacity duration-300 opacity-0 bg-black/40 group-hover:opacity-100">
                            <div
                                class="flex items-center justify-center w-12 h-12 transition-transform transform scale-75 rounded-full shadow-lg bg-amber-500 text-slate-900 group-hover:scale-100">
                                <PlayCircle class="w-6 h-6 fill-current" />
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col flex-1 p-6">
                        <div class="flex items-center justify-between mb-3">
                            <Badge variant="outline"
                                class="text-xs font-normal text-slate-500 border-slate-300 dark:border-slate-700">
                                {{ translateLevel(course.level) }}
                            </Badge>
                            <div class="flex items-center gap-1 text-xs text-muted-foreground">
                                <Clock class="w-3 h-3" />
                                <span>{{ course.duration_minutes || 60 }} min</span>
                            </div>
                        </div>

                        <h3
                            class="mb-2 text-xl font-bold leading-tight transition-colors text-slate-900 dark:text-white group-hover:text-amber-600">
                            {{ course.title }}
                        </h3>

                        <p class="flex-1 mb-6 text-sm text-muted-foreground line-clamp-2">
                            {{ course.headline || 'Sem descrição curta.' }}
                        </p>

                        <div
                            class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-1 text-xs font-medium text-slate-500">
                                <BarChart class="w-3 h-3" />
                                {{ course.lessons_count }} Vídeos
                            </div>
                            <div class="text-lg font-bold text-slate-900 dark:text-white">
                                {{ parseFloat(course.price) > 0 ? `R$ ${course.price}` : 'Grátis' }}
                            </div>
                        </div>
                    </div>
                </Link>
            </div>

            <div v-else class="py-24 text-center border-2 border-dashed rounded-xl">
                <BookOpen class="w-12 h-12 mx-auto mb-4 text-slate-300" />
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Nenhum projeto encontrado</h3>
                <p class="text-muted-foreground">Tente buscar por outro termo.</p>
            </div>

            <div class="flex justify-center mt-12" v-if="courses.data.length > 0">
                <Pagination :links="courses.links" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
