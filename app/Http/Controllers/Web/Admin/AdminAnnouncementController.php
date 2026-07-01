<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\Admin\AnnouncementService;
use Illuminate\Http\Request;

class AdminAnnouncementController extends Controller
{
    public function __construct(protected AnnouncementService $announcementService) {}

    public function index(Request $request)
    {
        $announcements = $this->announcementService->list($request->only(['search', 'is_published', 'type']));
        return view('admin.announcements.index', compact('announcements'));
    }

    public function create() { return view('admin.announcements.create'); }

    public function store(Request $request)
    {
        $request->validate(['title' => 'required|string|max:255', 'content' => 'required|string']);
        $this->announcementService->create($request->all());
        return redirect()->route('admin.announcements.index')->with('success', 'Announcement created.');
    }

    public function edit(Announcement $announcement) { return view('admin.announcements.edit', compact('announcement')); }

    public function update(Request $request, Announcement $announcement)
    {
        $this->announcementService->update($announcement, $request->all());
        return redirect()->route('admin.announcements.index')->with('success', 'Announcement updated.');
    }

    public function togglePublish(Announcement $announcement)
    {
        $this->announcementService->togglePublish($announcement);
        return back()->with('success', $announcement->fresh()->is_published ? 'Published.' : 'Unpublished.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return redirect()->route('admin.announcements.index')->with('success', 'Deleted.');
    }
}
