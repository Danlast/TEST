<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_banner_upload_is_shown_after_author_date_tags_and_title(): void
    {
        Storage::fake('public');
        $author = $this->createAuthor();

        $this->actingAs($author)->post(route('articles.store'), [
            'title' => 'Статья с баннером',
            'description' => 'Описание',
            'content' => 'Текст',
            'tags' => ['книги'],
            'banner' => UploadedFile::fake()->image('banner.jpg'),
        ])->assertRedirect(route('articles.index'));

        $article = Article::query()->where('title', 'Статья с баннером')->firstOrFail();
        Storage::disk('public')->assertExists($article->banner);

        $this->get(route('articles.index'))
            ->assertOk()
            ->assertSeeInOrder([
                $author->username,
                $article->created_at->format('d.m.Y'),
                $article->title,
                'книги',
                'article-card-banner',
                'Продолжить чтение',
            ])
            ->assertSee('class="article-card-title-link" href="' . route('articles.show', $article) . '"', false)
            ->assertSee($article->banner_url);

        $this->get(route('event.image', ['path' => $article->banner]))->assertOk();
    }

    public function test_replacing_and_deleting_an_article_removes_old_banner_files(): void
    {
        Storage::fake('public');
        $author = $this->createAuthor();
        $oldBanner = UploadedFile::fake()->image('old-banner.jpg')->store('article-banners', 'public');
        $article = Article::create([
            'user_id' => $author->id,
            'title' => 'Обновляемая статья',
            'content' => 'Текст',
            'banner' => $oldBanner,
        ]);

        $this->actingAs($author)->post(route('articles.update', $article), [
            'title' => $article->title,
            'description' => '',
            'content' => $article->content,
            'tags' => [],
            'banner' => UploadedFile::fake()->image('new-banner.jpg'),
        ])->assertRedirect(route('articles.show', $article));

        $article->refresh();
        Storage::disk('public')->assertMissing($oldBanner);
        Storage::disk('public')->assertExists($article->banner);

        $newBanner = $article->banner;
        $this->delete(route('articles.destroy', $article))
            ->assertRedirect(route('articles.index'));
        Storage::disk('public')->assertMissing($newBanner);
    }

    private function createAuthor(): User
    {
        return User::create([
            'username' => 'Автор баннера',
            'email' => 'banner-author@example.com',
            'password' => 'password',
            'role' => 'user',
        ]);
    }
}