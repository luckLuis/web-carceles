<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_a_user_has_many_reports()
    {
        $user = new User;
        $this ->assertInstanceOf(Collection::class, $user->reports);
    }
    
    public function test_a_user_belongs_to__many_wards()
    {
        $user = new User;
        $this->assertInstanceOf(Collection::class, $user->wards);
    }

    public function test_a_user_belongs_to__many_jails()
    {
        $user = new User;
        $this->assertInstanceOf(Collection::class, $user->jails);
    }
}
