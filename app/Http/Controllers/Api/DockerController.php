<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Services\DockerService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DockerController extends Controller
{
    private DockerService $dockerService;

    public function __construct(DockerService $dockerService)
    {
        $this->dockerService = $dockerService;
    }

    #[OA\Get(
        summary: 'List Images',
        description: 'List all docker images from a specific server.',
        path: '/docker/images/{server_uuid}',
        operationId: 'list-docker-images-by-server-uuid',
        security: [
            ['bearerAuth' => []],
        ],
        tags: ['Docker'],
        parameters: [
            new OA\Parameter(
                name: 'server_uuid',
                description: 'Server UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of docker images',
            ),
            new OA\Response(
                response: 401,
                ref: '#/components/responses/401',
            ),
            new OA\Response(
                response: 404,
                ref: '#/components/responses/404',
            ),
        ]
    )]
    public function listImages(Request $request, $server_uuid)
    {
        $teamId = getTeamIdFromToken();
        if (is_null($teamId)) {
            return invalidTokenResponse();
        }

        $server = Server::where('uuid', $server_uuid)->first();
        if (!$server) {
            return response()->json(['message' => 'Server not found.'], 404);
        }

        try {
            $result = $this->dockerService->getImages($server);
            return response()->json([
                'data' => $result['images'],
                'meta' => [
                    'stats' => $result['stats']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching images: ' . $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        summary: 'Get Image Details',
        description: 'Get detailed information about a specific docker image.',
        path: '/docker/images/{server_uuid}/{image_id}',
        operationId: 'get-docker-image',
        security: [
            ['bearerAuth' => []],
        ],
        tags: ['Docker'],
        parameters: [
            new OA\Parameter(
                name: 'server_uuid',
                description: 'Server UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'image_id',
                description: 'Docker Image ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Docker image details',
            ),
            new OA\Response(
                response: 401,
                ref: '#/components/responses/401',
            ),
            new OA\Response(
                response: 404,
                ref: '#/components/responses/404',
            ),
        ]
    )]
    public function getImageDetails(Request $request, $server_uuid, $image_id)
    {
        $teamId = getTeamIdFromToken();
        if (is_null($teamId)) {
            return invalidTokenResponse();
        }

        $server = Server::where('uuid', $server_uuid)
            ->first();

        if (!$server) {
            return response()->json(['message' => 'Server not found.'], 404);
        }

        try {
            $imageDetails = $this->dockerService->getImageDetails($server, $image_id);
            return response()->json($imageDetails);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Image not found.'], 404);
        }
    }
}
