<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AnalyticsLayout from '@/Layouts/AnalyticsLayout.vue';

const props = defineProps({
    actions: { type: Object, default: () => ({ data: [], links: [], last_page: 1 }) },
});

// ── Drawer state ──────────────────────────────────────────────────────────────
const activeAction = ref(null);
const drawerOpen   = ref(false);

function openDrawer(action) {
    activeAction.value = action;
    drawerOpen.value   = true;
}

function closeDrawer() {
    drawerOpen.value = false;
    // keep activeAction populated so the closing animation isn't blank
}

// ── Action-type metadata ──────────────────────────────────────────────────────
const TYPE_META = {
    simulate_contagion: { label: 'Simulate Contagion', color: 'red'    },
    simulate_shock:     { label: 'Simulate Shock',     color: 'red'    },
    launch_poll:        { label: 'Launch Poll',         color: 'emerald'},
    open_consultation:  { label: 'Open Consultation',  color: 'amber'  },
    flag_region:        { label: 'Flag Region',         color: 'amber'  },
};

const typeBadgeClass = (type) => ({
    red:     'bg-red-100     text-red-700     border-red-200',
    emerald: 'bg-emerald-100 text-emerald-700 border-emerald-200',
    amber:   'bg-amber-100   text-amber-700   border-amber-200',
    gray:    'bg-gray-100    text-gray-600    border-gray-200',
})[TYPE_META[type]?.color ?? 'gray'];

const typeLabel = (type) => TYPE_META[type]?.label ?? type;

// ── Impact indicator ──────────────────────────────────────────────────────────
function impact(action) {
    const snap   = action.result_snapshot ?? {};
    const params = action.parameters     ?? {};

    switch (action.action_type) {
        case 'simulate_contagion': {
            const ci = parseFloat(snap.cascade_index ?? snap['cascade_index']);
            const val = isNaN(ci) ? '—' : ci.toFixed(3);
            const cls = ci > 0.6
                ? 'bg-red-100 text-red-700'
                : ci > 0.3
                    ? 'bg-amber-100 text-amber-700'
                    : 'bg-emerald-100 text-emerald-700';
            return { badge: val, sub: 'cascade index', cls };
        }
        case 'simulate_shock': {
            const mag = params.shock_vector ?? params.shock_magnitude ?? snap.shock_vector ?? '—';
            return { badge: String(mag), sub: 'shock vector', cls: 'bg-red-100 text-red-700' };
        }
        case 'launch_poll': {
            const title = snap.title ?? params.title ?? '—';
            return { badge: null, text: title, sub: 'poll', cls: 'text-emerald-700' };
        }
        case 'open_consultation': {
            const title = snap.title ?? params.title ?? '—';
            return { badge: null, text: title, sub: 'proposal', cls: 'text-amber-700' };
        }
        case 'flag_region': {
            const regionId = snap.region_id ?? params.region_id ?? '—';
            return {
                badge: '⚑ Flagged',
                sub: shortId(regionId),
                cls: 'bg-amber-100 text-amber-700',
            };
        }
        default:
            return { badge: '—', sub: '', cls: 'bg-gray-100 text-gray-500' };
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const shortId = (uuid) =>
    uuid && uuid.length > 8 ? uuid.slice(0, 8) + '…' : (uuid ?? '—');

const fmt = (iso) => {
    if (!iso) return '—';
    try {
        return new Date(iso).toLocaleString(undefined, {
            year: 'numeric', month: 'short', day: 'numeric',
            hour: '2-digit', minute: '2-digit',
        });
    } catch { return iso; }
};

const prettyJson = (val) => {
    try { return JSON.stringify(val, null, 2); }
    catch { return String(val); }
};
</script>

<template>
    <AnalyticsLayout title="Executive Actions">

        <!-- ── Header ─────────────────────────────────────────────────────── -->
        <template #header>
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <div class="flex items-center gap-2 text-sm mb-1">
                        <a href="/executive" class="text-indigo-600 hover:text-indigo-800 font-medium transition">
                            Executive
                        </a>
                        <span class="text-gray-300">/</span>
                        <span class="font-semibold text-gray-800">Actions</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Executive Actions</h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Institutional decisions and interventions.
                        <span class="text-gray-400 ml-1">
                            {{ actions.total ?? actions.data.length }} record{{ (actions.total ?? actions.data.length) !== 1 ? 's' : '' }}
                        </span>
                    </p>
                </div>
            </div>
        </template>

        <!-- ── Body ───────────────────────────────────────────────────────── -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- Table -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div v-if="actions.data.length" class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-36">
                                    Time
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">
                                    Actor
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-44">
                                    Action Type
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-36">
                                    Target
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Impact Indicator
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">
                                    Details
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="action in actions.data"
                                :key="action.id"
                                class="hover:bg-gray-50 transition"
                            >
                                <!-- Time -->
                                <td class="px-4 py-3">
                                    <span class="text-xs text-gray-500 whitespace-nowrap">
                                        {{ fmt(action.executed_at) }}
                                    </span>
                                </td>

                                <!-- Actor -->
                                <td class="px-4 py-3">
                                    <span class="text-sm font-medium text-gray-800 truncate block max-w-[120px]">
                                        {{ action.actor?.name ?? '—' }}
                                    </span>
                                </td>

                                <!-- Action Type -->
                                <td class="px-4 py-3">
                                    <span :class="['inline-block text-xs font-semibold px-2.5 py-1 rounded-full border', typeBadgeClass(action.action_type)]">
                                        {{ typeLabel(action.action_type) }}
                                    </span>
                                </td>

                                <!-- Target -->
                                <td class="px-4 py-3">
                                    <template v-if="action.target_id">
                                        <span class="block text-xs font-mono text-gray-600">
                                            {{ shortId(action.target_id) }}
                                        </span>
                                        <span v-if="action.target_type" class="text-xs text-gray-400">
                                            {{ action.target_type.split('\\').pop() }}
                                        </span>
                                    </template>
                                    <span v-else class="text-gray-300 text-xs">—</span>
                                </td>

                                <!-- Impact Indicator -->
                                <td class="px-4 py-3">
                                    <template v-if="impact(action).badge !== null && impact(action).badge !== undefined">
                                        <span :class="['inline-block text-xs font-bold px-2.5 py-1 rounded-full', impact(action).cls]">
                                            {{ impact(action).badge }}
                                        </span>
                                        <span v-if="impact(action).sub" class="ml-1.5 text-xs text-gray-400">
                                            {{ impact(action).sub }}
                                        </span>
                                    </template>
                                    <template v-else>
                                        <span :class="['text-sm font-medium truncate block max-w-[240px]', impact(action).cls]">
                                            {{ impact(action).text }}
                                        </span>
                                        <span v-if="impact(action).sub" class="text-xs text-gray-400">
                                            {{ impact(action).sub }}
                                        </span>
                                    </template>
                                </td>

                                <!-- Details Button -->
                                <td class="px-4 py-3 text-right">
                                    <button
                                        @click="openDrawer(action)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-indigo-600 bg-indigo-50 border border-indigo-100 rounded-lg hover:bg-indigo-100 transition"
                                    >
                                        Details
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty state -->
                <div v-else class="px-6 py-16 text-center">
                    <div class="mx-auto w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-700">No governance actions recorded</p>
                    <p class="text-xs text-gray-400 mt-1">Actions will appear here as SuperAdmins execute interventions.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="actions.last_page > 1" class="flex justify-center gap-1 flex-wrap">
                <Link
                    v-for="link in actions.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    preserve-scroll
                    :class="[
                        'px-3 py-1.5 text-sm rounded-lg border transition',
                        link.active
                            ? 'bg-indigo-600 text-white border-indigo-600'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-indigo-300',
                        !link.url ? 'opacity-40 pointer-events-none' : '',
                    ]"
                />
            </div>

        </div>

        <!-- ── Details Drawer ──────────────────────────────────────────────── -->

        <!-- Backdrop -->
        <Transition
            enter-from-class="opacity-0"
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-to-class="opacity-100"
            leave-from-class="opacity-100"
            leave-active-class="transition-opacity duration-200 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="drawerOpen"
                class="fixed inset-0 bg-black/30 z-40"
                @click="closeDrawer"
            />
        </Transition>

        <!-- Slide-over panel -->
        <Transition
            enter-from-class="translate-x-full"
            enter-active-class="transition-transform duration-300 ease-out"
            enter-to-class="translate-x-0"
            leave-from-class="translate-x-0"
            leave-active-class="transition-transform duration-300 ease-in"
            leave-to-class="translate-x-full"
        >
            <div
                v-if="drawerOpen && activeAction"
                class="fixed inset-y-0 right-0 z-50 flex flex-col w-full max-w-lg bg-white shadow-2xl"
            >
                <!-- Drawer header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 shrink-0">
                    <div>
                        <div class="flex items-center gap-2 mb-0.5">
                            <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full border', typeBadgeClass(activeAction.action_type)]">
                                {{ typeLabel(activeAction.action_type) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ fmt(activeAction.executed_at) }}
                            <span v-if="activeAction.actor?.name" class="ml-2">
                                — by <span class="font-medium text-gray-600">{{ activeAction.actor.name }}</span>
                            </span>
                        </p>
                    </div>
                    <button
                        @click="closeDrawer"
                        class="p-2 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition"
                        aria-label="Close"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Drawer body — scrollable -->
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-6">

                    <!-- Executed at -->
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                            Executed At
                        </h3>
                        <p class="text-sm font-mono text-gray-800">{{ activeAction.executed_at ?? '—' }}</p>
                    </div>

                    <!-- Target -->
                    <div v-if="activeAction.target_id">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Target</h3>
                        <p class="text-sm font-mono text-gray-700 break-all">{{ activeAction.target_id }}</p>
                        <p v-if="activeAction.target_type" class="text-xs text-gray-400 mt-0.5">
                            {{ activeAction.target_type }}
                        </p>
                    </div>

                    <!-- Parameters -->
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                            Parameters
                        </h3>
                        <pre
                            v-if="activeAction.parameters"
                            class="text-xs bg-gray-50 border border-gray-200 rounded-xl p-4 overflow-auto max-h-56 whitespace-pre-wrap break-all leading-relaxed"
                        >{{ prettyJson(activeAction.parameters) }}</pre>
                        <p v-else class="text-xs text-gray-400 italic">No parameters recorded.</p>
                    </div>

                    <!-- Result Snapshot -->
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                            Result Snapshot
                        </h3>
                        <pre
                            v-if="activeAction.result_snapshot"
                            class="text-xs bg-gray-50 border border-gray-200 rounded-xl p-4 overflow-auto max-h-72 whitespace-pre-wrap break-all leading-relaxed"
                        >{{ prettyJson(activeAction.result_snapshot) }}</pre>
                        <p v-else class="text-xs text-gray-400 italic">No result snapshot available.</p>
                    </div>

                </div>

                <!-- Drawer footer -->
                <div class="shrink-0 px-6 py-4 border-t border-gray-100 bg-gray-50">
                    <p class="text-xs text-gray-400 font-mono truncate">ID: {{ activeAction.id }}</p>
                </div>
            </div>
        </Transition>

    </AnalyticsLayout>
</template>
