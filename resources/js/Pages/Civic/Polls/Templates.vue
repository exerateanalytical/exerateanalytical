<script setup>
import { ref, computed } from 'vue';
import CivicLayout from '@/Layouts/CivicLayout.vue';
import TemplatePreviewModal from '@/Components/Civic/TemplatePreviewModal.vue';

const props = defineProps({
    templates: { type: Array, default: () => [] },
});

// ── Category filter ──────────────────────────────────────────────────────────
const activeCategory = ref('all');

const categories = computed(() => {
    const seen = new Set(props.templates.map(t => t.category));
    return ['all', ...seen];
});

const filtered = computed(() =>
    activeCategory.value === 'all'
        ? props.templates
        : props.templates.filter(t => t.category === activeCategory.value)
);

const premium  = computed(() => filtered.value.filter(t => t.is_premium));
const standard = computed(() => filtered.value.filter(t => !t.is_premium));

// ── Preview modal ─────────────────────────────────────────────────────────────
const previewTemplate = ref(null);
const openPreview  = (t) => { previewTemplate.value = t; };
const closePreview = ()  => { previewTemplate.value = null; };

// ── Styling helpers ──────────────────────────────────────────────────────────
const CATEGORY_COLORS = {
    civic:     'bg-blue-100 text-blue-700',
    policy:    'bg-violet-100 text-violet-700',
    election:  'bg-amber-100 text-amber-700',
    feedback:  'bg-sky-100 text-sky-700',
    community: 'bg-teal-100 text-teal-700',
    general:   'bg-gray-100 text-gray-600',
};
const catColor = (cat) => CATEGORY_COLORS[cat] ?? CATEGORY_COLORS.general;

const POLL_TYPE_LABELS = {
    standard: 'Standard',
    ranked:   'Ranked choice',
    weighted: 'Weighted',
    premium:  'Premium',
};
</script>

<template>
    <CivicLayout title="Poll Templates">
        <template #header>
            <div class="flex items-center gap-3 flex-wrap">
                <a :href="route('civic.polls.index')" class="text-sm text-gray-500 hover:text-gray-700">← Polls</a>
                <span class="text-gray-300">/</span>
                <span class="text-sm font-semibold text-gray-800">Templates</span>
            </div>
        </template>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

            <!-- Page heading -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Poll Templates</h1>
                <p class="mt-1 text-gray-500 text-sm">
                    Choose a curated template to pre-fill your poll and get started faster.
                    All templates are free to use.
                </p>
            </div>

            <!-- Category filter chips -->
            <div class="flex gap-2 flex-wrap mb-8">
                <button v-for="cat in categories" :key="cat"
                    @click="activeCategory = cat"
                    :class="[
                        'px-3 py-1.5 rounded-full text-xs font-semibold transition capitalize border',
                        activeCategory === cat
                            ? 'bg-blue-600 text-white border-blue-600'
                            : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400 hover:text-blue-600',
                    ]">
                    {{ cat === 'all' ? 'All categories' : cat }}
                </button>
            </div>

            <!-- Empty state -->
            <div v-if="filtered.length === 0" class="text-center py-20 text-gray-400">
                <p class="text-lg font-medium">No templates in this category yet.</p>
                <p class="text-sm mt-1">Try selecting a different category.</p>
            </div>

            <!-- Premium / Featured templates -->
            <section v-if="premium.length > 0" class="mb-10">
                <h2 class="text-xs font-semibold text-amber-600 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <span class="text-base">★</span> Featured templates
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <button v-for="t in premium" :key="t.id"
                        @click="openPreview(t)"
                        class="text-left p-5 rounded-xl border-2 border-amber-200 bg-amber-50 hover:border-amber-400 hover:shadow-md transition group focus:outline-none focus:ring-2 focus:ring-amber-400">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div class="flex gap-2 flex-wrap">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-200 text-amber-800">★ Premium</span>
                                <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', catColor(t.category)]">{{ t.category }}</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    {{ POLL_TYPE_LABELS[t.poll_type] ?? t.poll_type }}
                                </span>
                            </div>
                        </div>
                        <h3 class="font-semibold text-gray-900 group-hover:text-amber-700 transition text-sm leading-snug">{{ t.title }}</h3>
                        <p v-if="t.description" class="mt-1 text-xs text-gray-500 line-clamp-2">{{ t.description }}</p>
                        <p class="mt-3 text-xs text-amber-600 font-medium group-hover:underline">Preview & use →</p>
                    </button>
                </div>
            </section>

            <!-- Standard templates -->
            <section v-if="standard.length > 0">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">All templates</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <button v-for="t in standard" :key="t.id"
                        @click="openPreview(t)"
                        class="text-left p-4 rounded-xl border border-gray-200 bg-white hover:border-blue-400 hover:shadow-md transition group focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <div class="flex gap-2 flex-wrap mb-2">
                            <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', catColor(t.category)]">{{ t.category }}</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                {{ POLL_TYPE_LABELS[t.poll_type] ?? t.poll_type }}
                            </span>
                        </div>
                        <h3 class="font-semibold text-gray-900 group-hover:text-blue-700 transition text-sm leading-snug">{{ t.title }}</h3>
                        <p v-if="t.description" class="mt-1 text-xs text-gray-500 line-clamp-2">{{ t.description }}</p>
                        <p class="mt-3 text-xs text-blue-600 font-medium group-hover:underline">Preview & use →</p>
                    </button>
                </div>
            </section>

            <!-- CTA if no templates exist at all -->
            <div v-if="templates.length === 0" class="text-center py-20 text-gray-400">
                <p class="text-lg font-medium">No templates available yet.</p>
                <p class="text-sm mt-1">Check back soon — curated templates are on their way.</p>
                <a :href="route('civic.polls.create')"
                    class="mt-6 inline-block px-5 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition">
                    Create from scratch →
                </a>
            </div>

        </div>

        <!-- Preview modal -->
        <TemplatePreviewModal
            type="poll"
            :template="previewTemplate"
            :on-close="closePreview"
        />
    </CivicLayout>
</template>
