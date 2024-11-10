<div>
    <x-slot:title>
        Dashboard | Coolify
    </x-slot>
    @if (session('error'))
        <span x-data x-init="$wire.emit('error', '{{ session('error') }}')" />
    @endif
    <h1>Dashboard</h1>
    <div class="subtitle">Your self-hosted infrastructure.</div>
    @if (request()->query->get('success'))
        <div class="items-center justify-center mb-10 font-bold rounded alert alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 stroke-current shrink-0" fill="none"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Your subscription has been activated! Welcome onboard! <br>It could take a few seconds before your
            subscription is activated.<br> Please be patient.
        </div>
    @endif

    {{-- Projects Section --}}
    <div class="mb-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Projects</h2>
            <x-modal-input buttonTitle="New Project" title="Create Project" class="btn-primary">
                <livewire:project.add-empty />
            </x-modal-input>
        </div>

        @if ($projects->count() > 0)
            <div class="grid gap-4 xl:grid-cols-2">
                @foreach ($projects as $project)
                    @php
                        $hasApplications = !$project->isEmpty();
                        $allHealthy = $project->environments->every(
                            fn($env) => $env->applications->every(fn($app) => $app->isRunning()),
                        );
                        $anyStopped = $project->environments->every(
                            fn($env) => $env->applications->every(fn($app) => $app->isExited()),
                        );
                        $statusClass = $allHealthy ? 'success' : ($anyStopped ? 'error' : 'warning');
                        $statusText = $allHealthy ? 'Healthy' : ($anyStopped ? 'Stopped' : 'Unhealthy');
                    @endphp

                    <div class="bg-white dark:bg-coolgray-100 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden"
                        x-data="{ open: false }">
                        {{-- Project Header --}}
                        <div class="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-coolgray-200">
                            <div class="flex items-center space-x-3 flex-1">
                                <button @click.stop="open = !open"
                                    class="p-1 hover:bg-gray-100 dark:hover:bg-coolgray-300 rounded">
                                    <svg class="w-5 h-5 transition-transform" :class="{ 'rotate-90': open }"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" />
                                    </svg>
                                </button>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $project->name }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $project->getDefaultEnvironmentAttribute() }}</p>
                                </div>
                                @if ($project->applications->count() > 0)
                                    <div
                                        class="flex items-center space-x-2 px-3 py-1 rounded-full text-sm
                                        {{ $statusClass === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100' : '' }}
                                        {{ $statusClass === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100' : '' }}
                                        {{ $statusClass === 'warning' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100' : '' }}">
                                        <div
                                            class="w-2 h-2 rounded-full 
                                            {{ $statusClass === 'success' ? 'bg-green-500' : '' }}
                                            {{ $statusClass === 'error' ? 'bg-red-500' : '' }}
                                            {{ $statusClass === 'warning' ? 'bg-yellow-500' : '' }}">
                                        </div>
                                        <span class="font-medium">{{ $statusText }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex items-center space-x-2">
                                <button @click.stop="gotoProject('{{ $project->uuid }}','{{ 'edit' }}')"
                                    class="p-2 hover:bg-gray-100 dark:hover:bg-coolgray-300 rounded-full transition-colors">
                                    <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path
                                            d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                    </svg>
                                </button>
                                <livewire:project.delete-project :disabled="!$project->isEmpty()" :project_id="$project->id" />
                            </div>
                        </div>

                        {{-- Project Details Section --}}
                        <div x-show="open" x-cloak class="border-t border-gray-200 dark:border-gray-700">
                            @foreach ($project->environments as $environment)
                                <div class="p-4 bg-gray-50 dark:bg-coolgray-200 m-4 rounded-lg">
                                    {{-- Environment Header --}}
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-3">
                                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ $environment->name }}
                                            </h4>
                                            <span
                                                class="px-2 py-1 text-xs rounded-full bg-gray-200 dark:bg-coolgray-300 text-gray-700 dark:text-gray-300">
                                                Environment
                                            </span>
                                        </div>
                                    </div>

                                    <div class="space-y-6">
                                        {{-- Applications Section --}}
                                        <div class="bg-white dark:bg-coolgray-100 rounded-lg p-4">
                                            <div class="flex items-center justify-between mb-3">
                                                <h5 class="font-medium text-gray-900 dark:text-white">Applications</h5>
                                                <span
                                                    class="text-sm text-gray-500">{{ $environment->applications->count() }}
                                                    total</span>
                                            </div>
                                            <div class="space-y-2">
                                                @forelse ($environment->applications->sortBy('name') as $application)
                                                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-coolgray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-coolgray-300 transition-colors cursor-pointer"
                                                        onclick="gotoApplication('{{ $project->uuid }}','{{ $environment->name }}','{{ $application->uuid }}')">
                                                        <div class="flex items-center space-x-3">
                                                            <div
                                                                class="w-2 h-2 rounded-full {{ $application->status === 'running' ? 'bg-green-500' : 'bg-red-500' }}">
                                                            </div>
                                                            <span class="font-medium">{{ $application->name }}</span>
                                                        </div>
                                                        <div class="flex items-center space-x-3">
                                                            <span
                                                                class="text-sm px-2 py-1 rounded-full {{ $application->status === 'running' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                                {{ $application->status }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="text-center py-4 text-gray-500">
                                                        <p>No applications found</p>
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>

                                        {{-- Services Section --}}
                                        <div class="bg-white dark:bg-coolgray-100 rounded-lg p-4">
                                            <div class="flex items-center justify-between mb-3">
                                                <h5 class="font-medium text-gray-900 dark:text-white">Services</h5>
                                                <span
                                                    class="text-sm text-gray-500">{{ $environment->services->count() }}
                                                    total</span>
                                            </div>
                                            <div class="grid gap-2 md:grid-cols-2">
                                                @forelse ($environment->services as $service)
                                                    <div
                                                        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-coolgray-200 rounded-lg group hover:bg-gray-100 dark:hover:bg-coolgray-300 transition-colors">
                                                        <div class="flex items-center space-x-3">
                                                            <span
                                                                class="text-gray-700 dark:text-gray-300">{{ $service->name }}</span>
                                                        </div>
                                                        <div
                                                            class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                            <button
                                                                class="p-1 hover:bg-gray-200 dark:hover:bg-coolgray-400 rounded">
                                                                <svg class="w-4 h-4" viewBox="0 0 20 20"
                                                                    fill="currentColor">
                                                                    <path
                                                                        d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793z" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="col-span-2 text-center py-4 text-gray-500">
                                                        <p>No services found</p>
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>

                                        {{-- Databases Section --}}
                                        <div class="bg-white dark:bg-coolgray-100 rounded-lg p-4">
                                            <div class="flex items-center justify-between mb-3">
                                                <h5 class="font-medium text-gray-900 dark:text-white">Databases</h5>
                                                <span
                                                    class="text-sm text-gray-500">{{ $environment->databases()->count() }}
                                                    total</span>
                                            </div>
                                            <div class="grid gap-2 md:grid-cols-2">
                                                @forelse ($environment->databases() as $database)
                                                    <div
                                                        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-coolgray-200 rounded-lg group hover:bg-gray-100 dark:hover:bg-coolgray-300 transition-colors">
                                                        <div class="flex items-center space-x-3">
                                                            <span
                                                                class="text-gray-700 dark:text-gray-300">{{ $database->name }}</span>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="col-span-2 text-center py-4 text-gray-500">
                                                        <p>No databases found</p>
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 bg-gray-50 dark:bg-coolgray-200 rounded-lg">
                <p class="text-lg font-medium text-gray-600 dark:text-gray-300 mb-4">No projects found</p>
                <div class="flex justify-center space-x-4">
                    <x-modal-input buttonTitle="Create Project" title="New Project" class="btn-primary">
                        <livewire:project.add-empty />
                    </x-modal-input>
                    <a href="{{ route('onboarding') }}"
                        class="text-primary-600 hover:text-primary-700 dark:text-primary-400">
                        Go to onboarding
                    </a>
                </div>
            </div>
        @endif
    </div>

    <h3 class="py-4">Servers</h3>
    @if ($servers->count() > 0)
        <div class="grid grid-cols-1 gap-2 xl:grid-cols-2">
            @foreach ($servers as $server)
                <a href="{{ route('server.show', ['server_uuid' => data_get($server, 'uuid')]) }}"
                    @class([
                        'gap-2 border cursor-pointer box group',
                        'border-transparent' => $server->settings->is_reachable,
                        'border-red-500' => !$server->settings->is_reachable,
                    ])>
                    <div class="flex flex-col justify-center mx-6">
                        <div class="box-title">
                            {{ $server->name }}
                        </div>
                        <div class="box-description">
                            {{ $server->description }}
                        </div>
                        <div class="flex gap-1 text-xs text-error">
                            @if (!$server->settings->is_reachable)
                                Not reachable
                            @endif
                            @if (!$server->settings->is_reachable && !$server->settings->is_usable)
                                &
                            @endif
                            @if (!$server->settings->is_usable)
                                Not usable by Coolify
                            @endif
                        </div>
                    </div>
                    <div class="flex-1"></div>
                </a>
            @endforeach
        </div>
    @else
        @if ($private_keys->count() === 0)
            <div class="flex flex-col gap-1">
                <div class='font-bold dark:text-warning'>No private keys found.</div>
                <div class="flex items-center gap-1">Before you can add your server, first <x-modal-input
                        buttonTitle="add" title="New Private Key">
                        <livewire:security.private-key.create from="server" />
                    </x-modal-input> a private key
                    or
                    go to the <a class="underline dark:text-white" href="{{ route('onboarding') }}">onboarding</a>
                    page.
                </div>
            </div>
        @else
            <div class="flex flex-col gap-1">
                <div class='font-bold dark:text-warning'>No servers found.</div>
                <div class="flex items-center gap-1">
                    <x-modal-input buttonTitle="Add" title="New Server" :closeOutside="false">
                        <livewire:server.create />
                    </x-modal-input> your first server
                    or
                    go to the <a class="underline dark:text-white" href="{{ route('onboarding') }}">onboarding</a>
                    page.
                </div>
            </div>
        @endif
    @endif
    @if ($servers->count() > 0 && $projects->count() > 0)
        <div class="flex items-center gap-2">
            <h3 class="py-4">Deployments</h3>
            @if (!empty($deployments_per_server))
                <x-loading />
            @endif
            <x-modal-confirmation title="Confirm Cleanup Queues?" buttonTitle="Cleanup Queues" isErrorButton
                submitAction="cleanup_queue" :actions="['All running Deployment Queues will be cleaned up.']" :confirmWithText="false" :confirmWithPassword="false"
                step2ButtonText="Permanently Cleanup Deployment Queues" :dispatchEvent="true" dispatchEventType="success"
                dispatchEventMessage="Deployment Queues cleanup started." />
        </div>
        <div wire:poll.3000ms="loadDeployments" class="grid grid-cols-1">
            @forelse ($deployments_per_server as $server_name => $deployments)
                <h4 class="py-4">{{ $server_name }}</h4>
                <div class="grid grid-cols-1 gap-2 lg:grid-cols-3">
                    @foreach ($deployments as $deployment)
                        <a href="{{ data_get($deployment, 'deployment_url') }}"
                            class="box-without-bg-without-border dark:bg-coolgray-100 bg-white gap-2 cursor-pointer group border-l-2 
                            {{ data_get($deployment, 'status') === 'queued' ? 'dark:border-coolgray-300' : '' }}
                            {{ data_get($deployment, 'status') === 'in_progress' ? 'dark:border-yellow-500' : '' }}">
                            <div class="flex flex-col mx-6">
                                <div class="box-title">
                                    {{ data_get($deployment, 'application_name') }}
                                </div>
                                @if (data_get($deployment, 'pull_request_id'))
                                    <div class="box-description">
                                        PR #{{ data_get($deployment, 'pull_request_id') }}
                                    </div>
                                @endif
                                <div class="box-description">
                                    {{ str(data_get($deployment, 'status'))->headline() }}
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @empty
                <div>No deployments running.</div>
            @endforelse
        </div>
    @endif


    <script>
        function gotoProject(uuid, environment) {
            if (environment) {
                window.location.href = '/project/' + uuid + '/' + environment;
            } else {
                window.location.href = '/project/' + uuid;
            }
        }

        function gotoApplication(projectUuid, environment, applicationUuid) {
            if (!projectUuid || !environment || !applicationUuid) {
                return
            }
            window.location.href = '/project/' + projectUuid + '/' + environment + '/application/' + applicationUuid
        }
    </script>
    {{-- <x-forms.button wire:click='getIptables'>Get IPTABLES</x-forms.button> --}}


</div>
