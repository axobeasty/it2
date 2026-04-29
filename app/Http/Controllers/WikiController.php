<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use App\Models\WikiPage;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WikiController extends Controller
{
    public const SLUG_REGEX = '[a-z0-9]+(?:-[a-z0-9]+)*';

    /** Зарезервировано под префиксы маршрутов. */
    private const RESERVED_SLUGS = ['create', 'store', 'edit'];

    public function index(Request $request): View
    {
        $user = $request->session()->get('user');
        $settings = Settings::query()->find(1);

        $roots = WikiPage::query()
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')->orderBy('title')])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $flatForNav = WikiPage::query()
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'parent_id']);

        return view('wiki.index', [
            'user' => $user,
            'settings' => $settings,
            'roots' => $roots,
            'flatForNav' => $flatForNav,
            'canEdit' => $user && $user->canAccessPage('knowledge_wiki_edit'),
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $user = $request->session()->get('user');
        $settings = Settings::query()->find(1);

        $page = WikiPage::query()
            ->with(['parent', 'creator', 'editor'])
            ->where('slug', $slug)
            ->firstOrFail();

        $flatForNav = WikiPage::query()
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'parent_id']);

        $sidebarRoots = WikiPage::query()
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')->orderBy('title')])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $html = Str::markdown((string) $page->body);

        return view('wiki.show', [
            'user' => $user,
            'settings' => $settings,
            'page' => $page,
            'html' => $html,
            'flatForNav' => $flatForNav,
            'sidebarRoots' => $sidebarRoots,
            'canEdit' => $user && $user->canAccessPage('knowledge_wiki_edit'),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->session()->get('user');
        if (! $user || ! $user->canAccessPage('knowledge_wiki_edit')) {
            Toastr::error('Недостаточно прав для создания статей.', 'База знаний', ['progressBar' => true]);

            return redirect()->route('wiki.index');
        }

        $settings = Settings::query()->find(1);
        $parents = WikiPage::query()->orderBy('title')->get(['id', 'title', 'parent_id']);

        return view('wiki.create', [
            'user' => $user,
            'settings' => $settings,
            'parents' => $parents,
            'page' => new WikiPage,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->session()->get('user');
        if (! $user || ! $user->canAccessPage('knowledge_wiki_edit')) {
            Toastr::error('Недостаточно прав.', 'База знаний', ['progressBar' => true]);

            return redirect()->route('wiki.index');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:191', 'regex:/^('.self::SLUG_REGEX.')$/'],
            'body' => ['nullable', 'string', 'max:524288'],
            'parent_id' => ['nullable', 'integer', 'exists:wiki_pages,id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);

        $parentId = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;
        if ($parentId && ! $this->isParentHierarchyValid(null, $parentId)) {
            Toastr::error('Выбран недопустимый родительский раздел.', 'База знаний', ['progressBar' => true]);

            return redirect()->back()->withInput();
        }

        $slug = isset($validated['slug']) && $validated['slug'] !== ''
            ? $validated['slug']
            : $this->uniqueSlugFromTitle($validated['title']);

        if ($this->isReservedSlug($slug)) {
            Toastr::error('Такой адрес страницы зарезервирован системой.', 'База знаний', ['progressBar' => true]);

            return redirect()->back()->withInput();
        }

        $slug = $this->ensureUniqueSlug($slug);

        WikiPage::query()->create([
            'title' => $validated['title'],
            'slug' => $slug,
            'body' => $validated['body'] ?? '',
            'parent_id' => $parentId,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        Toastr::success('Статья создана.', 'База знаний', ['progressBar' => true]);

        return redirect()->route('wiki.show', ['slug' => $slug]);
    }

    public function edit(Request $request, string $slug): View|RedirectResponse
    {
        $user = $request->session()->get('user');
        if (! $user || ! $user->canAccessPage('knowledge_wiki_edit')) {
            Toastr::error('Недостаточно прав для редактирования.', 'База знаний', ['progressBar' => true]);

            return redirect()->route('wiki.index');
        }

        $page = WikiPage::query()->where('slug', $slug)->firstOrFail();
        $settings = Settings::query()->find(1);
        $parents = WikiPage::query()
            ->where('id', '!=', $page->id)
            ->orderBy('title')
            ->get(['id', 'title', 'parent_id']);

        return view('wiki.edit', [
            'user' => $user,
            'settings' => $settings,
            'page' => $page,
            'parents' => $parents,
        ]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $user = $request->session()->get('user');
        if (! $user || ! $user->canAccessPage('knowledge_wiki_edit')) {
            Toastr::error('Недостаточно прав.', 'База знаний', ['progressBar' => true]);

            return redirect()->route('wiki.index');
        }

        $page = WikiPage::query()->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:191', 'regex:/^('.self::SLUG_REGEX.')$/'],
            'body' => ['nullable', 'string', 'max:524288'],
            'parent_id' => ['nullable', 'integer', 'exists:wiki_pages,id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);

        $parentId = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;
        if ($parentId === (int) $page->id) {
            Toastr::error('Страница не может быть родителем самой себя.', 'База знаний', ['progressBar' => true]);

            return redirect()->back()->withInput();
        }
        if ($parentId && ! $this->isParentHierarchyValid($page->id, $parentId)) {
            Toastr::error('Выбран недопустимый родительский раздел (цикл в дереве).', 'База знаний', ['progressBar' => true]);

            return redirect()->back()->withInput();
        }

        $newSlug = isset($validated['slug']) && $validated['slug'] !== ''
            ? $validated['slug']
            : $this->uniqueSlugFromTitle($validated['title'], $page->id);

        if ($this->isReservedSlug($newSlug)) {
            Toastr::error('Такой адрес страницы зарезервирован системой.', 'База знаний', ['progressBar' => true]);

            return redirect()->back()->withInput();
        }

        $newSlug = $this->ensureUniqueSlug($newSlug, $page->id);

        $page->fill([
            'title' => $validated['title'],
            'slug' => $newSlug,
            'body' => $validated['body'] ?? '',
            'parent_id' => $parentId,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'updated_by' => $user->id,
        ]);
        $page->save();

        Toastr::success('Статья сохранена.', 'База знаний', ['progressBar' => true]);

        return redirect()->route('wiki.show', ['slug' => $page->slug]);
    }

    public function destroy(Request $request, string $slug): RedirectResponse
    {
        $user = $request->session()->get('user');
        if (! $user || ! $user->canAccessPage('knowledge_wiki_edit')) {
            Toastr::error('Недостаточно прав.', 'База знаний', ['progressBar' => true]);

            return redirect()->route('wiki.index');
        }

        $page = WikiPage::query()->where('slug', $slug)->firstOrFail();

        if ($page->children()->exists()) {
            Toastr::error('Сначала удалите или перенесите дочерние страницы.', 'База знаний', ['progressBar' => true]);

            return redirect()->route('wiki.show', ['slug' => $page->slug]);
        }

        $page->delete();

        Toastr::success('Статья удалена.', 'База знаний', ['progressBar' => true]);

        return redirect()->route('wiki.index');
    }

    private function uniqueSlugFromTitle(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title, '-', 'ru');
        if ($base === '') {
            $base = 'stranica';
        }

        $slug = $base;
        $n = 2;
        while ($this->slugTaken($slug, $ignoreId) || $this->isReservedSlug($slug)) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }

    private function ensureUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $original = $slug;
        $n = 2;
        while ($this->slugTaken($slug, $ignoreId) || $this->isReservedSlug($slug)) {
            $slug = $original.'-'.$n++;
        }

        return $slug;
    }

    private function slugTaken(string $slug, ?int $ignoreId): bool
    {
        $q = WikiPage::query()->where('slug', $slug);
        if ($ignoreId) {
            $q->where('id', '!=', $ignoreId);
        }

        return $q->exists();
    }

    private function isReservedSlug(string $slug): bool
    {
        return in_array($slug, self::RESERVED_SLUGS, true);
    }

    private function isParentHierarchyValid(?int $pageId, int $newParentId): bool
    {
        $current = WikiPage::query()->find($newParentId);
        $guard = 0;
        while ($current && $guard < 100) {
            if ($pageId !== null && (int) $current->id === $pageId) {
                return false;
            }
            $current = $current->parent_id ? WikiPage::query()->find($current->parent_id) : null;
            $guard++;
        }

        return true;
    }
}
