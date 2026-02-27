<script setup>
import { computed } from 'vue';
import AnalyticsLayout from '@/Layouts/AnalyticsLayout.vue';

const props = defineProps({
    country:           { type: Object, default: () => ({}) },
    summary:           { type: Object, default: () => ({}) },
    drivers:           { type: Object, default: () => ({}) },
    govData:           { type: [Object, Array], default: () => ({}) },
    alerts:            { type: Array,  default: () => [] },
    recentAlertEvents: { type: Array,  default: () => [] },
});

const formatDate = (iso) =>
    iso ? new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';

const hasGovData = computed(() => {
    if (!props.govData) return false;
    if (Array.isArray(props.govData)) return props.govData.length > 0;
    return Object.keys(props.govData).length > 0;
});

const riskScore = computed(() => props.summary?.national_risk_score ?? props.summary?.risk_score ?? null);
const riskLevel = computed(() => props.summary?.risk_level ?? null);
const trend     = computed(() => props.summary?.trend ?? null);
const alertsCount = computed(() => props.summary?.alerts_count ?? props.alerts.length);

const scoreColor = (score) => {
    if (score == null) return 'text-gray-400';
    if (score >= 70) return 'text-red-600';
    if (score >= 40) return 'text-amber-600';
    return 'text-emerald-600';
};

const scoreBg = (score) => {
    if (score == null) return 'bg-gray-50';
    if (score >= 70) return 'bg-red-50';
    if (score >= 40) return 'bg-amber-50';
    return 'bg-emerald-50';
};

const riskLevelClass = (level) => {
    const map = {
        critical: 'bg-red-100 text-red-700 border-red-200',
        high:     'bg-orange-100 text-orange-700 border-orange-200',
        elevated: 'bg-amber-100 text-amber-700 border-amber-200',
        moderate: 'bg-yellow-100 text-yellow-700 border-yellow-200',
        low:      'bg-emerald-100 text-emerald-700 border-emerald-200',
        minimal:  'bg-green-100 text-green-700 border-green-200',
    };
    return map[level?.toLowerCase()] ?? 'bg-gray-100 text-gray-600 border-gray-200';
};

const trendInfo = (t) => {
    if (!t) return { icon: '→', cls: 'text-gray-400' };
    const tl = t.toLowerCase();
    if (tl.includes('improv') || tl === 'up' || tl === 'decreasing') return { icon: '↓', cls: 'text-emerald-600' };
    if (tl.includes('deterior') || tl === 'down' || tl === 'increasing') return { icon: '↑', cls: 'text-red-600' };
    return { icon: '→', cls: 'text-gray-500' };
};

const severityClass = (severity) => {
    const map = {
        critical: 'bg-red-100 text-red-700',
        high:     'bg-orange-100 text-orange-700',
        medium:   'bg-amber-100 text-amber-700',
        moderate: 'bg-amber-100 text-amber-700',
        low:      'bg-blue-100 text-blue-700',
        info:     'bg-gray-100 text-gray-600',
    };
    return map[severity?.toLowerCase()] ?? 'bg-gray-100 text-gray-600';
};

// Domain risk driver bar color
const domainBarColor = (value) => {
    if (value == null) return 'bg-gray-300';
    if (value >= 70) return 'bg-red-500';
    if (value >= 40) return 'bg-amber-500';
    return 'bg-emerald-500';
};

const driverDomains = computed(() => {
    if (!props.drivers || typeof props.drivers !== 'object') return [];
    const knownDomains = ['governance', 'fiscal', 'accountability'];
    const results = [];
    for (const key of knownDomains) {
        if (props.drivers[key] != null) {
            results.push({ key, label: key.charAt(0).toUpperCase() + key.slice(1), value: props.drivers[key] });
        }
    }
    // Add remaining domains not in the known list
    for (const [key, val] of Object.entries(props.drivers)) {
        if (!knownDomains.includes(key) && val != null && typeof val !== 'object') {
            results.push({ key, label: key.replace(/_/g, ' '), value: val });
        }
    }
    return results;
});

const govDataEntries = computed(() => {
    if (!props.govData || !hasGovData.value) return [];
    if (Array.isArray(props.govData)) return props.govData;
    return Object.entries(props.govData).map(([key, value]) => ({ key, value }));
});
</script>

<template>
    <AnalyticsLayout :title="`Executive — ${country.name ?? 'Country'}`">
        <template #header>
            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-sm mb-1">
                <a href="/executive" class="text-indigo-600 hover:text-indigo-800 font-medium transition">
                    ← Executive Intelligence
                </a>
                <span class="text-gray-300">/</span>
                <span class="text-gray-700 font-semibold">{{ country.name }}</span>
                <span v-if="country.iso_code" class="text-xs font-mono bg-gray-100 text-gray-500 px-2 py-0.5 rounded">
                    {{ country.iso_code }}
                </span>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

            <!-- Hero Card -->
            <div :class="['rounded-xl border shadow-sm p-6', scoreBg(riskScore), 'border-gray-200']">
                <div class="flex flex-wrap items-start gap-6">
                    <!-- Risk Score -->
                    <div class="text-center min-w-[100px]">
                        <p :class="['text-6xl font-extrabold leading-none', scoreColor(riskScore)]">
                            {{ riskScore != null ? Number(riskScore).toFixed(1) : '—' }}
                        </p>
                        <p class="text-xs text-gray-500 mt-2 font-medium uppercase tracking-wider">National Risk Score</p>
                    </div>

                    <!-- Badges and meta -->
                    <div class="flex flex-col gap-3 flex-1">
                        <!-- Risk level badge -->
                        <div>
                            <span
                                v-if="riskLevel"
                                :class="['inline-block text-sm font-bold px-3 py-1 rounded-full border', riskLevelClass(riskLevel)]"
                            >
                                {{ riskLevel.charAt(0).toUpperCase() + riskLevel.slice(1) }}
                            </span>
                            <span v-else class="inline-block text-sm font-bold px-3 py-1 rounded-full border bg-gray-100 text-gray-500 border-gray-200">
                                Unknown Level
                            </span>
                        </div>

                        <!-- Trend -->
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500 font-medium">Trend:</span>
                            <span :class="['text-sm font-semibold', trendInfo(trend).cls]">
                                {{ trendInfo(trend).icon }}
                                {{ trend ? trend.charAt(0).toUpperCase() + trend.slice(1) : 'N/A' }}
                            </span>
                        </div>

                        <!-- Alerts count -->
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500 font-medium">Active Alerts:</span>
                            <span :class="[
                                'text-sm font-bold px-2 py-0.5 rounded-full',
                                alertsCount > 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500'
                            ]">
                                {{ alertsCount }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Governance Metrics -->
            <section v-if="hasGovData">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Governance Metrics</h2>

                <div v-if="Array.isArray(govData)" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div
                        v-for="(item, idx) in govData"
                        :key="idx"
                        class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 text-center"
                    >
                        <p class="text-xs text-gray-400 capitalize mb-1">{{ String(item.key ?? item.metric ?? idx).replace(/_/g, ' ') }}</p>
                        <p class="text-xl font-bold text-gray-800">
                            {{ item.value ?? item.score ?? '—' }}
                        </p>
                    </div>
                </div>

                <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div
                        v-for="([key, value]) in Object.entries(govData)"
                        :key="key"
                        class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 text-center"
                    >
                        <p class="text-xs text-gray-400 capitalize mb-1">{{ String(key).replace(/_/g, ' ') }}</p>
                        <p class="text-xl font-bold text-gray-800 break-words">
                            {{ typeof value === 'object' ? JSON.stringify(value) : value }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Domain Risk Drivers -->
            <section v-if="driverDomains.length">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Domain Risk Drivers</h2>

                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-5">
                    <div v-for="domain in driverDomains" :key="domain.key">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm font-medium text-gray-700 capitalize">{{ domain.label }}</span>
                            <span :class="['text-sm font-bold', scoreColor(domain.value)]">
                                {{ Number(domain.value).toFixed(1) }}
                            </span>
                        </div>
                        <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                            <div
                                :class="['h-full rounded-full transition-all duration-500', domainBarColor(domain.value)]"
                                :style="{ width: `${Math.min(100, Math.max(0, domain.value))}%` }"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Active Alerts -->
            <section v-if="alerts.length">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    Active Alerts
                    <span class="ml-2 text-sm font-normal text-gray-400">({{ alerts.length }})</span>
                </h2>

                <div class="space-y-3">
                    <div
                        v-for="alert in alerts"
                        :key="alert.id"
                        class="bg-white border border-gray-200 rounded-xl shadow-sm px-5 py-4"
                    >
                        <div class="flex items-start gap-3">
                            <span :class="['shrink-0 mt-0.5 text-xs font-bold px-2 py-0.5 rounded-full', severityClass(alert.severity)]">
                                {{ alert.severity?.toUpperCase() ?? 'ALERT' }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 leading-snug">{{ alert.title }}</p>
                                <p v-if="alert.message" class="text-sm text-gray-500 mt-1">{{ alert.message }}</p>
                                <p class="text-xs text-gray-400 mt-1.5">{{ formatDate(alert.triggered_at) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Alert Event Log -->
            <section v-if="recentAlertEvents.length">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    Alert Event Log
                    <span class="ml-2 text-sm font-normal text-gray-400">({{ recentAlertEvents.length }})</span>
                </h2>

                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Severity</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Triggered</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Acknowledged</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">By</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="event in recentAlertEvents"
                                    :key="event.id"
                                    class="hover:bg-gray-50 transition"
                                >
                                    <td class="px-4 py-3 text-gray-700 font-medium capitalize">
                                        {{ event.alert_type?.replace(/_/g, ' ') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full', severityClass(event.severity)]">
                                            {{ event.severity?.toUpperCase() ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(event.triggered_at) }}</td>
                                    <td class="px-4 py-3 text-xs">
                                        <span v-if="event.acknowledged_at" class="text-emerald-600 font-medium">
                                            {{ formatDate(event.acknowledged_at) }}
                                        </span>
                                        <span v-else class="text-amber-600 font-medium">Pending</span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 text-xs">{{ event.acknowledged_by ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Bottom Navigation Links -->
            <div class="flex flex-wrap gap-4 pt-2">
                <a
                    :href="`/risk/${country.id}`"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition shadow-sm"
                >
                    View Risk Profile
                    <span aria-hidden="true">→</span>
                </a>
                <a
                    :href="`/countries/${country.id}`"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-50 transition shadow-sm"
                >
                    View Country Profile
                    <span aria-hidden="true">→</span>
                </a>
            </div>

        </div>
    </AnalyticsLayout>
</template>
