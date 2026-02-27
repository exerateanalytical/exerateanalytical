<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    matrices: { type: Array, default: () => [] },
});

const activating = ref(null);
const error      = ref('');

const activate = async (id) => {
    if (!confirm('Activate this exposure matrix? All others will be deactivated.')) return;
    error.value    = '';
    activating.value = id;
    try {
        await window.axios.put(`/api/v1/exposure-matrix/${id}/activate`);
        router.reload();
    } catch (err) {
        error.value = err.response?.data?.meta?.message ?? 'Could not activate matrix.';
    } finally {
        activating.value = null;
    }
};

const formatDate = (iso) => iso ? new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '—';
</script>

<template>
    <AppLayout title="Exposure Matrix">
        <template #header>
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Exposure Matrix Management</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage contagion exposure matrices. Only one can be active at a time.</p>
                </div>
                <a href="/admin" class="text-sm text-gray-500 hover:text-gray-700">← Admin</a>
            </div>
        </template>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <p v-if="error" class="mb-4 text-red-600 text-sm bg-red-50 border border-red-200 rounded-lg px-4 py-3">{{ error }}</p>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div v-if="matrices.length" class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Version / ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="m in matrices" :key="m.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <span :class="m.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="text-xs font-semibold px-2 py-0.5 rounded-full">
                                        {{ m.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ m.id }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ formatDate(m.created_at) }}</td>
                                <td class="px-4 py-3">
                                    <button
                                        v-if="!m.is_active"
                                        @click="activate(m.id)"
                                        :disabled="activating === m.id"
                                        class="text-xs font-semibold px-3 py-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition disabled:opacity-60"
                                    >
                                        {{ activating === m.id ? 'Activating…' : 'Activate' }}
                                    </button>
                                    <span v-else class="text-xs text-gray-400">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="p-10 text-center text-gray-400">
                    <p class="font-medium">No exposure matrices found.</p>
                    <p class="text-sm mt-1">Use the API to create a new exposure matrix.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
