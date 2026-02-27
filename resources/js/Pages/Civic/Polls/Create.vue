<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import CivicLayout from '@/Layouts/CivicLayout.vue';

defineProps({
    regions: { type: Array, default: () => [] },
});

const form = reactive({
    title:                '',
    description:          '',
    region_id:            '',
    visibility:           'public',
    type:                 'standard',
    options:              ['', ''],
    starts_at:            '',
    ends_at:              '',
    allow_multiple_votes: false,
    verified_only:        false,
});

const errors  = ref({});
const saving  = ref(false);

const addOption = () => { if (form.options.length < 10) form.options.push(''); };
const removeOption = (i) => { if (form.options.length > 2) form.options.splice(i, 1); };

const submit = async () => {
    errors.value = {};
    saving.value = true;
    try {
        const payload = {
            ...form,
            region_id: form.region_id || undefined,
            starts_at: form.starts_at || undefined,
            ends_at:   form.ends_at   || undefined,
            options:   form.options.filter(o => o.trim()),
        };
        const res = await window.axios.post('/api/v1/polls', payload);
        router.visit(route('civic.polls.show', res.data.data.id));
    } catch (err) {
        errors.value = err.response?.data?.errors ?? { general: err.response?.data?.meta?.message ?? 'An error occurred.' };
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <CivicLayout title="Create Poll">
        <template #header>
            <h1 class="text-2xl font-bold text-gray-900">Create Poll</h1>
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
                        class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                        placeholder="What are you asking?" />
                    <p v-if="errors.title" class="text-red-500 text-xs mt-1">{{ errors.title[0] }}</p>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea v-model="form.description" rows="3"
                        class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Optional context for respondents…" />
                </div>

                <!-- Options -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Options <span class="text-red-500">*</span></label>
                    <div class="space-y-2">
                        <div v-for="(opt, i) in form.options" :key="i" class="flex items-center gap-2">
                            <span class="text-xs text-gray-400 w-5 text-right shrink-0">{{ i + 1 }}.</span>
                            <input v-model="form.options[i]" type="text"
                                class="flex-1 border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                                :placeholder="`Option ${i + 1}`" />
                            <button type="button" @click="removeOption(i)"
                                class="text-gray-400 hover:text-red-500 transition" :disabled="form.options.length <= 2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <button type="button" @click="addOption"
                        class="mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium">
                        + Add option
                    </button>
                    <p v-if="errors.options" class="text-red-500 text-xs mt-1">{{ errors.options[0] }}</p>
                </div>

                <!-- Two-column row: visibility + type -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Visibility</label>
                        <select v-model="form.visibility" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="public">Public</option>
                            <option value="regional">Regional</option>
                            <option value="private">Private</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Poll type</label>
                        <select v-model="form.type" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="standard">Standard</option>
                            <option value="ranked">Ranked</option>
                            <option value="weighted">Weighted</option>
                            <option value="premium">Premium</option>
                        </select>
                    </div>
                </div>

                <!-- Region -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                    <select v-model="form.region_id" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All regions (global)</option>
                        <option v-for="r in regions" :key="r.id" :value="r.id">{{ r.name }}</option>
                    </select>
                </div>

                <!-- Date range -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Opens at</label>
                        <input v-model="form.starts_at" type="datetime-local"
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Closes at</label>
                        <input v-model="form.ends_at" type="datetime-local"
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>

                <!-- Toggles -->
                <div class="flex flex-col gap-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input v-model="form.allow_multiple_votes" type="checkbox"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                        <span class="text-sm text-gray-700">Allow selecting multiple options</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input v-model="form.verified_only" type="checkbox"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                        <span class="text-sm text-gray-700">Verified accounts only</span>
                    </label>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3 pt-2">
                    <a :href="route('civic.polls.index')" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">Cancel</a>
                    <button type="submit" :disabled="saving"
                        class="px-6 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition disabled:opacity-60">
                        {{ saving ? 'Publishing…' : 'Publish Poll' }}
                    </button>
                </div>
            </form>
        </div>
    </CivicLayout>
</template>
