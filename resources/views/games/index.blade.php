<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RTP Games</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-full bg-gray-100">
<div x-data="{
        search: '',
        selectedProvider: 'all',
        selectedCategory: 'all',
        openInfo: null
    }" class="container mx-auto px-4 py-8">
    <!-- Header Section -->
    <header class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">RTP Games</h1>

        <!-- Filters Section -->
        <div class="bg-white rounded-lg shadow p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Games</label>
                <input type="text"
                       id="search"
                       x-model="search"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       placeholder="Search by name...">
            </div>

            <!-- Provider Filter -->
            <div>
                <label for="provider" class="block text-sm font-medium text-gray-700 mb-1">Provider</label>
                <select id="provider"
                        x-model="selectedProvider"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="all">All Providers</option>
                    @foreach($providers as $provider)
                        <option value="{{ $provider }}">{{ $provider }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Category Filter -->
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select id="category"
                        x-model="selectedCategory"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="all">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}">{{ ucfirst($category) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </header>

    <!-- Debug Information -->
    @if(count($games) === 0)
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            No games found in the database.
        </div>
    @endif

    <!-- Games Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach($games as $game)
            <template x-if="(search === '' || '{{ strtolower($game->name) }}'.includes(search.toLowerCase())) &&
                              (selectedProvider === 'all' || selectedProvider === '{{ $game->provider->name }}') &&
                              (selectedCategory === 'all' || '{{ $game->category }}'.includes(selectedCategory))">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <!-- Game Image -->
                    <div class="relative h-48">
                        @if($game->image)
                            <img src="{{ asset($game->image) }}"
                                 alt="{{ $game->name }}"
                                 class="w-full h-full object-cover"
                                 onerror="this.onerror=null; this.src='{{ asset('images/placeholder.jpg') }}'">
                        @else
                            <div class="absolute inset-0 flex items-center justify-center bg-gray-200">
                                <span class="text-gray-500">No Image</span>
                            </div>
                        @endif

                        <!-- Info Button -->
                            <button @click.prevent="openInfo = openInfo === {{ $game->id }} ? null : {{ $game->id }}"
                                    class="absolute top-2 right-2 bg-white bg-opacity-90 rounded-full p-1 shadow-md hover:bg-opacity-100 transition-all duration-200">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </button>

                        <!-- Info Popup -->
                            <div x-show="openInfo === {{ $game->id }}"
                                 @click.away="openInfo = null"
                                 class="absolute right-0 top-8 w-64 bg-white rounded-lg shadow-lg p-4 z-50 text-left">
                                <h4 class="font-semibold mb-2">{{ $game->name }}</h4>
                                <div class="space-y-2 text-sm">
                                    @if($game->weekly_rtp)
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Weekly RTP:</span>
                                            <span class="font-semibold">{{ number_format($game->weekly_rtp, 2) }}%</span>
                                        </div>
                                    @endif
                                    @if($game->monthly_rtp)
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Monthly RTP:</span>
                                            <span class="font-semibold">{{ number_format($game->monthly_rtp, 2) }}%</span>
                                        </div>
                                    @endif
                                    @if($game->risk_level)
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Risk Level:</span>
                                            <span class="font-semibold">{{ $game->risk_level }}</span>
                                        </div>
                                    @endif
                                    @if($game->paylines)
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Paylines:</span>
                                            <span class="font-semibold">{{ $game->paylines }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                    </div>

                    <!-- Game Info -->
                    <div class="p-4">
                        <h3 class="font-semibold text-lg mb-1">{{ $game->name }}</h3>
                        <p class="text-gray-600 text-sm mb-3">{{ $game->provider->name }}</p>

                        <!-- RTP Stats -->
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Current RTP -->
                            <div class="flex flex-col">
                                <span class="text-xs text-gray-500">Current RTP</span>
                                <span class="font-mono font-bold text-lg {{ $game->current_rtp >= 97 ? 'text-green-600' : ($game->current_rtp >= 95 ? 'text-blue-600' : 'text-gray-600') }}">
                                        {{ number_format($game->current_rtp, 2) }}%
                                    </span>
                            </div>

                            <!-- Daily RTP -->
                            <div class="flex flex-col">
                                <span class="text-xs text-gray-500">24h RTP</span>
                                <div class="flex items-center">
                                        <span class="font-mono font-bold text-lg {{ $game->daily_rtp >= 97 ? 'text-green-600' : ($game->daily_rtp >= 95 ? 'text-blue-600' : 'text-gray-600') }}">
                                            {{ number_format($game->daily_rtp, 2) }}%
                                        </span>
                                    @if($game->daily_rtp != $game->current_rtp)
                                        @if($game->daily_rtp > $game->current_rtp)
                                            <svg class="w-4 h-4 ml-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4 ml-1 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                            </svg>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        @endforeach
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('gamesData', () => ({
            search: '',
            selectedProvider: 'all',
            selectedCategory: 'all'
        }))
    })
</script>
</body>
</html>
