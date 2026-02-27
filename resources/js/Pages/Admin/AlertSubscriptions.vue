<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    subscriptions: { type: Array, default: () => [] },
});

const deleting = ref(null);
const error    = ref('');

const deleteSub = async (id) => {
    if (!confirm('Delete this alert subscription?')) return;
    error.value = '';
    deleting.value = id;
    try {
        await window.axios.delete(`/api/v1/alert-subscriptions/${id}`);
        router.reload();
    } catch (err) {
        error.value = err.response?.data?.meta?.message ?? 'Could not delete subscription.';
    } finally {
        deleting.value = null;
    }
};

const severityClass = (s) => ({
    critical: 'bg-red-100 text-red-700',
    high:     'bg-orange-100 text-orange-700',
    moderate: 'bg-yellow-100 text-yellow-700',
    low:      'bg-blue-100 text-blue-700',
}[s] ?? 'bg-gray-100 text-gray-500');

const formatDate = (iso) => iso ? new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '—';
</script>

<template>
    <AppLayout title="Alert Subscriptions">
        <template #header>
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Alert Subscriptions</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage risk alert notification subscriptions.</p>
                </div>
                <a href="/admin" class="text-sm text-gray-500 hover:text-gray-700">← Admin</a>
            </div>
        </template>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <p v-if="error" class="mb-4 text-red-600 text-sm bg-red-50 border border-red-200 rounded-lg px-4 py-3">{{ error }}</p>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div v-if="subscriptions.length" class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Country</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Alert Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Severity</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Channel</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="sub in subscriptions" :key="sub.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ sub.country?.name ?? '—' }}
                                    <span class="ml-1 text-xs text-gray-400 font-mono">{{ sub.country?.iso_code ?? '' }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ sub.alert_type ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full', severityClass(sub.severity)]">
                                        {{ sub.severity ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ sub.channel ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ formatDate(sub.created_at) }}</td>
                                <td class="px-4 py-3">
                                    <button
                                        @click="deleteSub(sub.id)"
                                        :disabled="deleting === sub.id"
                                        class="text-xs font-semibold text-red-600 hover:text-red-800 transition disabled:opacity-60"
                                    >
                                        {{ deleting === sub.id ? 'Deleting…' : 'Delete' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="p-10 text-center text-gray-400">
                    <p class="font-medium">No alert subscriptions configured.</p>
                    <p class="text-sm mt-1">Use the API to create alert subscriptions.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
