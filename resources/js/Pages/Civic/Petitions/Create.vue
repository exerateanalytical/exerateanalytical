<script setup>
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import CivicLayout from '@/Layouts/CivicLayout.vue';

defineProps({ regions: { type: Array, default: () => [] } });

const form = reactive({
    title:          '',
    summary:        '',
    body:           '',
    region_id:      '',
    signature_goal: 100,
    deadline:       '',
});

const errors  = ref({});
const saving  = ref(false);

const BODY_MAX = 20000;
const bodyCharsLeft = computed(() => BODY_MAX - (form.body?.length ?? 0));

const submit = async () => {
    errors.value = {};
    saving.value = true;
    try {
        const payload = {
            ...form,
            region_id: form.region_id || undefined,
            deadline:  form.deadline  || undefined,
        };
        const res = await window.axios.post('/api/v1/petitions', payload);
        router.visit(route('civic.petitions.show', res.data.data.id));
    } catch (err) {
        errors.value = err.response?.data?.errors ?? { general: err.response?.data?.meta?.message ?? 'An error occurred.' };
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <CivicLayout title="Create Petition">
        <template #header>
            <h1 class="text-2xl font-bold text-gray-900">Create Petition</h1>
        </template>

        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <form @submit.prevent="submit" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-6">

                <div v-if="errors.general" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                    {{ errors.general }}
                </div>

                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input v-model="form.title" type="text" maxlength="180" required
                        class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500"
                        placeholder="A clear, compelling title" />
                    <p v-if="errors.title" class="text-red-500 text-xs mt-1">{{ errors.title[0] }}</p>
                </div>

                <!-- Summary -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Summary <span class="text-red-500">*</span></label>
                    <textarea v-model="form.summary" rows="2" required
                        class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500"
                        placeholder="One or two sentences explaining the petition" />
                    <p v-if="errors.summary" class="text-red-500 text-xs mt-1">{{ errors.summary[0] }}</p>
                </div>

                <!-- Body -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full text <span class="text-red-500">*</span></label>
                    <textarea v-model="form.body" rows="8" required :maxlength="BODY_MAX"
                        class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500"
                        placeholder="Explain the issue, the ask, and the expected impact in full…" />
                    <div class="flex justify-between mt-1">
                        <p v-if="errors.body" class="text-red-500 text-xs">{{ errors.body[0] }}</p>
                        <p :class="['text-xs ml-auto', bodyCharsLeft < 500 ? 'text-amber-500' : 'text-gray-400']">
                            {{ bodyCharsLeft.toLocaleString() }} characters remaining
                        </p>
                    </div>
                </div>

                <!-- Signature goal + region + deadline -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Signature goal</label>
                        <input v-model.number="form.signature_goal" type="number" min="1" max="10000000"
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500" />
                        <p v-if="errors.signature_goal" class="text-red-500 text-xs mt-1">{{ errors.signature_goal[0] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deadline</label>
                        <input v-model="form.deadline" type="datetime-local"
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                    <select v-model="form.region_id" class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">All regions (global)</option>
                        <option v-for="r in regions" :key="r.id" :value="r.id">{{ r.name }}</option>
                    </select>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a :href="route('civic.petitions.index')" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">Cancel</a>
                    <button type="submit" :disabled="saving"
                        class="px-6 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 transition disabled:opacity-60">
                        {{ saving ? 'Publishing…' : 'Publish Petition' }}
                    </button>
                </div>
            </form>
        </div>
    </CivicLayout>
</template>
