<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Models\Application;
use App\Models\Server;

class DockerService
{
    public function getImages(Server $server, bool $includeContainerUsage = true)
    {
        $images = $this->fetchImages($server);
        $applications = $this->getApplicationsUsingImages($server);
        $networkInfo = $this->getNetworksInfo($server);
        dd($images);

        if ($includeContainerUsage) {
            $containerUsage = $this->getContainerUsage($server);
            $images = collect($images)->map(function ($image) use ($containerUsage, $applications, $networkInfo) {
                $imageRef = $image['repository'] . ':' . $image['tag'];
                $image['container_count'] = $containerUsage[$imageRef] ?? 0;
                $image['applications'] = $applications[$imageRef] ?? [];
                $image['networks'] = $networkInfo['image_networks'][$imageRef] ?? [];
                return $image;
            })->toArray();
        }

        return [
            'images' => $images,
            'stats' => [
                'count' => count($images),
                'size_bytes' => collect($images)->sum('size_bytes'),
                'size_formatted' => $this->format_bytes(collect($images)->sum('size_bytes'))
            ],
            'networks' => $networkInfo['networks']
        ];
    }

    private function fetchImages(Server $server)
    {
        $command = "docker images --format '{{json .}}'";
        $result = instant_remote_process([$command], $server, true);

        if (empty($result)) {
            return [];
        }

        return collect(explode("\n", trim($result)))
            ->filter()
            ->map(function ($line) {
                $data = json_decode($line, true);
                if (!$data) return null;

                $size_bytes = $this->parse_size($data['Size'] ?? '0B');
                return [
                    'repository' => $data['Repository'] ?? '<none>',
                    'tag' => $data['Tag'] ?? '<none>',
                    'id' => $data['ID'] ?? '',
                    'size_bytes' => $size_bytes,
                    'size_formatted' => $this->format_bytes($size_bytes),
                    'created_at' => $data['CreatedAt'] ?? '',
                    'created_at_formatted' => $this->formatDate($data['CreatedAt'] ?? ''),
                    'status' => $this->getImageStatus($data['Repository'] ?? '<none>', $data['Tag'] ?? '<none>'),
                    'container_count' => 0
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    private function getImageStatus($repository, $tag): array
    {
        if ($repository === '<none>' || $tag === '<none>') {
            return ['label' => 'dangling', 'color' => 'gray'];
        }
        return ['label' => 'valid', 'color' => 'green'];
    }

    private function formatDate($date): string
    {
        return \Carbon\Carbon::parse($date)->diffForHumans();
    }

    public function getContainerUsage(Server $server)
    {
        $containerCommand = "docker ps -a --format '{{.Image}}\t{{.ID}}'";
        $containerResult = instant_remote_process([$containerCommand], $server, true);

        return collect(explode("\n", trim($containerResult)))
            ->filter()
            ->map(function ($line) {
                $parts = explode("\t", $line);
                return [
                    'image' => $parts[0] ?? '',
                    'container_id' => $parts[1] ?? ''
                ];
            })
            ->groupBy('image')
            ->map(fn($containers) => $containers->count())
            ->toArray();
    }

    public function getImageDetails(Server $server, string $imageId)
    {
        $details = json_decode(instant_remote_process(["docker image inspect {$imageId}"], $server, true), true)[0];
        $applications = $this->getApplicationsUsingImages($server);
        $networkInfo = $this->getNetworksInfo($server);
        $containerUsage = $this->getContainersForImage($server, $imageId);

        $imageRef = $details['RepoTags'][0] ?? '<none>:<none>';

        return array_merge($details, [
            'applications' => $applications[$imageRef] ?? [],
            'networks' => $networkInfo['image_networks'][$imageRef] ?? [],
            'containers' => $containerUsage
        ]);
    }

    private function getContainersForImage(Server $server, string $imageId): array
    {
        $command = "docker ps -a --filter ancestor={$imageId} --format '{{json .}}'";
        $result = instant_remote_process([$command], $server, true);

        return collect(explode("\n", trim($result)))
            ->filter()
            ->map(function ($line) {
                $data = json_decode($line, true);
                if (!$data) return null;

                return [
                    'id' => $data['ID'] ?? '',
                    'name' => $data['Names'] ?? '',
                    'status' => $data['Status'] ?? '',
                    'state' => $data['State'] ?? '',
                    'created_at' => $data['CreatedAt'] ?? ''
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    public function deleteImages(Server $server, array $imageIds)
    {
        foreach ($imageIds as $imageId) {
            instant_remote_process(["docker rmi {$imageId} -f"], $server, true);
        }
    }

    public function pruneUnused(Server $server)
    {
        $command = "docker image prune -a -f";
        instant_remote_process([$command], $server, true);
    }

    public function parse_size($size)
    {
        if (empty($size)) return 0;
        if (is_numeric($size)) return (int) $size;

        $units = ['B' => 1, 'KB' => 1024, 'MB' => 1024 * 1024, 'GB' => 1024 * 1024 * 1024];
        $pattern = '/^([\d.]+)\s*([A-Z]+)$/i';

        if (preg_match($pattern, $size, $matches)) {
            $value = (float) $matches[1];
            $unit = strtoupper($matches[2]);
            return isset($units[$unit]) ? (int) ($value * $units[$unit]) : 0;
        }

        return 0;
    }

    public function format_bytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function getApplicationsUsingImages(Server $server): array
    {
        $applications = Application::query()
            ->where(function ($query) use ($server) {
                $query->whereHasMorph('destination', [Server::class], function ($q) use ($server) {
                    $q->where('id', $server->id);
                });
            })
            ->with(['environment.project'])
            ->get();

        $imageUsage = [];

        foreach ($applications as $app) {
            $imageRefs = [];

            // Check static image
            if ($app->static_image) {
                $imageRefs[] = $app->static_image;
            }

            // Check docker registry image
            if ($app->docker_registry_image_name && $app->docker_registry_image_tag) {
                $imageRefs[] = "{$app->docker_registry_image_name}:{$app->docker_registry_image_tag}";
            }

            foreach ($imageRefs as $imageRef) {
                if (!isset($imageUsage[$imageRef])) {
                    $imageUsage[$imageRef] = [];
                }

                $imageUsage[$imageRef][] = [
                    'uuid' => $app->uuid,
                    'name' => $app->name,
                    'environment' => $app->environment->name,
                    'project' => $app->environment->project->name,
                    'status' => $app->status
                ];
            }
        }

        return $imageUsage;
    }

    private function getNetworksInfo(Server $server): array
    {
        $command = "docker network ls --format '{{json .}}'";
        $networks = collect(explode("\n", trim(instant_remote_process([$command], $server, true))))
            ->filter()
            ->map(fn($line) => json_decode($line, true))
            ->filter();

        $imageNetworks = [];
        $networkDetails = [];

        foreach ($networks as $network) {
            $inspectCommand = "docker network inspect {$network['ID']}";
            $details = json_decode(instant_remote_process([$inspectCommand], $server, true), true)[0];

            $networkDetails[] = [
                'id' => $network['ID'],
                'name' => $network['Name'],
                'driver' => $network['Driver'],
                'scope' => $network['Scope'],
                // 'containers' => collect($details['Containers'] ?? [])->map(function ($container) {
                //     return [
                //         'id' => $container['Id'],
                //         'name' => $container['Name'],
                //         'image' => $container['Image']
                //     ];
                // })->values()->toArray()
            ];

            // Map images to networks
            // foreach ($details['Containers'] ?? [] as $container) {
            //     $imageRef = $container['Image'];
            //     if (!isset($imageNetworks[$imageRef])) {
            //         $imageNetworks[$imageRef] = [];
            //     }
            //     $imageNetworks[$imageRef][] = [
            //         'id' => $network['ID'],
            //         'name' => $network['Name']
            //     ];
            // }
        }

        return [
            'networks' => $networkDetails,
            'image_networks' => $imageNetworks
        ];
    }

    public function getImagesForNetwork(Server $server, string $networkId): array
    {
        $networkInfo = $this->getNetworksInfo($server);
        $network = collect($networkInfo['networks'])->firstWhere('id', $networkId);

        if (!$network) {
            return [];
        }

        return collect($network['containers'])
            ->pluck('image')
            ->unique()
            ->values()
            ->toArray();
    }

    // Future Docker-related methods:
    // - Container management
    // - Network configuration
    // - Volume management
    // etc.
}
