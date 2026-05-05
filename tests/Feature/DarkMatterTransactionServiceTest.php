<?php

namespace Tests\Feature;

use OGame\Enums\DarkMatterTransactionType;
use OGame\Models\DarkMatterTransaction;
use OGame\Models\User;
use OGame\Services\DarkMatterTransactionService;
use Tests\AccountTestCase;

/**
 * Verifies DarkMatterTransactionService:
 *  - recordTransaction persists every column the audit trail relies on
 *  - getHistory returns only the current user's rows ordered by created_at DESC
 *  - getHistory honours the optional type filter (ofType scope)
 *  - getHistory honours the limit parameter
 *  - getStatistics splits earned/spent and counts every transaction
 *  - getStatistics is correctly scoped to the user (no leakage)
 */
class DarkMatterTransactionServiceTest extends AccountTestCase
{
    private DarkMatterTransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = resolve(DarkMatterTransactionService::class);

        // Defensive isolation: wipe any rows for the freshly created user.
        DarkMatterTransaction::query()->where('user_id', $this->currentUserId)->delete();
    }

    private function user(): User
    {
        return User::findOrFail($this->currentUserId);
    }

    public function testRecordTransactionPersistsAllFields(): void
    {
        $tx = $this->service->recordTransaction(
            $this->user(),
            -10000,
            DarkMatterTransactionType::SPEEDUP->value,
            'Speed up: build queue',
            5000
        );

        $this->assertSame($this->currentUserId, (int) $tx->user_id);
        $this->assertSame(-10000, (int) $tx->amount);
        $this->assertSame('speedup', $tx->type);
        $this->assertSame('Speed up: build queue', $tx->description);
        $this->assertSame(5000, (int) $tx->balance_after);

        $this->assertDatabaseHas('dark_matter_transactions', [
            'id' => $tx->id,
            'user_id' => $this->currentUserId,
            'amount' => -10000,
            'type' => 'speedup',
        ]);
    }

    public function testGetHistoryReturnsOnlyCurrentUserRowsOrderedDesc(): void
    {
        $this->service->recordTransaction($this->user(), 100, DarkMatterTransactionType::INITIAL_BONUS->value, 'first', 100);
        $this->travel(2)->seconds();
        $this->service->recordTransaction($this->user(), -50, DarkMatterTransactionType::EXPEDITION->value, 'second', 50);
        $this->travel(2)->seconds();
        $this->service->recordTransaction($this->user(), 200, DarkMatterTransactionType::EXPEDITION->value, 'third', 250);

        // Foreign user's transaction must NOT appear.
        $foreignUserId = $this->getSecondPlayerId();
        DarkMatterTransaction::query()->where('user_id', $foreignUserId)->delete();
        $foreignUser = User::find($foreignUserId);
        $this->service->recordTransaction($foreignUser, 999, DarkMatterTransactionType::INITIAL_BONUS->value, 'foreign', 999);

        $history = $this->service->getHistory($this->user());
        $this->assertCount(3, $history);
        $this->assertSame('third', $history[0]->description);
        $this->assertSame('second', $history[1]->description);
        $this->assertSame('first', $history[2]->description);
    }

    public function testGetHistoryFiltersByType(): void
    {
        $this->service->recordTransaction($this->user(), 100, DarkMatterTransactionType::INITIAL_BONUS->value, 'bonus', 100);
        $this->service->recordTransaction($this->user(), -50, DarkMatterTransactionType::EXPEDITION->value, 'exp1', 50);
        $this->service->recordTransaction($this->user(), -25, DarkMatterTransactionType::EXPEDITION->value, 'exp2', 25);

        $exped = $this->service->getHistory($this->user(), DarkMatterTransactionType::EXPEDITION->value);
        $this->assertCount(2, $exped);
        foreach ($exped as $row) {
            $this->assertSame('expedition', $row->type);
        }
    }

    public function testGetHistoryHonoursLimit(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->service->recordTransaction($this->user(), 1, DarkMatterTransactionType::REGENERATION->value, "tx{$i}", $i);
            $this->travel(1)->seconds();
        }

        $first3 = $this->service->getHistory($this->user(), null, 3);
        $this->assertCount(3, $first3);
    }

    public function testGetStatisticsSeparatesEarnedSpentAndCounts(): void
    {
        $this->service->recordTransaction($this->user(), 1000, DarkMatterTransactionType::INITIAL_BONUS->value, 'bonus', 1000);
        $this->service->recordTransaction($this->user(),  500, DarkMatterTransactionType::EXPEDITION->value, 'exp gain', 1500);
        $this->service->recordTransaction($this->user(), -200, DarkMatterTransactionType::SPEEDUP->value, 'officer', 1300);
        $this->service->recordTransaction($this->user(), -300, DarkMatterTransactionType::SPEEDUP->value, 'officer', 1000);

        $stats = $this->service->getStatistics($this->user());
        $this->assertSame(1500, $stats['total_earned']);
        $this->assertSame(500, $stats['total_spent']); // abs of -500
        $this->assertSame(4, $stats['transaction_count']);
    }

    public function testGetStatisticsIsScopedToUser(): void
    {
        $this->service->recordTransaction($this->user(), 100, DarkMatterTransactionType::INITIAL_BONUS->value, 'mine', 100);

        // Foreign user's transactions must not leak into our stats.
        $foreignUserId = $this->getSecondPlayerId();
        DarkMatterTransaction::query()->where('user_id', $foreignUserId)->delete();
        $foreignUser = User::find($foreignUserId);
        $this->service->recordTransaction($foreignUser, 999999, DarkMatterTransactionType::INITIAL_BONUS->value, 'foreign', 999999);

        $stats = $this->service->getStatistics($this->user());
        $this->assertSame(100, $stats['total_earned']);
        $this->assertSame(1, $stats['transaction_count']);
    }
}
