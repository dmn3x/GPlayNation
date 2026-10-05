<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_approves_pending_developer_who_can_then_log_in(): void // SRS 3.1.1.4
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->developer()->create();

        $this->actingAs($admin)->post("/admin/verification/{$dev->id}", ['action' => 'approve'])->assertRedirect();
        $this->assertSame(UserStatus::Active, $dev->fresh()->status);
        $this->post('/login', ['email' => $dev->email, 'password' => 'password'])->assertRedirect('/dev');
    }

    public function test_reject_requires_reason_and_blocks_login(): void // SRS 3.1.1.4 alternate
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->developer()->create();

        $this->actingAs($admin)->from('/admin/verification')->post("/admin/verification/{$dev->id}", ['action' => 'reject'])
            ->assertSessionHasErrors('reason');
        $this->actingAs($admin)->post("/admin/verification/{$dev->id}", ['action' => 'reject', 'reason' => 'Invalid documents'])
            ->assertRedirect();
        $this->assertSame(UserStatus::Rejected, $dev->fresh()->status);
        $this->assertSame('Invalid documents', $dev->fresh()->rejection_reason);
    }

    public function test_rejected_developer_can_be_reapproved(): void // Review Focus 5
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->developer()->create(['status' => UserStatus::Rejected, 'rejection_reason' => 'x']);

        $this->actingAs($admin)->get('/admin/verification?tab=rejected')->assertSee($dev->email);
        $this->actingAs($admin)->post("/admin/verification/{$dev->id}", ['action' => 'approve']);
        $this->assertSame(UserStatus::Active, $dev->fresh()->status);
        $this->assertNull($dev->fresh()->rejection_reason);
    }

    public function test_non_admin_cannot_verify(): void
    {
        $dev = User::factory()->developer()->create();
        $target = User::factory()->developer()->create();
        $this->actingAs($dev)->post("/admin/verification/{$target->id}", ['action' => 'approve'])->assertForbidden();
    }
}
