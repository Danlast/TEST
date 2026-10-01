<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Article;
use App\Models\ArticleView;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->input('q', ''));
        $sort = $request->input('sort', 'newest');
        $sortOptions = ['newest', 'oldest', 'best_week', 'best_month', 'best_year', 'best_all'];
        if (! in_array($sort, $sortOptions, true)) {
            $sort = 'newest';
        }
        $selectedTags = array_values(array_filter(array_map('trim', (array) $request->input('tags', []))));

        $articlesQuery = Article::query()
            ->with(['user', 'club'])
            ->withCount(['comments', 'likes', 'views'])
            ->when(Auth::check(), fn ($articles) => $articles->withExists([
                'likes as liked_by_user' => fn ($likes) => $likes->where('users.id', Auth::id()),
            ]))
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
            });

        if ($sort === 'newest') {
            $articlesQuery->orderByDesc('created_at')->orderByDesc('id');
        } elseif ($sort === 'oldest') {
            $articlesQuery->orderBy('created_at')->orderBy('id');
        } elseif ($sort === 'best_all') {
            $articlesQuery->orderByDesc('likes_count')->orderByDesc('created_at');
        } else {
            $since = match ($sort) {
                'best_week' => now()->subWeek(),
                'best_month' => now()->subMonth(),
                'best_year' => now()->subYear(),
            };

            $articlesQuery
                ->withCount(['likes as period_likes_count' => fn ($likes) => $likes
                    ->where('article_likes.created_at', '>=', $since)])
                ->orderByDesc('period_likes_count')
                ->orderByDesc('created_at');
        }

        $articles = $articlesQuery->paginate(12);
        $articles->appends($request->query());

        return view('pages.articles.index', compact('articles', 'query', 'selectedTags', 'sort'))
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
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5000',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string',
        ]);

        $validated['tags'] = array_values(array_filter($validated['tags'] ?? []));
        $validated['banner'] = $request->file('banner')?->store('article-banners', 'public');
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

    public function show(Request $request, Article $article)
    {
        if (Auth::check()) {
            $visitorKey = 'user:' . Auth::id();
        } else {
            $guestKey = $request->session()->get('article_visitor_key');
            if (! $guestKey) {
                $guestKey = Str::random(40);
                $request->session()->put('article_visitor_key', $guestKey);
            }
            $visitorKey = 'guest:' . $guestKey;
        }

        ArticleView::firstOrCreate([
            'article_id' => $article->id,
            'visitor_key' => $visitorKey,
        ], [
            'user_id' => Auth::id(),
        ]);

        $article->load(['user', 'club']);
        $article->loadCount(['comments', 'likes', 'views']);
        $likedByUser = Auth::check() && $article->likes()->where('users.id', Auth::id())->exists();
        $comments = Comment::nestReplies($article->comments()->with(['user', 'repliedTo.user'])->oldest()->get());

        return view('pages.articles.show', compact('article', 'comments', 'likedByUser'));
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
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5000',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string',
        ]);

        $validated['tags'] = array_values(array_filter($validated['tags'] ?? []));

        if ($request->hasFile('banner')) {
            $oldBanner = $article->banner;
            $validated['banner'] = $request->file('banner')->store('article-banners', 'public');
        } else {
            unset($validated['banner']);
        }

        $article->update($validated);

        if (! empty($oldBanner)) {
            Storage::disk('public')->delete($oldBanner);
        }

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

        $banner = $article->banner;
        $article->delete();

        if ($banner) {
            Storage::disk('public')->delete($banner);
        }

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

        $comment = Comment::create([
            'user_id' => Auth::id(),
            'content' => $request->input('content'),
            'event_id' => null,
            'profile_user_id' => null,
            'article_id' => $article->id,
        ]);

        return CommentController::createdResponse($request, $comment, 'Комментарий добавлен');
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
