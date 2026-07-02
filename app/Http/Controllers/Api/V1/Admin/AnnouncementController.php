<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\Admin\AnnouncementService;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(name="Admin - Announcements", description="Announcement management")
 */
class AnnouncementController extends Controller
{
    use ApiResponse;

    public function __construct(protected AnnouncementService $announcementService) {}

    public function index(Request $request): JsonResponse
    {
        $announcements = $this->announcementService->list(
            $request->only(['search', 'is_published', 'type']),
            $request->integer('per_page', 15)
        );
        return $this->paginated($announcements);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'             => 'required|string|max:255',
            'content'           => 'required|string',
            'type'              => 'nullable|in:info,warning,success,maintenance,feature',
            'visibility'        => 'nullable|in:all,specific_tenants,plan_based',
            'is_published'      => 'boolean',
            'expires_at'        => 'nullable|date',
            'target_tenant_ids' => 'nullable|array',
            'target_plan_slugs' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $announcement = $this->announcementService->create($request->all());
        return $this->created($announcement);
    }

    public function show(Announcement $announcement): JsonResponse
    {
        return $this->success($announcement);
    }

    public function update(Request $request, Announcement $announcement): JsonResponse
    {
        $announcement = $this->announcementService->update($announcement, $request->all());
        return $this->success($announcement, 'Announcement updated');
    }

    public function togglePublish(Announcement $announcement): JsonResponse
    {
        $announcement = $this->announcementService->togglePublish($announcement);
        $msg = $announcement->is_published ? 'Announcement published' : 'Announcement unpublished';
        return $this->success($announcement, $msg);
    }

    public function destroy(Announcement $announcement): JsonResponse
    {
        $announcement->delete();
        return $this->success(null, 'Announcement deleted');
    }
}
