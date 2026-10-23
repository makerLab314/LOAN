<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\InventoryNumbers;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private InventoryNumbers $numbers) {}

    public function index(): View
    {
        $categories = Category::withCount('devices')->orderBy('name')->get();

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('categories.form', ['category' => new Category]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']);
        $data['code'] = $data['code'] ?: $this->numbers->suggestCode($data['name']);

        $category = Category::create($data);
        Audit::log('category.created', $category);

        return redirect()->route('categories.index')->with('success', __('Kategorie angelegt.'));
    }

    public function edit(Category $category): View
    {
        return view('categories.form', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validated($request, $category);
        $data['slug'] = Str::slug($data['name']);
        $data['code'] = $data['code'] ?: ($category->code ?: $this->numbers->suggestCode($data['name'], $category->id));

        $category->update($data);
        Audit::log('category.updated', $category);

        return redirect()->route('categories.index')->with('success', __('Kategorie gespeichert.'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->devices()->exists()) {
            return back()->with('error', __('Der Kategorie sind noch Geräte zugeordnet.'));
        }

        $category->delete();
        Audit::log('category.deleted', $category);

        return redirect()->route('categories.index')->with('success', __('Kategorie gelöscht.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category?->id)],
            'code' => ['nullable', 'string', 'min:2', 'max:5', 'regex:/^[A-Za-z0-9]+$/', Rule::unique('categories', 'code')->ignore($category?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [], ['name' => __('Name'), 'code' => __('Kürzel')]);

        $data['code'] = isset($data['code']) ? strtoupper($data['code']) : null;

        return $data;
    }
}
