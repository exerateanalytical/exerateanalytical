<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    status: { type: Number, required: true },
});

const title = computed(() => ({
    503: 'Service Unavailable',
    500: 'Server Error',
    404: 'Page Not Found',
    403: 'Forbidden',
    401: 'Unauthorized',
}[props.status] ?? 'An Error Occurred'));

const description = computed(() => ({
    503: 'We are performing maintenance. Please check back in a few minutes.',
    500: 'Something went wrong on our end. Our team has been notified.',
    404: 'The page you are looking for could not be found.',
    403: 'You do not have permission to access this resource.',
    401: 'You must be logged in to access this page.',
}[props.status] ?? 'An unexpected error occurred. Please try again.'));

const color = computed(() => ({
    503: 'yellow',
    500: 'red',
    404: 'gray',
    403: 'orange',
    401: 'indigo',
}[props.status] ?? 'gray'));
</script>

<template>
    <Head :title="`${status} — ${title}`" />

    <div class="min-h-screen bg-gray-50 flex items-center justify-center px-4">
        <div class="max-w-md w-full text-center space-y-6">

            <!-- Status badge -->
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-white border border-gray-200 shadow-sm mx-auto">
                <span class="text-3xl font-bold text-gray-800">{{ status }}</span>
            </div>

            <!-- Message -->
            <div class="space-y-2">
                <h1 class="text-2xl font-bold text-gray-900">{{ title }}</h1>
                <p class="text-gray-500 leading-relaxed">{{ description }}</p>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a
                    href="/"
                    class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition"
                >
                    Go to Dashboard
                </a>
                <button
                    @click="() => history.back()"
                    class="px-5 py-2.5 bg-white text-gray-700 text-sm font-semibold rounded-lg border border-gray-200 hover:border-gray-300 hover:text-gray-900 transition"
                >
                    Go Back
                </button>
            </div>

            <!-- Platform branding -->
            <p class="text-xs text-gray-400">Exerate Analytical Federation Platform</p>

        </div>
    </div>
</template>
