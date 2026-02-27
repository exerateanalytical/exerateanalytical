<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import CivicLayout from '@/Layouts/CivicLayout.vue';
import StatusPill from '@/Components/Civic/StatusPill.vue';
import EmptyState from '@/Components/Civic/EmptyState.vue';

const props = defineProps({
    feed:    { type: Array,  default: () => [] },
    regions: { type: Array,  default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const regionId = ref(props.filters.region_id ?? '');
const typeFilter = ref(props.filters.type ?? '');
const sortFilter = ref(props.filters.sort ?? 'recent');

const applyFilters = () => {
    router.get(route('civic.feed'), {
        region_id: regionId.value || undefined,
        type:      typeFilter.value || undefined,
        sort:      sortFilter.value,
    }, { preserveState: true, replace: true });
};

const typeColors = {
    poll:     { pill: 'bg-blue-100 text-blue-700',    dot: 'bg-blue-500'    },
    petition: { pill: 'bg-emerald-100 text-emerald-700', dot: 'bg-emerald-500' },
    policy:   { pill: 'bg-violet-100 text-violet-700',   dot: 'bg-violet-500'   },
};

const detailRoute = (item) => {
    if (item.type === 'poll')     return route('civic.polls.show',      item.id);
    if (item.type === 'petition') return route('civic.petitions.show',  item.id);
    if (item.type === 'policy')   return route('civic.policies.show',   item.id);
    return '#';
};

const formatDate = (iso) => iso ? new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : '';
</script>

<template>
    <CivicLayout title="Civic Feed">
        <template #header>
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Civic Feed</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Polls, petitions and policy proposals from across the federation.</p>
                </div>
            </div>
        </template>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- Filter bar -->
            <div class="bg-white border border-gray-200 rounded-xl px-4 py-3 flex flex-wrap items-center gap-3 shadow-sm">
                <select v-model="typeFilter" @change="applyFilters" class="text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All types</option>
                    <option value="poll">Polls</option>
                    <option value="petition">Petitions</option>
                    <option value="policy">Policy</option>
                </select>

                <select v-model="regionId" @change="applyFilters" class="text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All regions</option>
                    <option v-for="r in regions" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>

                <select v-model="sortFilter" @change="applyFilters" class="text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="recent">Most Recent</option>
                    <option value="impact">Most Impactful</option>
                </select>

                <span class="ms-auto text-xs text-gray-400">{{ feed.length }} item{{ feed.length !== 1 ? 's' : '' }}</span>
            </div>

            <!-- Feed list -->
            <div v-if="feed.length" class="space-y-3">
                <a
                    v-for="item in feed"
                    :key="item.id"
                    :href="detailRoute(item)"
                    class="block bg-white rounded-xl border border-gray-200 px-5 py-4 hover:shadow-md hover:border-gray-300 transition group"
                >
                    <div class="flex items-start gap-3">
                        <span :class="['shrink-0 mt-1 w-2.5 h-2.5 rounded-full', typeColors[item.type]?.dot ?? 'bg-gray-400']" />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full', typeColors[item.type]?.pill ?? 'bg-gray-100 text-gray-600']">
                                    {{ item.type.charAt(0).toUpperCase() + item.type.slice(1) }}
                                </span>
                                <StatusPill :status="item.status" />
                                <span v-if="item.region_id" class="text-xs text-gray-400">
                                    {{ regions.find(r => r.id === item.region_id)?.name ?? '' }}
                                </span>
                            </div>
                            <p class="font-semibold text-gray-900 group-hover:text-indigo-700 transition truncate">
                                {{ item.title }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">{{ formatDate(item.created_at) }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-sm font-bold text-gray-700">{{ item.engagement_score }}</p>
                            <p class="text-xs text-gray-400">score</p>
                        </div>
                    </div>
                </a>
            </div>

            <EmptyState
                v-else
                title="No items match your filters"
                description="Try adjusting the type or region filter, or check back later."
            />
        </div>
    </CivicLayout>
</template>
