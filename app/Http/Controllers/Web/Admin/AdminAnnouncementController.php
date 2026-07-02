<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Support\InternalApiGateway;
use Illuminate\Http\Request;

class AdminAnnouncementController extends Controller
{
    public function __construct(protected InternalApiGateway $api) {}

    public function index(Request $request)
    {
        $perPage = 15;
        $response = $this->api->get('/admin/announcements', [
            'search' => $request->query('search'),
            'is_published' => $request->query('is_published'),
            'type' => $request->query('type'),
            'page' => $request->integer('page', 1),
            'per_page' => $perPage,
        ]);

        $announcements = $this->api->toPaginator($response, $perPage);

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create() { return view('admin.announcements.create'); }

    public function store(Request $request)
    {
        $request->validate(['title' => 'required|string|max:255', 'content' => 'required|string']);

        $response = $this->api->post('/admin/announcements', $request->all());
        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response))->withInput();
        }

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement created.');
    }

    public function edit(int $announcement)
    {
        $response = $this->api->get('/admin/announcements/' . $announcement);
        if (!($response['success'] ?? false)) {
            abort(404);
        }

        $announcement = $this->api->toEntities($response['data'] ?? []);

        return view('admin.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, int $announcement)
    {
        $response = $this->api->put('/admin/announcements/' . $announcement, $request->all());
        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response))->withInput();
        }

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement updated.');
    }

    public function togglePublish(int $announcement)
    {
        $response = $this->api->post('/admin/announcements/' . $announcement . '/toggle-publish');
        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response));
        }

        $entity = $this->api->toEntities($response['data'] ?? []);
        return back()->with('success', $entity?->is_published ? 'Published.' : 'Unpublished.');
    }

    public function destroy(int $announcement)
    {
        $response = $this->api->delete('/admin/announcements/' . $announcement);
        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response));
        }

        return redirect()->route('admin.announcements.index')->with('success', 'Deleted.');
    }
}
