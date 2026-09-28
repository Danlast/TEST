<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class ProfilePrivacyTest extends TestCase
{
    public function test_public_profile_is_visible_to_any_viewer(): void
    {
        $profile = $this->user(10, false);
        $viewer = $this->user(20, false);

        $this->assertTrue($profile->canViewProfile($viewer));
        $this->assertTrue($profile->canViewProfile(null));
    }

    public function test_private_profile_is_visible_only_to_its_owner(): void
    {
        $profile = $this->user(10, true);
        $owner = $this->user(10, true);
        $otherUser = $this->user(20, false);

        $this->assertTrue($profile->canViewProfile($owner));
        $this->assertFalse($profile->canViewProfile($otherUser));
        $this->assertFalse($profile->canViewProfile(null));
    }

    private function user(int $id, bool $isPrivate): User
    {
        $user = new User(['is_profile_private' => $isPrivate]);
        $user->setAttribute($user->getKeyName(), $id);

        return $user;
    }
}
