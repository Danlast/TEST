<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->input('q', ''));
        $selectedTags = array_values(array_filter(array_map('trim', (array) $request->input('tags', []))));

        $articlesQuery = Article::query()
            ->with(['user', 'club'])
            ->where('is_published', true)
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('title', 'like', '%' . $query . '%')
                        ->orWhere('description', 'like', '%' . $query . '%')
                        ->orWhere('content', 'like', '%' . $query . '%')
                        ->orWhereJsonContains('tags', $query);
                });
            })
            ->when(! empty($selectedTags), function ($q) use ($selectedTags) {
                foreach ($selectedTags as $tag) {
                    $q->whereJsonContains('tags', $tag);
                }
            })
            ->latest();

        $articles = $articlesQuery->paginate(12);
        $articles->appends($request->query());

        return view('pages.articles.index', compact('articles', 'query', 'selectedTags'))
            ->with('availableTags', $this->availableTags());
    }

    public function create()
    {
        if (! Auth::check()) {
            abort(403);
        }

        return view('pages.articles.create')->with('availableTags', $this->availableTags());
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'required|string',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string',
        ]);

        $validated['tags'] = array_values(array_filter($validated['tags'] ?? []));
        $validated['user_id'] = $user->id;
        $validated['club_id'] = null;
        $validated['is_published'] = true;

        if ($user->role === UserRole::CLUB) {
            $validated['club_id'] = $user->id;
        } elseif ($user->role === UserRole::CLUB_MODERATOR) {
            abort_unless($user->club_id, 403, 'Для публикации статьи клубному модератору нужно назначить клуб.');
            abort_unless(
                User::query()->whereKey($user->club_id)->where('role', UserRole::CLUB)->exists(),
                403,
                'Указанный клуб недоступен.'
            );
            $validated['club_id'] = $user->club_id;
        }

        Article::create($validated);

        return redirect()->route('articles.index')->with('success', 'Статья опубликована');
    }

    public function show(Article $article)
    {
        $article->load(['user', 'club', 'comments.user']);

        return view('pages.articles.show', compact('article'));
    }

    public function edit(Article $article)
    {
        abort_unless($article->canBeManagedBy(Auth::user()), 403);

        return view('pages.articles.edit', compact('article'))->with('availableTags', $this->availableTags());
    }

    public function update(Request $request, Article $article)
    {
        abort_unless($article->canBeManagedBy(Auth::user()), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'required|string',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string',
        ]);

        $validated['tags'] = array_values(array_filter($validated['tags'] ?? []));

        $article->update($validated);

        return redirect()->route('articles.show', $article)->with('success', 'Статья обновлена');
    }

    public function delete(Article $article)
    {
        abort_unless($article->canBeManagedBy(Auth::user()), 403);

        return view('pages.articles.delete', compact('article'));
    }

    public function destroy(Article $article)
    {
        abort_unless($article->canBeManagedBy(Auth::user()), 403);

        $article->delete();

        return redirect()->route('articles.index')->with('success', 'Статья удалена');
    }

    public function storeComment(Request $request, Article $article)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        if (! Auth::check()) {
            abort(403);
        }

        $user = Auth::user();

        if (! $user->canAccessClubContent($article->club_id)) {
            abort(403, 'Вы забанены в этом клубе и не можете оставлять комментарии под его статьями.');
        }

        Comment::create([
            'user_id' => Auth::id(),
            'content' => $request->input('content'),
            'event_id' => null,
            'profile_user_id' => null,
            'article_id' => $article->id,
        ]);

        return back()->with('success', 'Комментарий добавлен');
    }

    protected function availableTags(): array
    {
        return [
            'новости' => 'Новости',
            'обзор' => 'Обзор',
            'рекомендации' => 'Рекомендации',
            'книги' => 'Книги',
            'клуб' => 'Клуб',
            'мероприятия' => 'Мероприятия',
        ];
    }
}
