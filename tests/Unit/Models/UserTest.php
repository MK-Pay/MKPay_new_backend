<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function testUserHasManyAccounts(): void
    {
        $user = User::factory()->create();
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        $user->accounts()->attach($account1, ['is_root' => true]);
        $user->accounts()->attach($account2, ['is_root' => false]);

        $this->assertCount(2, $user->accounts);
        $this->assertTrue($user->accounts->contains($account1));
        $this->assertTrue($user->accounts->contains($account2));
    }

    public function testUserHasRootAccounts(): void
    {
        $user = User::factory()->create();
        $rootAccount = Account::factory()->create();
        $memberAccount = Account::factory()->create();

        $user->accounts()->attach($rootAccount, ['is_root' => true]);
        $user->accounts()->attach($memberAccount, ['is_root' => false]);

        $rootAccounts = $user->rootAccounts()->get();

        $this->assertCount(1, $rootAccounts);
        $this->assertTrue($rootAccounts->contains($rootAccount));
        $this->assertFalse($rootAccounts->contains($memberAccount));
    }

    public function testUserAccountsPivotIncludesIsRoot(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();

        $user->accounts()->attach($account, ['is_root' => true]);

        $accountWithPivot = $user->accounts()->first();
        $this->assertTrue($accountWithPivot->pivot->is_root);
    }

    public function testUserCanBeRootOfMultipleAccounts(): void
    {
        $user = User::factory()->create();
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();
        $account3 = Account::factory()->create();

        $user->accounts()->attach($account1, ['is_root' => true]);
        $user->accounts()->attach($account2, ['is_root' => true]);
        $user->accounts()->attach($account3, ['is_root' => false]);

        $rootAccounts = $user->rootAccounts()->get();

        $this->assertCount(2, $rootAccounts);
        $this->assertFalse($rootAccounts->contains($account3));
    }
}
