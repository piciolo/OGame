<?php

namespace OGame\Services;

use Illuminate\Support\Carbon;
use OGame\Enums\HighscoreTypeEnum;
use OGame\Models\PlayerRankHistory;

class PlayerRankHistoryService
{
    /**
     * Salva (o aggiorna) lo snapshot di rank+punti per un player/tipo nella data odierna.
     */
    public function recordSnapshot(int $playerId, HighscoreTypeEnum $type, int $rank, int $points, ?Carbon $date = null): void
    {
        $date = ($date ?? Carbon::today())->toDateString();

        PlayerRankHistory::updateOrCreate(
            [
                'player_id' => $playerId,
                'highscore_type' => $type->value,
                'snapshot_date' => $date,
            ],
            [
                'rank' => $rank,
                'points' => $points,
            ]
        );
    }

    /**
     * Calcola il delta di rank rispetto allo snapshot precedente disponibile.
     *
     * Ritorna ['change' => 'up'|'down'|'point', 'delta' => int].
     * - 'up'   = rank migliorato (numero più basso). delta = differenza in posizioni (positiva).
     * - 'down' = rank peggiorato.
     * - 'point' = nessuna variazione o nessuno storico precedente.
     *
     * @return array{change:string, delta:int}
     */
    public function getRankDelta(int $playerId, HighscoreTypeEnum $type, int $currentRank): array
    {
        if ($currentRank <= 0) {
            return ['change' => 'point', 'delta' => 0];
        }

        $previous = PlayerRankHistory::where('player_id', $playerId)
            ->where('highscore_type', $type->value)
            ->where('snapshot_date', '<', Carbon::today()->toDateString())
            ->orderByDesc('snapshot_date')
            ->first();

        if (!$previous || $previous->rank <= 0) {
            return ['change' => 'point', 'delta' => 0];
        }

        // In OGame "up" = rank numero più basso (migliore). Il delta esposto è positivo.
        $diff = $previous->rank - $currentRank;
        if ($diff > 0) {
            return ['change' => 'up', 'delta' => $diff];
        }
        if ($diff < 0) {
            return ['change' => 'down', 'delta' => abs($diff)];
        }
        return ['change' => 'point', 'delta' => 0];
    }
}
