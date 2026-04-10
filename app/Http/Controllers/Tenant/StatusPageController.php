<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\StatusPage;
use App\Models\Tenant\Component;
use Illuminate\Http\Request;

class StatusPageController extends Controller
{
    public function index()
    {
        $statusPages = StatusPage::withCount('components', 'subscribers')->get();

        return view('tenant.status-pages.index', compact('statusPages'));
    }

    public function create()
    {
        $components = Component::orderBy('name')->get();

        return view('tenant.status-pages.create', compact('components'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:status_pages,slug'],
            'description' => ['nullable', 'string'],
            'is_public' => ['boolean'],
            'brand_color' => ['nullable', 'string', 'max:7'],
            'logo_url' => ['nullable', 'url'],
            'footer_text' => ['nullable', 'string'],
            'show_powered_by' => ['boolean'],
            'show_subscribers' => ['boolean'],
            'allow_subscriber_signup' => ['boolean'],
            'component_ids' => ['nullable', 'array'],
            'component_ids.*' => ['exists:components,id'],
        ]);

        $statusPage = StatusPage::create($validated);

        if ($request->has('component_ids')) {
            $statusPage->components()->sync($request->component_ids);
        }

        return redirect()->route('tenant.status-pages.index')
            ->with('success', 'Status page created successfully.');
    }

    public function edit(StatusPage $statusPage)
    {
        $components = Component::orderBy('name')->get();
        $selectedComponents = $statusPage->components->pluck('id')->toArray();

        return view('tenant.status-pages.edit', compact('statusPage', 'components', 'selectedComponents'));
    }

    public function update(Request $request, StatusPage $statusPage)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:status_pages,slug,' . $statusPage->id],
            'description' => ['nullable', 'string'],
            'custom_domain' => ['nullable', 'string', 'max:255'],
            'is_public' => ['boolean'],
            'password_protected' => ['boolean'],
            'brand_color' => ['nullable', 'string', 'max:7'],
            'logo_url' => ['nullable', 'url'],
            'footer_text' => ['nullable', 'string'],
            'show_powered_by' => ['boolean'],
            'show_subscribers' => ['boolean'],
            'allow_subscriber_signup' => ['boolean'],
            'component_ids' => ['nullable', 'array'],
            'component_ids.*' => ['exists:components,id'],
        ]);

        $statusPage->update($validated);

        if ($request->has('component_ids')) {
            $statusPage->components()->sync($request->component_ids);
        }

        return redirect()->route('tenant.status-pages.index')
            ->with('success', 'Status page updated successfully.');
    }

    public function destroy(StatusPage $statusPage)
    {
        $statusPage->delete();

        return redirect()->route('tenant.status-pages.index')
            ->with('success', 'Status page deleted successfully.');
    }

    public function show(StatusPage $statusPage)
    {
        $statusPage->load(['components.monitor', 'subscribers']);

        return view('tenant.status-pages.show', compact('statusPage'));
    }
}
