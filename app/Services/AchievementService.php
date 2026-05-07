<?php

namespace OGame\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use OGame\Models\Achievement;
use OGame\Models\PlayerAchievementProgress;
use OGame\Models\PlayerUnlockedAvatar;
use OGame\Models\PlayerUnlockedSkin;
use OGame\Models\PlayerUnlockedTitle;

class AchievementService
{
    /**
     * Lista degli achievement con progresso del giocatore (usata per la tab Riepilogo).
     *
     * @return Collection<int, array{achievement:Achievement, progress:PlayerAchievementProgress|null, tiers:array}>
     */
    public function getAchievementsForPlayer(PlayerService $player): Collection
    {
        $achievements = Achievement::with('tiers')
            ->where('is_secret', false)
            ->orderBy('sort_order')
            ->orderBy('display_number')
            ->get();

        $progressByAchievement = PlayerAchievementProgress::where('player_id', $player->getId())
            ->get()
            ->keyBy('achievement_id');

        return $achievements->map(function (Achievement $a) use ($progressByAchievement) {
            return [
                'achievement' => $a,
                'progress' => $progressByAchievement->get($a->id),
                'tiers' => $a->tiers,
            ];
        });
    }

    /**
     * Avatar sbloccati dal giocatore (per la tab Avatar).
     *
     * @return array<int, string>  array di machine_name
     */
    public function getUnlockedAvatars(PlayerService $player): array
    {
        return PlayerUnlockedAvatar::where('player_id', $player->getId())
            ->pluck('avatar_machine_name')
            ->all();
    }

    /**
     * Skin pianeta sbloccate.
     *
     * @return array<int, string>
     */
    public function getUnlockedSkins(PlayerService $player): array
    {
        return PlayerUnlockedSkin::where('player_id', $player->getId())
            ->pluck('skin_machine_name')
            ->all();
    }

    /**
     * Titoli sbloccati.
     *
     * @return array<int, string>
     */
    public function getUnlockedTitles(PlayerService $player): array
    {
        return PlayerUnlockedTitle::where('player_id', $player->getId())
            ->pluck('title_machine_name')
            ->all();
    }

    /**
     * Incrementa il valore di progresso di un achievement per un giocatore.
     * Se il valore raggiunge il target di un nuovo tier, sblocca la ricompensa
     * e aggiorna `completed_tier`.
     *
     * @param int $newValue valore assoluto (NON delta) del contatore di progresso.
     * @return PlayerAchievementProgress
     */
    public function setProgress(PlayerService $player, Achievement $achievement, int $newValue): PlayerAchievementProgress
    {
        $progress = PlayerAchievementProgress::firstOrCreate(
            ['player_id' => $player->getId(), 'achievement_id' => $achievement->id],
            ['current_value' => 0, 'completed_tier' => 0]
        );

        if ($newValue <= $progress->current_value) {
            // Nessun avanzamento.
            $progress->last_updated_at = Carbon::now();
            $progress->save();
            return $progress;
        }

        $progress->current_value = $newValue;
        $progress->last_updated_at = Carbon::now();

        // Verifica se si sono completati nuovi tier.
        $tiers = $achievement->tiers->sortBy('tier');
        foreach ($tiers as $tier) {
            if ($tier->tier <= $progress->completed_tier) {
                continue;
            }
            if ($newValue >= $tier->target) {
                $this->unlockReward($player, $tier->reward_type, $tier->reward_machine_name);
                $progress->completed_tier = $tier->tier;
                if ($tier->tier === $tiers->last()->tier) {
                    $progress->completed_at = Carbon::now();
                }
            } else {
                break; // tier successivi non raggiunti
            }
        }

        $progress->save();
        return $progress;
    }

    /**
     * Sblocca una ricompensa generica per il giocatore.
     */
    public function unlockReward(PlayerService $player, string $rewardType, string $machineName): void
    {
        $now = Carbon::now();
        $playerId = $player->getId();
        match ($rewardType) {
            'avatar' => PlayerUnlockedAvatar::firstOrCreate(
                ['player_id' => $playerId, 'avatar_machine_name' => $machineName],
                ['unlocked_at' => $now]
            ),
            'skin' => PlayerUnlockedSkin::firstOrCreate(
                ['player_id' => $playerId, 'skin_machine_name' => $machineName],
                ['unlocked_at' => $now]
            ),
            'title' => PlayerUnlockedTitle::firstOrCreate(
                ['player_id' => $playerId, 'title_machine_name' => $machineName],
                ['unlocked_at' => $now]
            ),
            default => null,
        };
    }

    /**
     * Verifica se un asset è sbloccato dal giocatore.
     */
    public function isUnlocked(PlayerService $player, string $rewardType, string $machineName): bool
    {
        $playerId = $player->getId();
        return match ($rewardType) {
            'avatar' => PlayerUnlockedAvatar::where('player_id', $playerId)->where('avatar_machine_name', $machineName)->exists(),
            'skin' => PlayerUnlockedSkin::where('player_id', $playerId)->where('skin_machine_name', $machineName)->exists(),
            'title' => PlayerUnlockedTitle::where('player_id', $playerId)->where('title_machine_name', $machineName)->exists(),
            default => false,
        };
    }
}
