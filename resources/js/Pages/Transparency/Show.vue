<script setup>
import { ref, computed } from 'vue';
import AnalyticsLayout from '@/Layouts/AnalyticsLayout.vue';

const props = defineProps({
    country:      { type: Object, required: true },
    methodology:  { type: Object, default: null },
    pillars:      { type: Array,  default: () => [] },
    indicators:   { type: Array,  default: () => [] },
    reliability:  { type: Array,  default: () => [] },
    publications: { type: Array,  default: () => [] },
    disruptions:  { type: Array,  default: () => [] },
});

const tabs = ['Methodology', 'Indicators', 'Publications', 'Data Quality'];
const activeTab = ref('Methodology');

// Build a lookup map: pillar_id -> pillar name
const pillarMap = computed(() => {
    const map = {};
    props.pillars.forEach(p => { map[p.id] = p.name; });
    return map;
});

const normalizationBadgeClass = (method) => {
    const map = {
        'min-max':    'bg-blue-100 text-blue-700',
        'z-score':    'bg-violet-100 text-violet-700',
        'percentile': 'bg-amber-100 text-amber-700',
        'raw':        'bg-gray-100 text-gray-600',
    };
    return map[method] ?? 'bg-gray-100 text-gray-600';
};

const documentTypeBadgeClass = (type) => {
    const map = {
        'methodology': 'bg-indigo-100 text-indigo-700',
        'report':      'bg-green-100 text-green-700',
        'dataset':     'bg-blue-100 text-blue-700',
        'brief':       'bg-amber-100 text-amber-700',
    };
    return map[(type ?? '').toLowerCase()] ?? 'bg-gray-100 text-gray-600';
};

const reliabilityBadgeClass = (score) => {
    if (score == null) return 'bg-gray-100 text-gray-500';
    if (score >= 80) return 'bg-green-100 text-green-700';
    if (score >= 60) return 'bg-amber-100 text-amber-700';
    return 'bg-red-100 text-red-700';
};

const severityBadgeClass = (severity) => {
    const map = {
        critical: 'bg-red-100 text-red-700',
        high:     'bg-orange-100 text-orange-700',
        moderate: 'bg-amber-100 text-amber-700',
        low:      'bg-green-100 text-green-700',
    };
    return map[(severity ?? '').toLowerCase()] ?? 'bg-gray-100 text-gray-600';
};

const formatDate = (iso) => {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString(undefined, {
        year: 'numeric', month: 'short', day: 'numeric',
    });
};

const formatWeight = (weight) => {
    if (weight == null) return '—';
    const pct = parseFloat(weight);
    return isNaN(pct) ? weight : `${(pct * 100).toFixed(1)}%`;
};
</script>

<template>
    <AnalyticsLayout :title="`Transparency — ${country.name}`">
        <template #header>
            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 flex-wrap">
                <a
                    :href="`/countries/${country.id}`"
                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium transition"
                >&larr; {{ country.name }}</a>
                <span class="text-gray-300">/</span>
                <span class="text-sm text-gray-700 font-medium">Transparency</span>
            </div>
        </template>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- Page title -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Transparency</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    Methodology, indicators, publications and data quality for {{ country.name }}.
                </p>
            </div>

            <!-- Tab navigation -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex gap-1 overflow-x-auto">
                    <button
                        v-for="tab in tabs"
                        :key="tab"
                        @click="activeTab = tab"
                        :class="[
                            'shrink-0 px-4 py-2.5 text-sm font-medium border-b-2 transition',
                            activeTab === tab
                                ? 'border-indigo-600 text-indigo-700'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                        ]"
                    >{{ tab }}</button>
                </nav>
            </div>

            <!-- METHODOLOGY TAB -->
            <div v-if="activeTab === 'Methodology'" class="space-y-6">
                <div v-if="methodology" class="space-y-6">
                    <!-- Version card -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">
                                    Methodology Version {{ methodology.version_number }}
                                </h2>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    Published: {{ formatDate(methodology.published_at) }}
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                Active
                            </span>
                        </div>

                        <div v-if="methodology.description_of_change" class="space-y-1">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Description of Change</p>
                            <p class="text-sm text-gray-700 leading-relaxed">{{ methodology.description_of_change }}</p>
                        </div>

                        <div v-if="methodology.change_rationale" class="space-y-1">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Change Rationale</p>
                            <p class="text-sm text-gray-700 leading-relaxed">{{ methodology.change_rationale }}</p>
                        </div>

                        <div v-if="methodology.impact_summary" class="space-y-1">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Impact Summary</p>
                            <p class="text-sm text-gray-700 leading-relaxed">{{ methodology.impact_summary }}</p>
                        </div>
                    </div>

                    <!-- Pillars list -->
                    <div v-if="pillars.length" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h2 class="font-semibold text-gray-800">Pillars</h2>
                            <p class="text-xs text-gray-400 mt-0.5">{{ pillars.length }} pillar{{ pillars.length === 1 ? '' : 's' }} defined</p>
                        </div>
                        <div class="divide-y divide-gray-50">
                            <div
                                v-for="pillar in pillars"
                                :key="pillar.id"
                                class="px-6 py-4 flex items-start gap-4"
                            >
                                <div class="shrink-0 w-14 text-right">
                                    <span class="inline-block text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-100 rounded-lg px-2 py-1">
                                        {{ formatWeight(pillar.weight) }}
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-900">{{ pillar.name }}</p>
                                    <p v-if="pillar.description" class="text-xs text-gray-500 mt-0.5 leading-relaxed">
                                        {{ pillar.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state: no methodology -->
                <div v-else class="bg-white rounded-xl border border-gray-200 shadow-sm py-16 text-center">
                    <div class="text-4xl mb-3 select-none">&#128203;</div>
                    <p class="text-gray-600 font-medium">No active methodology version published</p>
                    <p class="text-gray-400 text-sm mt-1">Methodology documentation will appear here once published.</p>
                </div>
            </div>

            <!-- INDICATORS TAB -->
            <div v-if="activeTab === 'Indicators'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        <span class="font-semibold text-gray-900">{{ indicators.length }}</span>
                        total indicator{{ indicators.length === 1 ? '' : 's' }}
                    </p>
                </div>

                <div v-if="indicators.length" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    <th class="px-4 py-3 text-left">Indicator</th>
                                    <th class="px-4 py-3 text-left">Pillar</th>
                                    <th class="px-4 py-3 text-right">Weight</th>
                                    <th class="px-4 py-3 text-center">Normalization</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr
                                    v-for="indicator in indicators"
                                    :key="indicator.id"
                                    class="hover:bg-gray-50 transition"
                                >
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900">{{ indicator.name }}</p>
                                        <p v-if="indicator.description" class="text-xs text-gray-400 mt-0.5 line-clamp-1">
                                            {{ indicator.description }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 text-sm">
                                        {{ pillarMap[indicator.pillar_id] ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-sm text-gray-700">
                                        {{ formatWeight(indicator.weight) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            v-if="indicator.normalization_method"
                                            :class="['inline-block text-xs font-semibold px-2 py-0.5 rounded-full', normalizationBadgeClass(indicator.normalization_method)]"
                                        >
                                            {{ indicator.normalization_method }}
                                        </span>
                                        <span v-else class="text-gray-300">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-else class="bg-white rounded-xl border border-gray-200 shadow-sm py-16 text-center">
                    <p class="text-gray-400 font-medium">No indicators defined for this country.</p>
                </div>
            </div>

            <!-- PUBLICATIONS TAB -->
            <div v-if="activeTab === 'Publications'" class="space-y-4">
                <div v-if="publications.length" class="space-y-3">
                    <div
                        v-for="pub in publications"
                        :key="pub.id"
                        class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex items-start gap-4 hover:border-indigo-200 transition"
                    >
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span
                                    v-if="pub.document_type"
                                    :class="['text-xs font-semibold px-2 py-0.5 rounded-full shrink-0', documentTypeBadgeClass(pub.document_type)]"
                                >
                                    {{ pub.document_type }}
                                </span>
                                <span class="text-xs text-gray-400">{{ pub.publication_year }}</span>
                            </div>
                            <p class="text-sm font-semibold text-gray-900">{{ pub.title }}</p>
                        </div>
                        <a
                            v-if="pub.url"
                            :href="pub.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="shrink-0 inline-flex items-center px-3 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-100 rounded-lg hover:bg-indigo-100 transition"
                        >
                            View &rarr;
                        </a>
                    </div>
                </div>

                <div v-else class="bg-white rounded-xl border border-gray-200 shadow-sm py-16 text-center">
                    <div class="text-4xl mb-3 select-none">&#128196;</div>
                    <p class="text-gray-600 font-medium">No publications available</p>
                    <p class="text-gray-400 text-sm mt-1">Published reports and datasets will appear here.</p>
                </div>
            </div>

            <!-- DATA QUALITY TAB -->
            <div v-if="activeTab === 'Data Quality'" class="space-y-6">

                <!-- Reliability Scores -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="font-semibold text-gray-800">Indicator Reliability Scores</h2>
                    </div>

                    <div v-if="reliability.length" class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    <th class="px-4 py-3 text-left">Indicator ID</th>
                                    <th class="px-4 py-3 text-center">Score</th>
                                    <th class="px-4 py-3 text-left">Assessed At</th>
                                    <th class="px-4 py-3 text-left">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr
                                    v-for="(item, index) in reliability"
                                    :key="`rel-${index}`"
                                    class="hover:bg-gray-50 transition"
                                >
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">
                                        {{ item.indicator_id }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            :class="['inline-block text-xs font-bold px-2.5 py-1 rounded-full', reliabilityBadgeClass(item.score)]"
                                        >
                                            {{ item.score != null ? Number(item.score).toFixed(1) : '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ formatDate(item.assessed_at) }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500 max-w-xs truncate">
                                        {{ item.notes ?? '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="py-10 text-center">
                        <p class="text-sm text-gray-400">No reliability scores recorded.</p>
                    </div>
                </div>

                <!-- Active Disruptions -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <h2 class="font-semibold text-gray-800">Data Access Disruptions</h2>
                        <span
                            v-if="disruptions.length"
                            class="ml-auto text-xs font-semibold bg-red-100 text-red-700 px-2 py-0.5 rounded-full"
                        >
                            {{ disruptions.length }} active
                        </span>
                    </div>

                    <div v-if="disruptions.length" class="divide-y divide-gray-50">
                        <div
                            v-for="disruption in disruptions"
                            :key="disruption.id"
                            class="px-6 py-4 space-y-1"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    :class="['text-xs font-semibold px-2 py-0.5 rounded-full', severityBadgeClass(disruption.severity)]"
                                >
                                    {{ disruption.severity }}
                                </span>
                                <span class="text-sm font-medium text-gray-900">{{ disruption.disruption_type }}</span>
                                <span class="ml-auto text-xs text-gray-400">
                                    {{ formatDate(disruption.started_at) }} &mdash;
                                    {{ disruption.resolved_at ? formatDate(disruption.resolved_at) : 'Ongoing' }}
                                </span>
                            </div>
                            <p v-if="disruption.description" class="text-xs text-gray-500 leading-relaxed pl-0.5">
                                {{ disruption.description }}
                            </p>
                        </div>
                    </div>

                    <div v-else class="py-10 text-center">
                        <p class="text-sm text-gray-400">No data access disruptions recorded.</p>
                    </div>
                </div>

                <!-- Full empty state when both sections are empty -->
                <div
                    v-if="!reliability.length && !disruptions.length"
                    class="bg-white rounded-xl border border-gray-200 shadow-sm py-16 text-center"
                >
                    <div class="text-4xl mb-3 select-none">&#10003;</div>
                    <p class="text-gray-600 font-medium">No data quality issues recorded</p>
                    <p class="text-gray-400 text-sm mt-1">Reliability scores and disruptions will appear here.</p>
                </div>
            </div>

        </div>
    </AnalyticsLayout>
</template>
