<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('name')->paginate(20);
        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255','unique:categories,name'],
            'description' => ['nullable','string'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        Category::create($data);

        return redirect()->route('categories.index')->with('success', 'Kategorie erstellt.');
    }

    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255', Rule::unique('categories','name')->ignore($category->id)],
            'description' => ['nullable','string'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $category->update($data);

        return redirect()->route('categories.index')->with('success', 'Kategorie aktualisiert.');
    }

    public function destroy(Category $category)
    {
        // Wenn du verhindern willst, dass Kategorien mit Geräten gelöscht werden:
        if ($category->devices()->exists()) {
            return back()->with('error', 'Kategorie kann nicht gelöscht werden, es sind noch Geräte zugeordnet.');
        }

        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Kategorie gelöscht.');
    }
}
