<?php

namespace OGame\Enums;

/**
 * Alliance Class — set by the alliance founder/leader, applies to all members.
 *
 * Source: OGame ufficiale (s274-it.ogame.gameforge.com), tab "Classi Alleanza".
 * Verified bonuses (testo italiano ufficiale):
 *
 *   id=1 Guerriero (Alleanza)
 *     - Velocità delle navi per i voli tra i giocatori dell'alleanza: +10%
 *     - Ricerche militari: +1 livelli
 *     - Ricerche di spionaggio: +1 livelli
 *     - Lo Spionaggio del sistema permette di scansionare l'intero sistema.
 *
 *   id=2 Mercante (Alleanza)
 *     - Velocità Cargo: +10%
 *     - Produzione miniere: +5%
 *     - Produzione di energia: +5%
 *     - Capienza del deposito dei pianeti: +10%
 *     - Capienza del deposito delle lune: +10%
 *
 *   id=3 Ricercatore (Alleanza)
 *     - Pianeti più grandi (+5%) quando si colonizza
 *     - Velocità di volo verso la destinazione della spedizione: +10%
 *     - La Falange del sistema permette di scansionare i movimenti delle flotte nell'intero sistema.
 *
 * Activation rules (verified on official UI):
 *   - Cost per change: 500.000 MO
 *   - First activation FREE, but only after 14 days from alliance creation
 *     ("Attiva gratis: Devi attendere fino al ..." tooltip)
 *   - Bonus applies to ALL members of the alliance
 *   - Bonus is permanent until class is changed
 */
enum AllianceClass: int
{
    case WARRIOR = 1;
    case TRADER = 2;
    case RESEARCHER = 3;

    public function getName(): string
    {
        return match ($this) {
            self::WARRIOR => __('t_ingame.alliance.class_warrior'),
            self::TRADER => __('t_ingame.alliance.class_trader'),
            self::RESEARCHER => __('t_ingame.alliance.class_researcher'),
        };
    }

    /**
     * CSS sprite class name.
     */
    public function getMachineName(): string
    {
        return match ($this) {
            self::WARRIOR => 'warrior',
            self::TRADER => 'trader',
            self::RESEARCHER => 'explorer',
        };
    }

    public function getChangeCost(): int
    {
        return 500000;
    }

    /**
     * Days an alliance must exist before the founder can activate the
     * first class for free. Verified on official UI tooltip.
     */
    public static function getFreeActivationDelayDays(): int
    {
        return 14;
    }

    /**
     * Localised lang keys (lookup in t_ingame.alliance) for bonus list.
     *
     * @return array<int, string>
     */
    public function getBonusLangKeys(): array
    {
        return match ($this) {
            self::WARRIOR => [
                't_ingame.alliance.warrior_bonus_1',
                't_ingame.alliance.warrior_bonus_2',
                't_ingame.alliance.warrior_bonus_3',
                't_ingame.alliance.warrior_bonus_4',
            ],
            self::TRADER => [
                't_ingame.alliance.trader_bonus_1',
                't_ingame.alliance.trader_bonus_2',
                't_ingame.alliance.trader_bonus_3',
                't_ingame.alliance.trader_bonus_4',
                't_ingame.alliance.trader_bonus_5',
            ],
            self::RESEARCHER => [
                't_ingame.alliance.researcher_bonus_1',
                't_ingame.alliance.researcher_bonus_2',
                't_ingame.alliance.researcher_bonus_3',
            ],
        };
    }
}
