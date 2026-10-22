<?php

namespace App\Http\Controllers;

use App\Models\ResponseTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ResponseTemplateController extends Controller
{
    public function index(): View
    {
        return view('response-templates.index', ['templates' => ResponseTemplate::query()->orderBy('title')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120', Rule::unique('response_templates', 'title')],
            'content' => ['required', 'string', 'max:10000'],
        ]);

        ResponseTemplate::query()->create($validated);

        return back()->with('success', 'Template respons berhasil dibuat.');
    }

    public function update(Request $request, ResponseTemplate $responseTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120', Rule::unique('response_templates', 'title')->ignore($responseTemplate)],
            'content' => ['required', 'string', 'max:10000'],
        ]);

        $responseTemplate->update($validated);

        return back()->with('success', 'Template respons berhasil diperbarui.');
    }

    public function destroy(ResponseTemplate $responseTemplate): RedirectResponse
    {
        $responseTemplate->delete();

        return back()->with('success', 'Template respons berhasil dihapus.');
    }
}
