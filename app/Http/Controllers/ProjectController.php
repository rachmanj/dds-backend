<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Services\ProjectService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    private ProjectService $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    public function index()
    {
        $fields = ['id', 'code', 'owner', 'location'];
        $projects = $this->projectService->getAll($fields);

        return response()->json(ProjectResource::collection($projects));
    }

    public function show(int $id)
    {
        try {
            $fields = ['id', 'code', 'owner', 'location'];
            $project = $this->projectService->getById($id, $fields);

            return response()->json(new ProjectResource($project));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Project not found'
            ], 404);
        }
    }

    public function store(ProjectRequest $request)
    {
        $project = $this->projectService->create($request->validated());

        return response()->json(new ProjectResource($project), 201);
    }

    public function update(ProjectRequest $request, int $id)
    {
        try {
            $project = $this->projectService->update($id, $request->validated());
            return response()->json(new ProjectResource($project));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Project not found'
            ], 404);
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->projectService->delete($id);
            return response()->json(['message' => 'Project deleted successfully'], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Project not found'
            ], 404);
        }
    }
}
