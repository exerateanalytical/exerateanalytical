<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Shared template preview modal.
 *
 * Props:
 *   type       — 'poll' | 'petition'
 *   template   — the template object (or null to hide the modal)
 *   onClose    — function to call when the modal is dismissed
 */
const props = defineProps({
    type:     { type: String, required: true },    // 'poll' | 'petition'
    template: { type: Object, default: null },
    onClose:  { type: Function, required: true },
});

const isPoll     = computed(() => props.type === 'poll');
const isPetition = computed(() => props.type === 'petition');

const CATEGORY_COLORS = {
    civic:       'bg-blue-100 text-blue-700',
    policy:      'bg-violet-100 text-violet-700',
    election:    'bg-amber-100 text-amber-700',
    feedback:    'bg-sky-100 text-sky-700',
    community:   'bg-teal-100 text-teal-700',
    environment: 'bg-green-100 text-green-700',
    rights:      'bg-rose-100 text-rose-700',
    general:     'bg-gray-100 text-gray-600',
};

const categoryColor = computed(() =>
    CATEGORY_COLORS[props.template?.category] ?? CATEGORY_COLORS.general
);

const POLL_TYPE_LABELS = {
    standard: 'Standard',
    ranked:   'Ranked choice',
    weighted: 'Weighted',
    premium:  'Premium',
};

const useTemplate = () => {
    if (!props.template) return;
    const createRoute = isPoll.value
        ? route('civic.polls.create')
        : route('civic.petitions.create');

    router.visit(`${createRoute}?template_id=${props.template.id}`);
};
</script>

<template>
    <Teleport to="body">
        <!-- Overlay fade transition -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
        <div
            v-if="template"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @click.self="onClose"
        >
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="onClose" />

            <!-- Panel slide-up transition (appear runs on mount inside v-if) -->
            <Transition
                appear
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 translate-y-4 scale-95"
                enter-to-class="opacity-100 translate-y-0 scale-100"
            >
            <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

                <!-- Header -->
                <div class="px-6 pt-6 pb-4 border-b border-gray-100 flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2 flex-wrap">
                            <span v-if="template.is_premium"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                ★ Premium
                            </span>
                            <span :class="['inline-flex px-2 py-0.5 rounded-full text-xs font-medium', categoryColor]">
                                {{ template.category }}
                            </span>
                            <span v-if="isPoll" class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                {{ POLL_TYPE_LABELS[template.poll_type] ?? template.poll_type }}
                            </span>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900 leading-snug">{{ template.title }}</h2>
                        <p v-if="template.description" class="mt-1 text-sm text-gray-500">{{ template.description }}</p>
                    </div>
                    <button @click="onClose" class="shrink-0 text-gray-400 hover:text-gray-600 transition mt-0.5">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="overflow-y-auto px-6 py-4 space-y-5 flex-1">

                    <!-- POLL preview -->
                    <template v-if="isPoll">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Pre-filled options</p>
                            <ul class="space-y-2">
                                <li v-for="(opt, i) in template.options" :key="i"
                                    class="flex items-center gap-3 px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-700">
                                    <span class="w-5 h-5 rounded-full border-2 border-gray-300 shrink-0" />
                                    {{ opt }}
                                </li>
                            </ul>
                        </div>
                        <div class="flex gap-4 text-sm text-gray-600">
                            <span v-if="template.allow_multiple_votes" class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                Multiple choice
                            </span>
                            <span v-if="template.verified_only" class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Verified accounts only
                            </span>
                        </div>
                    </template>

                    <!-- PETITION preview -->
                    <template v-if="isPetition">
                        <div v-if="template.summary_template">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Summary template</p>
                            <p class="text-sm text-gray-700 italic bg-gray-50 rounded-lg px-3 py-2 border border-gray-200">
                                {{ template.summary_template }}
                            </p>
                        </div>
                        <div v-if="template.body_template">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Body template</p>
                            <pre class="text-sm text-gray-700 bg-gray-50 rounded-lg px-3 py-2 border border-gray-200 whitespace-pre-wrap font-sans leading-relaxed max-h-56 overflow-y-auto">{{ template.body_template }}</pre>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Default signature goal: <strong>{{ template.default_signature_goal?.toLocaleString() }}</strong>
                        </div>
                    </template>

                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50">
                    <button @click="onClose"
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">
                        Cancel
                    </button>
                    <button @click="useTemplate"
                        :class="[
                            'px-5 py-2 text-sm font-semibold text-white rounded-lg transition',
                            isPoll ? 'bg-blue-600 hover:bg-blue-700' : 'bg-emerald-600 hover:bg-emerald-700',
                        ]">
                        Use this template →
                    </button>
                </div>
            </div>
            </Transition>
        </div>
        </Transition>
    </Teleport>
</template>
