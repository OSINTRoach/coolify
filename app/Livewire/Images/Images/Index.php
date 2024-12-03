<?php

namespace App\Livewire\Images\Images;

use App\Models\Server;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\DockerService;
use Livewire\Attributes\Computed;

class Index extends Component
{
    public string $selected_uuid = 'default';
    public array $serverImages = [];
    public array $totalStats = [
        'count' => 0,
        'size' => 0,
    ];
    public array $selectedImages = [];
    public array $containerUsage = [];
    public bool $isLoadingImages = false;
    public Collection $servers;
    public string $filter = 'all';  // all, used, unused, dangling
    public string $search = '';     // for repository/tag search
    private DockerService $dockerService;
    public ?array $selectedImageDetails = null;

    public function __construct()
    {
        $this->dockerService = new DockerService();
    }

    public function mount()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
        $this->servers = Server::isReachable()->get();
    }

    public function updatedSelectedUuid()
    {
        $this->loadServerImages();
    }

    public function loadServerImages()
    {
        $this->isLoadingImages = true;

        try {
            if ($this->selected_uuid === 'default') {
                $this->dispatch('error', 'Please select a server.');
                return;
            }

            $server = $this->servers->firstWhere('uuid', $this->selected_uuid);
            if (!$server) {
                $this->dispatch('error', 'Server not found');
                return;
            }

            $result = $this->dockerService->getImages($server);
            $this->serverImages = $result['images'];
            $this->totalStats = $result['stats'];

            error_log('Images loaded successfully');
        } catch (\Exception $e) {
            error_log("Error loading docker images: " . $e->getMessage());
            $this->dispatch('error', 'Error loading images: ' . $e->getMessage());
        } finally {
            $this->isLoadingImages = false;
        }
    }

    public function toggleImageSelection($imageId)
    {
        if (in_array($imageId, $this->selectedImages)) {
            $this->selectedImages = array_diff($this->selectedImages, [$imageId]);
        } else {
            $this->selectedImages[] = $imageId;
        }
    }

    public function deleteSelectedImages()
    {
        if (!$this->selected_uuid || empty($this->selectedImages)) return;

        $server = $this->servers->firstWhere('uuid', $this->selected_uuid);
        if (!$server) return;

        try {
            $this->dockerService->deleteImages($server, $this->selectedImages);
            $this->selectedImages = [];
            $this->loadServerImages();
            error_log('Selected images deleted successfully');
        } catch (\Exception $e) {
            error_log('Error deleting selected images: ' . $e->getMessage());
        }
    }

    public function getImageDetails($imageId)
    {
        if ($this->selected_uuid === 'default') {
            $this->dispatch('error', 'Please select a server first.');
            return;
        }

        try {
            $server = $this->servers->firstWhere('uuid', $this->selected_uuid);
            if (!$server) {
                $this->dispatch('error', 'Server not found');
                return;
            }

            $imageDetails = $this->dockerService->getImageDetails($server, $imageId);
            \Log::info('Image Details:', ['details' => $imageDetails]);

            $this->selectedImageDetails = $imageDetails;
        } catch (\Exception $e) {
            $this->dispatch('error', 'Error loading image details: ' . $e->getMessage());
        }
    }

    public function closeImageDetails()
    {
        $this->selectedImageDetails = null;
    }

    public function pruneUnused()
    {
        if (!$this->selected_uuid) return;

        $server = $this->servers->firstWhere('uuid', $this->selected_uuid);
        if (!$server) return;

        try {
            $this->dockerService->pruneUnused($server);
            $this->loadServerImages();
            error_log('Unused images pruned successfully');
        } catch (\Exception $e) {
            error_log('Error pruning unused images: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.images.images.index', [
            'servers' => Server::isUsable()->get(),
        ]);
    }

    public function loadAllImages()
    {
        $command = "docker images --format '{{json .}}'";
        $result = instant_remote_process([$command], $this, true);

        return collect(explode("\n", trim($result)))
            ->filter()
            ->map(fn($line) => json_decode($line, true))
            ->values();
    }

    protected function getFilteredImages()
    {
        return collect($this->serverImages)
            ->when($this->search, function ($images) {
                return $images->filter(function ($image) {
                    $searchString = strtolower($this->search);
                    return str_contains(strtolower($image['repository']), $searchString) ||
                        str_contains(strtolower($image['tag']), $searchString);
                });
            })
            ->when($this->filter !== 'all', function ($images) {
                return $images->filter(function ($image) {
                    return match ($this->filter) {
                        'used' => $image['container_count'] > 0,
                        'unused' => $image['container_count'] === 0,
                        'dangling' => $image['repository'] === '<none>' || $image['tag'] === '<none>',
                        default => true
                    };
                });
            })
            ->values()
            ->toArray();
    }

    public function getImageStatus($image)
    {
        if ($image['repository'] === '<none>' || $image['tag'] === '<none>') {
            return ['dangling', 'gray'];
        }
        if ($image['container_count'] > 0) {
            return ['in use', 'green'];
        }
        return ['unused', 'yellow'];
    }

    public function formatDate($date)
    {
        return \Carbon\Carbon::parse($date)->diffForHumans();
    }

    #[Computed]
    public function showDetailsModal(): bool
    {
        return $this->showImageDetails;
    }
}
