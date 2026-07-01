<?php

namespace App\Services\Admin;

use App\Models\Announcement;

class AnnouncementService
{
    public function list(array $filters = [], int $perPage = 15)
    {
        $query = Announcement::query();

        if (!empty($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }
        if (isset($filters['is_published'])) {
            $query->where('is_published', $filters['is_published']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(array $data): Announcement
    {
        return Announcement::create([
            'title'             => $data['title'],
            'content'           => $data['content'],
            'type'              => $data['type'] ?? 'info',
            'visibility'        => $data['visibility'] ?? 'all',
            'target_tenant_ids' => $data['target_tenant_ids'] ?? null,
            'target_plan_slugs' => $data['target_plan_slugs'] ?? null,
            'is_published'      => $data['is_published'] ?? false,
            'published_at'      => ($data['is_published'] ?? false) ? now() : null,
            'expires_at'        => $data['expires_at'] ?? null,
            'created_by'        => auth()->id(),
        ]);
    }

    public function update(Announcement $announcement, array $data): Announcement
    {
        $announcement->update(array_filter([
            'title'             => $data['title'] ?? null,
            'content'           => $data['content'] ?? null,
            'type'              => $data['type'] ?? null,
            'visibility'        => $data['visibility'] ?? null,
            'target_tenant_ids' => $data['target_tenant_ids'] ?? null,
            'is_published'      => $data['is_published'] ?? null,
            'expires_at'        => $data['expires_at'] ?? null,
        ], fn($v) => $v !== null));

        if (isset($data['is_published']) && $data['is_published'] && !$announcement->published_at) {
            $announcement->update(['published_at' => now()]);
        }

        return $announcement->fresh();
    }

    public function togglePublish(Announcement $announcement): Announcement
    {
        $announcement->update([
            'is_published' => !$announcement->is_published,
            'published_at' => !$announcement->is_published ? now() : $announcement->published_at,
        ]);
        return $announcement->fresh();
    }

    public function getForTenant(int $tenantId, ?string $planSlug = null): \Illuminate\Database\Eloquent\Collection
    {
        return Announcement::where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) use ($tenantId, $planSlug) {
                $q->where('visibility', 'all')
                  ->orWhere(function ($q2) use ($tenantId) {
                      $q2->where('visibility', 'specific_tenants')
                         ->whereJsonContains('target_tenant_ids', $tenantId);
                  });
                if ($planSlug) {
                    $q->orWhere(function ($q2) use ($planSlug) {
                        $q2->where('visibility', 'plan_based')
                           ->whereJsonContains('target_plan_slugs', $planSlug);
                    });
                }
            })
            ->latest('published_at')
            ->get();
    }
}
