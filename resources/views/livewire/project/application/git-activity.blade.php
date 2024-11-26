<div>
    <div class="flex items-center justify-between mb-4">
        <h2>Git Activity</h2>
        <x-forms.button wire:click="loadGitActivity">Refresh</x-forms.button>
    </div>

    @if ($error_message)
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-100 rounded-lg">
            {{ $error_message }}
        </div>
    @endif

    @isset($rate_limit_remaining)
        <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            API Requests remaining: {{ $rate_limit_remaining }}
        </div>
    @endisset

    <!-- Debug Information -->
    {{-- <div class="mb-6 p-4 bg-gray-100 dark:bg-coolgray-200 rounded-lg">
        <h3 class="font-bold mb-2">Debug Information:</h3>
        <pre class="text-xs overflow-auto">
            Repository: {{ $debug_info['repo'] ?? 'Not set' }}
            Branch: {{ $debug_info['branch'] ?? 'Not set' }}
            Has Token: {{ $debug_info['has_token'] ? 'Yes' : 'No' }}
            Auth Type: {{ $debug_info['auth_type'] ?? 'unknown' }}
            Using GitHub App: {{ $debug_info['using_github_app'] ? 'Yes' : 'No' }}
            
            Commits Response Status: {{ $debug_info['commits_status'] ?? 'No response' }}
            PRs Response Status: {{ $debug_info['prs_status'] ?? 'No response' }}
            
            Raw Commits Response:
            @json($debug_info['commits_body'] ?? [], JSON_PRETTY_PRINT)
            
            Raw PRs Response:
            @json($debug_info['prs_body'] ?? [], JSON_PRETTY_PRINT)
        </pre>
    </div> --}}

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Commits -->
        <div class="bg-white dark:bg-coolgray-100 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="mb-4">Recent Commits</h3>
            <div class="space-y-4">
                @forelse($commits as $commit)
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4 last:border-0">
                        <div class="flex items-start gap-3">
                            @if (is_array($commit) && isset($commit['author']['avatar_url']))
                                <img src="{{ $commit['author']['avatar_url'] }}" class="w-8 h-8 rounded-full"
                                    alt="Author avatar">
                            @endif
                            <div>
                                @if (is_array($commit) && isset($commit['commit']['message']))
                                    <div class="font-medium">{{ $commit['commit']['message'] }}</div>
                                @endif
                                @if (is_array($commit) && isset($commit['commit']['author']['name']))
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $commit['commit']['author']['name'] }} committed
                                        {{ \Carbon\Carbon::parse($commit['commit']['author']['date'])->diffForHumans() }}
                                    </div>
                                @endif
                                @if (is_array($commit) && isset($commit['sha']))
                                    <div class="text-xs font-mono mt-1">
                                        {{ substr($commit['sha'], 0, 7) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-gray-500 dark:text-gray-400">No commits found</div>
                @endforelse
            </div>
        </div>

        <!-- Pull Requests -->
        <div class="bg-white dark:bg-coolgray-100 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="mb-4">Recent Pull Requests</h3>
            <div class="space-y-4">
                @forelse($pullRequests as $pr)
                    @if (is_array($pr))
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4 last:border-0">
                            <div class="flex items-start gap-3">
                                <div
                                    class="w-8 h-8 flex items-center justify-center rounded-full 
                                    {{ isset($pr['state']) && $pr['state'] === 'open' ? 'bg-green-100 text-green-700' : 'bg-purple-100 text-purple-700' }}">
                                    @if (isset($pr['state']) && $pr['state'] === 'open')
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    @endif
                                </div>
                                <div>
                                    @if (isset($pr['title']))
                                        <div class="font-medium">{{ $pr['title'] }}</div>
                                    @endif
                                    @if (isset($pr['number']) && isset($pr['user']['login']))
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            #{{ $pr['number'] }} by {{ $pr['user']['login'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="text-gray-500 dark:text-gray-400">No pull requests found</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
