<div>
    <div class="space-y-4">
        <x-images.navbar />

        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <h2>Docker Images</h2>
                <form class="flex items-center gap-2" wire:submit="loadServerImages">
                    <x-forms.select id="server" required wire:model.live="selected_uuid">
                        @foreach ($servers as $server)
                            @if ($loop->first)
                                <option disabled value="default">Select a server</option>
                            @endif
                            <option value="{{ $server->uuid }}">{{ $server->name }}</option>
                        @endforeach
                    </x-forms.select>
                    <x-forms.button type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="loadServerImages">Refresh</span>
                        <span wire:loading wire:target="loadServerImages">Loading...</span>
                    </x-forms.button>
                </form>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <x-forms.input wire:model.live.debounce.300ms="search" placeholder="Search images..."
                        type="search" />
                </div>

                <div class="flex items-center gap-2">
                    <x-forms.select wire:model.live="filter">
                        <option value="all">All Images</option>
                        <option value="used">In Use</option>
                        <option value="unused">Unused</option>
                        <option value="dangling">Dangling</option>
                    </x-forms.select>
                </div>

                <div class="text-sm">
                    Images: {{ count($this->getFilteredImages()) }} of {{ $totalStats['count'] }} |
                    Storage: {{ $totalStats['size_formatted'] ?? '0 B' }}
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div wire:loading.block wire:target="loadServerImages" class="text-center py-4">
                <x-loading text="Loading images..." />
            </div>

            <div wire:loading.remove wire:target="loadServerImages">
                @if ($selected_uuid !== 'default')
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-8">
                                        <input type="checkbox" class="rounded border-gray-300 dark:border-gray-700"
                                            wire:model.live="selectAll" />
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Repository</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tag
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Image
                                        ID</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Created</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Size
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Containers</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            @if ($selected_uuid)
                                @if (count($serverImages) > 0)
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @forelse($this->getFilteredImages() as $image)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                                <td class="px-6 py-4 whitespace-nowrap" wire:click.stop>
                                                    <input type="checkbox"
                                                        class="rounded border-gray-300 dark:border-gray-700"
                                                        wire:model.live="selectedImages" value="{{ $image['id'] }}" />
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <span class="max-w-xs truncate ..."
                                                            title="{{ $image['repository'] }}">
                                                            {{ $image['repository'] }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="max-w-xs truncate ..." title="{{ $image['tag'] }}">
                                                        {{ $image['tag'] }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @php
                                                        [$status, $color] = $this->getImageStatus($image);
                                                    @endphp
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800">
                                                        {{ $status }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap font-mono text-sm">
                                                    <span title="{{ $image['id'] }}">
                                                        {{ Str::limit($image['id'], 12) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span title="{{ $image['created_at'] }}">
                                                        {{ \Carbon\Carbon::parse($image['created_at'])->diffForHumans() }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span title="{{ $image['size_formatted'] }}">
                                                        {{ $image['size_formatted'] }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $image['container_count'] > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                                        {{ $image['container_count'] }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex gap-2">
                                                        <x-forms.button
                                                            wire:click="getImageDetails('{{ $image['id'] }}')"
                                                            wire:loading.attr="disabled">
                                                            <span wire:loading.remove
                                                                wire:target="getImageDetails('{{ $image['id'] }}')">Details</span>
                                                            <span wire:loading
                                                                wire:target="getImageDetails('{{ $image['id'] }}')">Loading...</span>
                                                        </x-forms.button>
                                                        <x-modal-confirmation isErrorButton buttonTitle="Delete"
                                                            title="Confirm Image Deletion" :submitAction="'deleteImage(\'' . $image['id'] . '\')'"
                                                            :actions="['This image will be permanently deleted.']" />
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                                    @if ($search || $filter !== 'all')
                                                        No images found matching your filters.
                                                    @else
                                                        No images found on this server.
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                @endif
                            @endif
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-gray-500">
                        Please select a server to view images
                    </div>
                @endif
            </div>
        </div>

        @if ($selectedImageDetails)
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg mt-4">
                <div
                    class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900 rounded-t-lg">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-semibold">Image Details</h3>
                        <span class="text-sm text-gray-500">
                            {{ $selectedImageDetails['RepoTags'][0] ?? 'Untagged' }}
                        </span>
                    </div>
                    <x-forms.button wire:click="closeImageDetails" size="sm"
                        class="hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </x-forms.button>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Basic Information --}}
                        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                            <h3 class="text-base font-semibold mb-3 flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Basic Information
                            </h3>
                            <dl class="grid grid-cols-1 gap-2 text-sm">
                                <div class="flex justify-between py-1 border-b border-gray-200 dark:border-gray-700">
                                    <dt class="font-medium text-gray-500">ID</dt>
                                    <dd class="font-mono">
                                        {{ Str::limit(str_replace('sha256:', '', $selectedImageDetails['Id']), 12) }}
                                    </dd>
                                </div>
                                <div class="flex justify-between py-1 border-b border-gray-200 dark:border-gray-700">
                                    <dt class="font-medium text-gray-500">Created</dt>
                                    <dd>{{ \Carbon\Carbon::parse($selectedImageDetails['Created'])->format('Y-m-d H:i:s') }}
                                    </dd>
                                </div>
                                <div class="flex justify-between py-1 border-b border-gray-200 dark:border-gray-700">
                                    <dt class="font-medium text-gray-500">Size</dt>
                                    <dd class="font-mono">
                                        {{ $this->dockerService->format_bytes($selectedImageDetails['Size'] ?? 0) }}
                                    </dd>
                                </div>
                                <div class="flex justify-between py-1 border-b border-gray-200 dark:border-gray-700">
                                    <dt class="font-medium text-gray-500">Architecture</dt>
                                    <dd>{{ $selectedImageDetails['Architecture'] }}</dd>
                                </div>
                                <div class="flex justify-between py-1">
                                    <dt class="font-medium text-gray-500">OS</dt>
                                    <dd>{{ $selectedImageDetails['Os'] }}</dd>
                                </div>
                            </dl>
                        </div>

                        {{-- Configuration --}}
                        @if (!empty($selectedImageDetails['Config']))
                            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                                <h3 class="text-base font-semibold mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                        </path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    Configuration
                                </h3>
                                <div class="space-y-3">
                                    @if (!empty($selectedImageDetails['Config']['ExposedPorts']))
                                        <div class="text-sm">
                                            <span class="font-medium text-gray-500">Exposed Ports</span>
                                            <div class="mt-1 pl-4 flex flex-wrap gap-2">
                                                @foreach (array_keys($selectedImageDetails['Config']['ExposedPorts']) as $port)
                                                    <span
                                                        class="px-2 py-1 bg-blue-100 text-blue-800 rounded-md text-xs">{{ $port }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if (!empty($selectedImageDetails['Config']['Env']))
                                        <div class="text-sm">
                                            <span class="font-medium text-gray-500">Environment</span>
                                            <div class="mt-1 pl-4 space-y-1 max-h-32 overflow-y-auto">
                                                @foreach ($selectedImageDetails['Config']['Env'] as $env)
                                                    <div
                                                        class="font-mono text-xs bg-gray-100 dark:bg-gray-800 p-1 rounded">
                                                        {{ $env }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Layers --}}
                    @if (!empty($selectedImageDetails['RootFS']))
                        <div class="mt-6 bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                            <h3 class="text-base font-semibold mb-3 flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                    </path>
                                </svg>
                                Layers
                            </h3>
                            <div class="text-sm">
                                <div class="mb-2">
                                    <span class="font-medium text-gray-500">Type:</span>
                                    <span class="ml-2">{{ $selectedImageDetails['RootFS']['Type'] }}</span>
                                </div>
                                <div class="space-y-1">
                                    @foreach ($selectedImageDetails['RootFS']['Layers'] as $layer)
                                        <div class="font-mono text-xs bg-gray-100 dark:bg-gray-800 p-2 rounded">
                                            {{ Str::limit(str_replace('sha256:', '', $layer), 16) }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
