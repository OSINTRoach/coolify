<?php

namespace App\Livewire;

use App\Models\ApplicationDeploymentQueue;
use App\Models\PrivateKey;
use App\Models\Project;
use App\Models\Server;
use App\Models\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class Dashboard extends Component
{
    public $projects = [];

    public Collection $servers;

    public Collection $privateKeys;

    public array $deploymentsPerServer = [];

    protected $listeners = ['deploymentFinished' => 'loadDeployments'];

    public function mount()
    {
        $this->privateKeys = PrivateKey::ownedByCurrentTeam()->get();
        $this->servers = Server::ownedByCurrentTeam()->get();
        $this->projects = Project::ownedByCurrentTeam()
            ->with(['environments.applications.destination.server'])
            ->get();
        $this->loadDeployments();
    }

    public function getProjectStatus($project)
    {
        $applications = collect();
        foreach ($project->environments as $environment) {
            $applications = $applications->merge($environment->applications);
        }

        $allHealthy = $applications->count() > 0 && $applications->every(fn($app) => $app->status === 'running');
        $anyStopped = $applications->contains(fn($app) => $app->status === 'stopped');

        return [
            'badgeClass' => $allHealthy
                ? 'badge-success'
                : ($anyStopped ? 'badge-error' : 'badge-warning'),
            'statusText' => $allHealthy
                ? 'Healthy'
                : ($anyStopped ? 'Stopped' : 'Unhealthy'),
            'allHealthy' => $allHealthy,
            'anyStopped' => $anyStopped
        ];
    }

    public function deployApplication(Application $application)
    {
        try {
            $application->deploy();
            $this->dispatch('success', 'Deployment started');
        } catch (\Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }

    public function restartApplication(Application $application)
    {
        try {
            $application->restart();
            $this->dispatch('success', 'Application restarted');
        } catch (\Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }

    public function cleanupQueue()
    {
        Artisan::queue('cleanup:deployment-queue', [
            '--team-id' => currentTeam()->id,
        ]);
    }

    public function loadDeployments()
    {
        try {
            $this->deploymentsPerServer = ApplicationDeploymentQueue::whereIn('status', ['in_progress', 'queued'])
                ->whereIn('server_id', $this->servers->pluck('id'))
                ->get([
                    'id',
                    'application_id',
                    'application_name',
                    'deployment_url',
                    'pull_request_id',
                    'server_name',
                    'server_id',
                    'status',
                ])
                ->sortBy('id')
                ->groupBy('server_name')
                ->toArray();
        } catch (\Exception $e) {
            $this->deploymentsPerServer = [];
        }
    }

    public function render()
    {
        return view('livewire.dashboard', [
            'deployments_per_server' => $this->deploymentsPerServer
        ]);
    }
}
