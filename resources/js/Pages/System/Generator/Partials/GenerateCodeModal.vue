<script setup lang="ts">
import { ref, watch } from 'vue'; // <-- Adicione 'watch'
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';

const props = defineProps<{
    modelName: string | null;
    open: boolean;
}>();

const emit = defineEmits(['update:open']);

const allCommands = [
    { id: 'grgsdev:migration', label: 'Migration' },
    { id: 'grgsdev:model', label: 'Model (Abstrato e Concreto)' },
    { id: 'grgsdev:formrequest', label: 'Form Requests (Store/Update)' },
    { id: 'grgsdev:resource', label: 'API Resource' },
    { id: 'grgsdev:controller', label: 'Controller (Abstrato e Concreto)' },
];

const form = useForm({
    commands: ['grgsdev:model', 'grgsdev:formrequest', 'grgsdev:resource', 'grgsdev:controller'],
});

// --- NOSSA CORREÇÃO ---
// 1. Criamos uma ref local, inicializada com os valores do form.
const selectedCommands = ref([...form.commands]);

// 2. Usamos um 'watch' para sincronizar a nossa ref local com o 'form' do Inertia.
//    Isso garante que a atualização seja sempre detetada.
watch(selectedCommands, (newValue) => {
    form.commands = newValue;
});
// --- FIM DA CORREÇÃO ---

const submit = () => {
    if (!props.modelName) return;
    form.post(route('system.generator.run', props.modelName), {
        onSuccess: () => {
            emit('update:open', false);
        }
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Gerar Código para: <span class="text-primary">{{ modelName }}</span></DialogTitle>
                <DialogDescription>
                    Selecione quais partes do CRUD você deseja gerar ou atualizar. O sistema executará os comandos
                    Artisan correspondentes.
                </DialogDescription>
            </DialogHeader>
            <div class="py-4">
                <p class="text-sm text-gray-600">
                    Você pode selecionar múltiplos comandos. O sistema irá gerar ou atualizar os arquivos
                    correspondentes
                    no diretório do modelo.
                </p>
            </div>
            <div class="py-4 space-y-4">
                <div v-for="command in allCommands" :key="command.id" class="flex items-center space-x-3">
                    <input type="checkbox" :id="command.id" v-model="selectedCommands" :value="command.id"
                        class="w-4 h-4 border-gray-300 rounded text-primary focus:ring-primary" />
                    <Label :for="command.id" class="font-medium">{{ command.label }}</Label>
                </div>
                <p v-if="form.errors.commands" class="text-sm text-red-500">{{ form.errors.commands }}</p>
            </div>

            <DialogFooter>
                <Button variant="ghost" @click="$emit('update:open', false)">Cancelar</Button>
                <Button @click="submit" :disabled="form.processing || form.commands.length === 0">
                    Gerar Código
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
