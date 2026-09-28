<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_can_store_avatar_and_description(): void
    {
        Storage::fake('public');

        $user = User::create([
            'username' => 'Тестовый пользователь',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user',
        ]);

        $this->actingAs($user);

        $avatar = UploadedFile::fake()->image('avatar.jpg', 200, 200);
        $description = str_repeat('А', 1000);

        $response = $this->post('/profile/update', [
            'username' => 'Новое имя',
            'email' => 'new@example.com',
            'description' => $description,
            'avatar' => $avatar,
        ]);

        $response->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Новое имя', $user->username);
        $this->assertSame('new@example.com', $user->email);
        $this->assertSame($description, $user->description);
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_club_profile_can_store_avatar_and_description(): void
    {
        Storage::fake('public');

        $club = User::create([
            'username' => 'Тестовый клуб',
            'email' => 'club@example.com',
            'password' => 'password123',
            'role' => UserRole::CLUB,
        ]);

        $response = $this->actingAs($club)->post(route('club.update', $club), [
            'username' => $club->username,
            'email' => $club->email,
            'description' => 'Клуб любителей фантастики',
            'avatar' => UploadedFile::fake()->image('club.jpg', 200, 200),
        ]);

        $response->assertRedirect(route('club.profile', $club));
        $club->refresh();

        $this->assertSame('Клуб любителей фантастики', $club->description);
        $this->assertNotNull($club->avatar);
        Storage::disk('public')->assertExists($club->avatar);
    }
}
