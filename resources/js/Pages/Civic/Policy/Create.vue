<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import CivicLayout from '@/Layouts/CivicLayout.vue';

defineProps({ regions: { type: Array, default: () => [] } });

const form = reactive({
    title:     '',
    abstract:  '',
    full_text: '',
    region_id: '',
});

const errors  = ref({});
const saving  = ref(false);

const submit = async () => {
    errors.value = {};
    saving.value = true;
    try {
        const payload = { ...form, region_id: form.region_id || undefined };
        const res = await window.axios.post('/api/v1/policies', payload);
        router.visit(route('civic.policies.show', res.data.data.id));
    } catch (err) {
        errors.value = err.response?.data?.errors ?? { general: err.response?.data?.meta?.message ?? 'An error occurred.' };
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <CivicLayout title="Submit Policy Proposal">
        <template #header>
            <h1 class="text-2xl font-bold text-gray-900">Submit Policy Proposal</h1>
        </template>

        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <form @submit.prevent="submit" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-6">

                <div v-if="errors.general" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                    {{ errors.general }}
                </div>

                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input v-model="form.title" type="text" maxlength="200" required
                        class="w-full border-gray-300 rounded-lg text-sm focus:ring-violet-500 focus:border-violet-500"
                        placeholder="Clear, descriptive policy title" />
                    <p v-if="errors.title" class="text-red-500 text-xs mt-1">{{ errors.title[0] }}</p>
                </div>

                <!-- Abstract -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Abstract <span class="text-red-500">*</span></label>
                    <textarea v-model="form.abstract" rows="3" required
                        class="w-full border-gray-300 rounded-lg text-sm focus:ring-violet-500 focus:border-violet-500"
                        placeholder="Brief overview of the proposal and its objectives" />
                    <p v-if="errors.abstract" class="text-red-500 text-xs mt-1">{{ errors.abstract[0] }}</p>
                </div>

                <!-- Full text -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full proposal text <span class="text-red-500">*</span></label>
                    <textarea v-model="form.full_text" rows="12" required
                        class="w-full border-gray-300 rounded-lg text-sm focus:ring-violet-500 focus:border-violet-500"
                        placeholder="The complete policy proposal — background, objectives, implementation plan, and expected outcomes…" />
                    <p v-if="errors.full_text" class="text-red-500 text-xs mt-1">{{ errors.full_text[0] }}</p>
                </div>

                <!-- Region -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                    <select v-model="form.region_id" class="w-full border-gray-300 rounded-lg text-sm focus:ring-violet-500 focus:border-violet-500">
                        <option value="">All regions (federation-wide)</option>
                        <option v-for="r in regions" :key="r.id" :value="r.id">{{ r.name }}</option>
                    </select>
                </div>

                <!-- Info note -->
                <div class="bg-violet-50 border border-violet-100 rounded-lg px-4 py-3 text-xs text-violet-700">
                    Your proposal will be created in <strong>Draft</strong> stage. You can then advance it to
                    Public Consultation when ready for community review.
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a :href="route('civic.policies.index')" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">Cancel</a>
                    <button type="submit" :disabled="saving"
                        class="px-6 py-2 bg-violet-600 text-white text-sm font-semibold rounded-lg hover:bg-violet-700 transition disabled:opacity-60">
                        {{ saving ? 'Submitting…' : 'Submit Proposal' }}
                    </button>
                </div>
            </form>
        </div>
    </CivicLayout>
</template>
