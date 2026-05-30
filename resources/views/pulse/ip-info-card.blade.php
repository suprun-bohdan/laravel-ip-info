<x-pulse>
    <x-pulse-card name="IP Info Lookups" cols="full">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Lookups</div>
                <div class="text-2xl font-bold">{{ number_format($lookups) }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Cache hits</div>
                <div class="text-2xl font-bold">{{ number_format($cacheHits) }}</div>
            </div>
        </div>
    </x-pulse-card>
</x-pulse>
