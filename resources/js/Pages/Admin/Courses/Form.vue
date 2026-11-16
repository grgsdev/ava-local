<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
//import ImageUpload from '@/Components/ImageUpload.vue';
import NativeSwitch from '@/Components/NativeSwitch.vue';
//import RichTextEditor from '@/Components/RichTextEditor.vue';
//import CategoryCheckboxTree from '@/Components/Admin/CategoryCheckboxTree.vue'; // <--- Importar

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const props = defineProps<{
    course?: any;
    categories: any[]; // <--- Receber categorias
    selectedCategories?: number[]; // <--- Receber selecionadas
}>();

const isEditing = !!props.course;

const form = useForm({
    _method: isEditing ? 'put' : 'post',
    title: props.course?.title || '',
    headline: props.course?.headline || '',
    description: props.course?.description || '',
    price: props.course?.price || '0.00',
    level: props.course?.level || 'beginner',
    is_published: props.course?.is_published || false,
    thumbnail: null as File | null,
    categories: props.selectedCategories || [], // <--- Inicializar array
});

const submit = () => {
    if (isEditing) {
        form.post(route('admin.courses.update', props.course.id), { forceFormData: true });
    } else {
        form.post(route('admin.courses.store'), { forceFormData: true });
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight">
                {{ isEditing ? 'Editar Projeto' : 'Novo Projeto' }}
            </h2>
        </template>

        <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <div class="space-y-6 lg:col-span-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Detalhes da Categoria</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-2">
                            <Label>Título</Label>
                            <Input v-model="form.title" placeholder="Ex: Curso de NR1" required />
                            <span v-if="form.errors.title" class="text-sm text-red-500">{{ form.errors.title }}</span>
                        </div>



                        <div class="grid gap-2">
                            <Label>Descrição Completa</Label>
                            <!-- <RichTextEditor v-model="form.description" placeholder="O que o aluno vai aprender?" /> -->
                            <Textarea v-model="form.description" placeholder="O que o aluno vai aprender?" />

                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Configurações</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between pb-4 border-b">
                            <Label>Publicado?</Label>
                            <NativeSwitch v-model="form.is_published" />
                        </div>

                        <!-- <div class="grid gap-2">
                            <Label>Preço (R$)</Label>
                            <Input type="number" step="0.01" v-model="form.price" />
                            <p class="text-[10px] text-muted-foreground">Coloque 0 para Grátis.</p>
                        </div> -->

                        <!-- <div class="grid gap-2">
                            <Label>Nível</Label>
                            <Select v-model="form.level">
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecione" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="beginner">Iniciante</SelectItem>
                                    <SelectItem value="intermediate">Intermediário</SelectItem>
                                    <SelectItem value="advanced">Avançado</SelectItem>
                                </SelectContent>
                            </Select>
                        </div> -->
                    </CardContent>
                </Card>

                <!-- <Card>
                    <CardHeader>
                        <CardTitle>Categorias</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            class="p-4 space-y-1 overflow-y-auto border rounded-md max-h-[300px] bg-white dark:bg-slate-950">

                            <CategoryCheckboxTree v-for="cat in categories" :key="cat.id" :category="cat"
                                v-model="form.categories" />

                            <div v-if="categories.length === 0" class="py-2 text-sm text-center text-muted-foreground">
                                Nenhuma categoria de curso.
                            </div>
                        </div>
                    </CardContent>
                </Card> -->

                <!-- <Card>
                    <CardHeader>
                        <CardTitle>Capa do Curso</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ImageUpload v-model="form.thumbnail" :preview-url="props.course?.thumbnail_url"
                            label="Thumbnail (16:9)" />
                    </CardContent>
                </Card> -->

                <Button type="submit" class="w-full" :disabled="form.processing">
                    {{ isEditing ? 'Salvar Alterações' : 'Criar' }}
                </Button>
            </div>

        </form>
    </AuthenticatedLayout>
</template>
