<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    regions:    { type: Array, default: () => [] },
    recentRuns: { type: Array, default: () => [] },
});

const form = reactive({
    region_id:        '',
    shock_magnitude:  25,
    contagion_factor: 0.3,
    iterations:       10,
});

const result   = ref(null);
const running  = ref(false);
const error    = ref('');

const run = async () => {
    if (!form.region_id) { error.value = 'Please select a region.'; return; }
    error.value = '';
    running.value = true;
    result.value  = null;
    try {
        const res = await window.axios.post('/api/v1/internal/risk/contagion', form);
        result.value = res.data;
        router.reload({ only: ['recentRuns'] });
    } catch (err) {
        error.value = err.response?.data?.meta?.message ?? err.response?.data?.message ?? 'Simulation failed.';
    } finally {
        running.value = false;
    }
};

const formatDate = (iso) => iso ? new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';
</script>

<template>
    <AppLayout title="Risk Contagion Simulator">
        <template #header>
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Risk Contagion Simulator</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Model how risk shocks propagate across federation regions. SuperAdmin only.</p>
                </div>
                <a href="/admin" class="text-sm text-gray-500 hover:text-gray-700">← Admin</a>
            </div>
        </template>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- Simulation Form -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Run Simulation</h2>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Origin Region <span class="text-red-500">*</span></label>
                        <select v-model="form.region_id" class="w-full border-gray-300 rounded-lg text-sm focus:ring-red-500 focus:border-red-500">
                            <option value="">Select a federation region</option>
                            <option v-for="r in regions" :key="r.id" :value="r.id">{{ r.name }} ({{ r.code }})</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Shock Magnitude (0–100)</label>
                        <input v-model.number="form.shock_magnitude" type="number" min="1" max="100"
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-red-500 focus:border-red-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contagion Factor (0–1)</label>
                        <input v-model.number="form.contagion_factor" type="number" step="0.05" min="0" max="1"
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-red-500 focus:border-red-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Iterations</label>
                        <input v-model.number="form.iterations" type="number" min="1" max="100"
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-red-500 focus:border-red-500" />
                    </div>
                </div>

                <div class="bg-red-50 border border-red-100 rounded-lg px-4 py-3 text-xs text-red-700 mt-4">
                    This simulation requires SuperAdmin credentials and uses the internal risk contagion engine. Results are not saved automatically.
                </div>

                <p v-if="error" class="mt-3 text-red-600 text-sm">{{ error }}</p>

                <div class="flex justify-end mt-4">
                    <button @click="run" :disabled="running"
                        class="px-6 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition disabled:opacity-60">
                        {{ running ? 'Running simulation…' : 'Run Contagion Simulation' }}
                    </button>
                </div>
            </div>

            <!-- Result -->
            <div v-if="result" class="bg-white rounded-xl border border-green-200 shadow-sm p-6">
                <h2 class="font-semibold text-green-800 mb-3">Simulation Result</h2>
                <pre class="text-xs bg-gray-50 rounded-lg p-4 overflow-auto max-h-96 border border-gray-200">{{ JSON.stringify(result, null, 2) }}</pre>
            </div>

            <!-- Recent Runs -->
            <div v-if="recentRuns.length" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Recent Runs</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Run ID</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Ran At</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="run in recentRuns" :key="run.id" class="hover:bg-gray-50">
                                <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ run.id }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ formatDate(run.created_at) }}</td>
                                <td class="px-4 py-2">
                                    <span :class="run.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="text-xs font-semibold px-2 py-0.5 rounded-full">
                                        {{ run.status ?? 'unknown' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
