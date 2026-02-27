<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    stats:           { type: Object, default: () => ({}) },
    recentAuditLogs: { type: Array,  default: () => [] },
});

const formatDate = (iso) => {
    if (!iso) return '';
    return new Date(iso).toLocaleString(undefined, {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

// Strip PHP namespace from auditable_type, e.g. "App\Models\Country" -> "Country"
const stripNamespace = (type) => {
    if (!type) return '—';
    return type.split('\\').pop();
};

const statCards = [
    // Row 1
    { key: 'countries',    label: 'Countries',    icon: '&#127758;', iconBg: 'bg-blue-50',   iconColor: 'text-blue-600',   href: '/admin/countries' },
    { key: 'regions',      label: 'Regions',      icon: '&#128506;', iconBg: 'bg-indigo-50', iconColor: 'text-indigo-600', href: null },
    { key: 'institutions', label: 'Institutions', icon: '&#127963;', iconBg: 'bg-violet-50', iconColor: 'text-violet-600', href: null },
    { key: 'users',        label: 'Users',        icon: '&#128100;', iconBg: 'bg-cyan-50',   iconColor: 'text-cyan-600',   href: null },
    // Row 2
    { key: 'polls',        label: 'Polls',        icon: '&#128202;', iconBg: 'bg-amber-50',  iconColor: 'text-amber-600',  href: null },
    { key: 'petitions',    label: 'Petitions',    icon: '&#9997;&#65039;',  iconBg: 'bg-emerald-50', iconColor: 'text-emerald-600', href: null },
    { key: 'policies',     label: 'Policies',     icon: '&#128196;', iconBg: 'bg-teal-50',   iconColor: 'text-teal-600',   href: null },
    { key: 'activeAlerts', label: 'Active Alerts', icon: '&#9888;&#65039;',  iconBg: 'bg-red-50',    iconColor: 'text-red-600',    href: null },
];

const quickActions = [
    { label: 'Manage Countries',        href: '/admin/countries',           icon: '&#127758;' },
    { label: 'Manage Exposure Matrix',  href: '/admin/exposure-matrix',     icon: '&#128200;' },
    { label: 'Alert Subscriptions',     href: '/admin/alert-subscriptions', icon: '&#128276;' },
    { label: 'Risk Contagion',          href: '/admin/risk-contagion',      icon: '&#128308;' },
];
</script>

<template>
    <AppLayout title="Admin Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Admin Dashboard
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

                <!-- Stats grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <component
                        :is="card.href ? 'a' : 'div'"
                        v-for="card in statCards"
                        :key="card.key"
                        :href="card.href ?? undefined"
                        :class="[
                            'bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex items-center gap-4',
                            card.href ? 'hover:border-indigo-200 hover:shadow-md transition group cursor-pointer' : '',
                        ]"
                    >
                        <div :class="['w-12 h-12 rounded-xl flex items-center justify-center text-xl shrink-0', card.iconBg]">
                            <span :class="card.iconColor" v-html="card.icon"></span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide truncate">{{ card.label }}</p>
                            <p class="text-2xl font-bold text-gray-900 tabular-nums">
                                {{ stats[card.key] ?? 0 }}
                            </p>
                        </div>
                    </component>
                </div>

                <!-- Quick actions -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Quick Actions</h3>
                    <div class="flex flex-wrap gap-3">
                        <a
                            v-for="action in quickActions"
                            :key="action.href"
                            :href="action.href"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 transition"
                        >
                            <span v-html="action.icon"></span>
                            {{ action.label }} &rarr;
                        </a>
                    </div>
                </div>

                <!-- Recent Audit Logs -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">Recent Audit Log</h3>
                        <span class="text-xs text-gray-400">Last {{ recentAuditLogs.length }} entries</span>
                    </div>

                    <div v-if="recentAuditLogs.length" class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    <th class="px-4 py-3 text-left">Event</th>
                                    <th class="px-4 py-3 text-left">Model</th>
                                    <th class="px-4 py-3 text-left">ID</th>
                                    <th class="px-4 py-3 text-left">User</th>
                                    <th class="px-4 py-3 text-right">When</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr
                                    v-for="log in recentAuditLogs"
                                    :key="log.id"
                                    class="hover:bg-gray-50 transition"
                                >
                                    <td class="px-4 py-3">
                                        <span class="inline-block text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">
                                            {{ log.event }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 font-medium">
                                        {{ stripNamespace(log.auditable_type) }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-400">
                                        {{ log.auditable_id ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-400">
                                        {{ log.user_id ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-400 text-right whitespace-nowrap">
                                        {{ formatDate(log.created_at) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="py-10 text-center">
                        <p class="text-sm text-gray-400">No audit log entries yet.</p>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
