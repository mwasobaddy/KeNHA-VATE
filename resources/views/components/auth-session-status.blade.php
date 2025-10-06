@props([
    'status',
])

@if ($status)
    <div {{ $attributes->merge(['class' => 'p-4 mb-4 text-sm font-medium rounded-lg bg-green-100 text-green-800 border border-green-200 dark:bg-green-800 dark:text-green-200 dark:border-green-700']) }}>
        <div class="flex items-center">
            <flux:icon name="check-circle" class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" />
            {{ $status }}
        </div>
    </div>
@endif
