<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import CivicLayout from '@/Layouts/CivicLayout.vue';
import StatusPill from '@/Components/Civic/StatusPill.vue';
import ProgressBar from '@/Components/Civic/ProgressBar.vue';
import TierBadge from '@/Components/Civic/TierBadge.vue';
import EmptyState from '@/Components/Civic/EmptyState.vue';

const props = defineProps({
    petitions: { type: Object, default: () => ({ data: [] }) },
    regions:   { type: Array,  default: () => [] },
    filters:   { type: Object, default: () => ({}) },
});

const regionId = ref(props.filters.region_id ?? '');
const status   = ref(props.filters.status ?? '');

const applyFilters = () => {
    router.get(route('civic.petitions.index'), {
        region_id: regionId.value || undefined,
        status:    status.value   || undefined,
    }, { preserveState: true, replace: true });
};

const formatDate = (iso) => iso
    ? new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
    : '';
</script>

<template>
    <CivicLayout title="Petitions">
        <template #header>
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Petitions</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Civic petitions open for signatures.</p>
                </div>
                <Link
                    v-if="$page.props.auth?.user"
                    :href="route('civic.petitions.create')"
                    class="px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 transition shadow-sm"
                >
                    + Create Petition
                </Link>
            </div>
        </template>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- Filters -->
            <div class="bg-white border border-gray-200 rounded-xl px-4 py-3 flex flex-wrap gap-3 items-center shadow-sm">
                <select v-model="regionId" @change="applyFilters" class="text-sm border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">All regions</option>
                    <option v-for="r in regions" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
                <select v-model="status" @change="applyFilters" class="text-sm border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">All statuses</option>
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="milestone_reached">Milestone Reached</option>
                    <option value="submitted">Submitted</option>
                    <option value="closed">Closed</option>
                </select>
                <span class="ms-auto text-xs text-gray-400">
                    {{ petitions.total ?? petitions.data.length }} petition{{ (petitions.total ?? petitions.data.length) !== 1 ? 's' : '' }}
                </span>
            </div>

            <!-- List -->
            <div v-if="petitions.data.length" class="space-y-4">
                <Link
                    v-for="p in petitions.data"
                    :key="p.id"
                    :href="route('civic.petitions.show', p.id)"
                    class="block bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md hover:border-emerald-200 transition group"
                >
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex items-center gap-2 flex-wrap">
                            <StatusPill :status="p.status" />
                            <span v-if="p.region" class="text-xs text-gray-400">{{ p.region.name }}</span>
                        </div>
                        <span class="text-xs text-gray-400 shrink-0">{{ formatDate(p.created_at) }}</span>
                    </div>

                    <h3 class="font-semibold text-gray-900 group-hover:text-emerald-700 transition mb-1 line-clamp-2">
                        {{ p.title }}
                    </h3>
                    <p class="text-sm text-gray-500 mb-4 line-clamp-2">{{ p.summary }}</p>

                    <ProgressBar :current="p.signature_count" :goal="p.signature_goal" color="emerald" />

                    <div class="flex items-center justify-between mt-3">
                        <div v-if="p.creator" class="flex items-center gap-1.5 text-xs text-gray-400">
                            <span>by {{ p.creator.name }}</span>
                            <TierBadge :tier="p.creator.reputation_tier" />
                        </div>
                        <span v-if="p.deadline" class="text-xs text-gray-400">Deadline {{ formatDate(p.deadline) }}</span>
                    </div>
                </Link>
            </div>

            <EmptyState
                v-else
                title="No petitions found"
                description="Be the first to create a petition for your community."
                :action-label="$page.props.auth?.user ? 'Create a Petition' : null"
                :action-href="$page.props.auth?.user ? route('civic.petitions.create') : null"
            />

            <!-- Pagination -->
            <div v-if="petitions.last_page > 1" class="flex justify-center gap-1">
                <Link
                    v-for="link in petitions.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    preserve-scroll
                    :class="[
                        'px-3 py-1.5 text-sm rounded-lg border transition',
                        link.active ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-600 border-gray-200 hover:border-emerald-300',
                        !link.url ? 'opacity-40 pointer-events-none' : '',
                    ]"
                />
            </div>
        </div>
    </CivicLayout>
</template>
