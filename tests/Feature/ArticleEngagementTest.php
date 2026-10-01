<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleEngagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_likes_toggle_once_per_user_and_update_card_count(): void
    {
        $article = $this->createArticle('Статья для лайков', now());
        $user = $this->createUser('liker');

        $this->actingAs($user)
            ->get(route('articles.index'))
            ->assertOk()
            ->assertSee('Лайки: 0')
            ->assertDontSee('Нравится');

        $this->post(route('favorites.store', ['id' => $article->id, 'type' => 'article']))
            ->assertRedirect(route('articles.index') . '#article-engagement');

        $this->assertDatabaseHas('article_likes', [
            'article_id' => $article->id,
            'user_id' => $user->id,
        ]);
        $this->get(route('articles.index'))
            ->assertSee('Лайки: 1')
            ->assertDontSee('Нравится');

        $this->get(route('articles.show', $article))
            ->assertOk()
            ->assertSeeInOrder([
                $article->user->username,
                $article->created_at->format('d.m.Y'),
                $article->title,
                'Текст статьи',
                'Убрать лайк',
            ])
            ->assertSee('aria-label="Просмотры: 1"', false)
            ->assertDontSee('Автор:')
            ->assertDontSee('Теги:')
            ->assertDontSee('Описание:')
            ->assertSee('id="article-engagement"', false);

        $this->post(route('favorites.store', ['id' => $article->id, 'type' => 'article']))
            ->assertRedirect(route('articles.show', $article) . '#article-engagement');

        $this->assertDatabaseMissing('article_likes', [
            'article_id' => $article->id,
            'user_id' => $user->id,
        ]);
        $this->get(route('articles.index'))->assertSee('Лайки: 0');
    }

    public function test_article_views_count_unique_user_and_guest_session_visits(): void
    {
        $article = $this->createArticle('Статья для просмотров', now());

        $this->withSession(['article_visitor_key' => 'same-guest-browser'])
            ->get(route('articles.show', $article))
            ->assertOk();
        $this->withSession(['article_visitor_key' => 'same-guest-browser'])
            ->get(route('articles.show', $article))
            ->assertOk();
        $this->assertDatabaseCount('article_views', 1);

        $user = $this->createUser('reader');
        $this->actingAs($user)->get(route('articles.show', $article))->assertOk();
        $this->get(route('articles.show', $article))->assertOk();

        $this->assertDatabaseCount('article_views', 2);
        $this->get(route('articles.index'))->assertSee('Просмотры: 2');
    }

    public function test_article_index_sorts_by_date_and_like_period(): void
    {
        $user = $this->createUser('period-liker');
        $newest = $this->createArticle('Новая статья', now());
        $oldest = $this->createArticle('Старая статья', now()->subDays(2));
        $weekly = $this->createArticle('Лучшее за неделю', now()->subDays(10));
        $monthly = $this->createArticle('Лучшее за месяц', now()->subDays(20));
        $yearly = $this->createArticle('Лучшее за год', now()->subDays(40));
        $allTime = $this->createArticle('Лучшее за всё время', now()->subDays(500));

        $this->like($weekly, $user, now()->subDays(2));
        $this->like($monthly, $user, now()->subDays(10));
        $this->like($monthly, $this->createUser('month-liker'), now()->subDays(10));
        foreach (range(1, 3) as $index) {
            $this->like($yearly, $this->createUser('year-liker-' . $index), now()->subDays(40));
        }
        foreach (range(1, 4) as $index) {
            $this->like($allTime, $this->createUser('all-liker-' . $index), now()->subDays(500));
        }

        $this->get(route('articles.index', ['sort' => 'newest']))
            ->assertSeeInOrder(['Новая статья', 'Старая статья']);
        $this->get(route('articles.index', ['sort' => 'oldest']))
            ->assertSeeInOrder(['Лучшее за всё время', 'Лучшее за год']);
        $this->get(route('articles.index', ['sort' => 'best_week']))
            ->assertSeeInOrder(['Лучшее за неделю', 'Лучшее за месяц']);
        $this->get(route('articles.index', ['sort' => 'best_month']))
            ->assertSeeInOrder(['Лучшее за месяц', 'Лучшее за неделю']);
        $this->get(route('articles.index', ['sort' => 'best_year']))
            ->assertSeeInOrder(['Лучшее за год', 'Лучшее за месяц']);
        $this->get(route('articles.index', ['sort' => 'best_all']))
            ->assertSeeInOrder(['Лучшее за всё время', 'Лучшее за год']);
    }

    private function createArticle(string $title, $createdAt): Article
    {
        $author = $this->createUser('author-' . uniqid());

        return Article::create([
            'user_id' => $author->id,
            'title' => $title,
            'content' => 'Текст статьи',
            'is_published' => true,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function createUser(string $name): User
    {
        return User::create([
            'username' => $name . uniqid(),
            'email' => $name . uniqid() . '@example.test',
            'password' => 'password',
            'role' => 'user',
        ]);
    }

    private function like(Article $article, User $user, $createdAt): void
    {
        $article->likes()->attach($user->id, [
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}