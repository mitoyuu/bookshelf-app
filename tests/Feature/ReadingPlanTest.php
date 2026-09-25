<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_未認証ユーザーは読書計画一覧にアクセスできない(): void
    {
        $response = $this->get(route('reading-plans.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_認証済みユーザーは自分の読書計画一覧を表示できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(7),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index'));

        $response->assertOk();
        $response->assertViewIs('reading-plans.index');
        $response->assertViewHas('readingPlans');
    }

    public function test_他のユーザーの読書計画は一覧に表示されない(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create();
        $otherBook = Book::factory()->create();

        $myReadingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(7),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $otherReadingPlan = ReadingPlan::create([
            'user_id' => $otherUser->id,
            'book_id' => $otherBook->id,
            'target_date' => now()->addDays(7),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index'));

        $response->assertOk();

        $readingPlans = $response->viewData('readingPlans');

        $this->assertTrue($readingPlans->contains($myReadingPlan));
        $this->assertFalse($readingPlans->contains($otherReadingPlan));
    }

    public function test_認証済みユーザーは読書計画を作成できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $targetDate = now()->addDays(7)->toDateString();

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => $targetDate,
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $response->assertSessionHas(
            'success',
            '読書計画を作成しました。'
        );

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate,
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
    }

    public function test_書籍と期限を指定しない場合はバリデーションエラーになる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), []);

        $response->assertSessionHasErrors([
            'book_id' => '書籍は必須です。',
            'target_date' => '期日は必須です。',
        ]);
    }

    public function test_同じユーザーは同じ本の進行中の読書計画を重複して作成できない(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(7),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => now()->addDays(14)->toDateString(),
            ]);

        $response->assertSessionHasErrors('book_id');

        $this->assertDatabaseCount('reading_plans', 1);
    }

    public function test_期限に日付以外を指定した場合はバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => '日付ではありません',
            ]);

        $response->assertSessionHasErrors([
            'target_date',
        ]);

        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_読書計画の所有者は自分の読書計画の編集画面を表示できる()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(7),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.edit', $readingPlan));

        $response->assertOk();
        $response->assertViewIs('reading-plans.edit');
    }

    public function test_読書計画の所有者は自分の読書計画を更新できる()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(7),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $newTargetDate = now()->addDays(14)->toDateString();

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => $newTargetDate,
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $response->assertSessionHas(
            'success',
            '読書計画を更新しました。'
        );

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => $newTargetDate,
        ]);
    }

    public function test_他人の読書計画は編集・更新できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $otherUser->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(7),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $newTargetDate = now()->addDays(14)->toDateString();

        $editResponse = $this->actingAs($user)
            ->get(route('reading-plans.edit', $readingPlan));

        $editResponse->assertForbidden();

        $updateResponse = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => $newTargetDate,
            ]);

        $updateResponse->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => now()->addDays(7)->toDateString(),
        ]);
    }

    public function test_読書計画の所有者は自分の読書計画を削除できる()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(7),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));

        $response->assertSessionHas(
            'success',
            '読書計画を削除しました。'
        );

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }

    public function test_他人の読書計画は削除できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $otherUser->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(7),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }

    public function test_読書計画の所有者は自分の読書計画を完了に変更できる()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(7),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));

        $response->assertSessionHas(
            'success',
            '読書計画を完了しました。'
        );

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);

        $this->assertNotNull($readingPlan->fresh()->completed_at);
    }

    public function test_他人の読書計画は完了に変更できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $otherUser->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(7),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
    }

    public function test_期日の3日前の読書計画にはリマインダー通知が作成される()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $this->artisan('reading-plan:batch')
            ->assertSuccessful();

        $notification = $user->notifications()
            ->where('type', ReadingPlanNotification::class)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame(
            $readingPlan->id,
            $notification->data['reading_plan_id']
        );
        $this->assertSame(
            'three_days_before',
            $notification->data['timing']
        );
    }

    public function test_同じ3日前通知は重複して作成されない()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $this->artisan('reading-plan:batch')
            ->assertSuccessful();

        $this->artisan('reading-plan:batch')
            ->assertSuccessful();

        $notificationCount = $user->notifications()
            ->where('type', ReadingPlanNotification::class)
            ->where('data->reading_plan_id', $readingPlan->id)
            ->where('data->timing', 'three_days_before')
            ->count();

        $this->assertSame(1, $notificationCount);
    }

    public function test_期日当日の読書計画にはリマインダー通知が作成される()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $this->artisan('reading-plan:batch')
            ->assertSuccessful();

        $notification = $user->notifications()
            ->where('type', ReadingPlanNotification::class)
            ->where('data->reading_plan_id', $readingPlan->id)
            ->first();

        $this->assertNotNull($notification);

        $this->assertSame(
            'on_due_date',
            $notification->data['timing']
        );
    }

    public function test_期日の3日後の期限切れ読書計画には再エンゲージメント通知が作成される()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->subDays(3)->toDateString(),
            'status' => ReadingPlanStatus::Expired,
            'completed_at' => null,
        ]);

        $this->artisan('reading-plan:batch')
            ->assertSuccessful();

        $notification = $user->notifications()
            ->where('type', ReadingPlanNotification::class)
            ->where('data->reading_plan_id', $readingPlan->id)
            ->first();

        $this->assertNotNull($notification);

        $this->assertSame(
            'three_days_after',
            $notification->data['timing']
        );

        $this->assertSame(
            '読書計画 — 期限超過3日経過',
            $notification->data['title']
        );

        $this->assertSame(
            "『{$book->title}』の期限から3日が経過しました。\n読了済みなら完了登録、続けるなら期限を変更してください。",
            $notification->data['body']
        );
    }

    public function test_期限を過ぎた進行中の読書計画は期限切れになる()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->subDay()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $this->artisan('reading-plan:batch')
            ->assertSuccessful();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::Expired->value,
        ]);
    }

    public function test_完了済みの読書計画は期限を過ぎても期限切れにならない()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->subDay()->toDateString(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now()->subDay(),
        ]);

        $this->artisan('reading-plan:batch')
            ->assertSuccessful();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);
    }

    public function test_完了済みの読書計画には期限超過3日後の通知が作成されない()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->subDays(3)->toDateString(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now()->subDays(3),
        ]);

        $this->artisan('reading-plan:batch')
            ->assertSuccessful();

        $notificationCount = $user->notifications()
            ->where('type', ReadingPlanNotification::class)
            ->where('data->reading_plan_id', $readingPlan->id)
            ->where('data->timing', 'three_days_after')
            ->count();

        $this->assertSame(0, $notificationCount);
    }
}
