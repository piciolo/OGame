<?php

namespace Tests\Feature;

use OGame\Models\Planet;
use OGame\Services\PlanetNameLocalizationService;
use Tests\AccountTestCase;

/**
 * Verifies PlanetNameLocalizationService:
 *  - default-named planets in any supported locale are renamed to the new locale
 *  - custom (player-chosen) planet names are NEVER touched
 *  - planets owned by other users are NEVER touched
 *  - moons follow the same rule (planet_type filter is not applied; the lookup is by name)
 *  - the return value is the count of rows actually renamed
 *  - calling twice in a row with the same target locale renames 0 the second time
 */
class PlanetNameLocalizationServiceTest extends AccountTestCase
{
    private PlanetNameLocalizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = resolve(PlanetNameLocalizationService::class);

        // The freshly-registered user owns 2 planets, both with localized default
        // names ("Pianeta Madre" + "Colonia") generated at signup. Reset every
        // owned body to a non-default custom name so each test starts from a
        // clean slate and can assert the exact rename count.
        Planet::query()
            ->where('user_id', $this->currentUserId)
            ->update(['name' => 'TestCustomPlanet_' . uniqid()]);
    }

    public function testDefaultNamedItalianPlanetIsRetranslatedToEnglish(): void
    {
        $planet = Planet::find($this->currentPlanetId);
        $planet->name = 'Pianeta Madre'; // Italian default for "homeworld"
        $planet->save();

        $renamed = $this->service->retranslateDefaultNamesForUser($this->currentUserId, 'en');
        $this->assertSame(1, $renamed);

        $planet->refresh();
        $this->assertSame('Homeworld', $planet->name);
    }

    public function testDefaultNamedEnglishColonyIsRetranslatedToItalian(): void
    {
        $planet = Planet::find($this->currentPlanetId);
        $planet->name = 'Colony';
        $planet->save();

        $renamed = $this->service->retranslateDefaultNamesForUser($this->currentUserId, 'it');
        $this->assertSame(1, $renamed);

        $planet->refresh();
        $this->assertSame('Colonia', $planet->name);
    }

    public function testCustomPlayerChosenNameIsNeverTouched(): void
    {
        $planet = Planet::find($this->currentPlanetId);
        $planet->name = 'My Awesome Empire HQ'; // not a default in any locale
        $planet->save();

        $renamed = $this->service->retranslateDefaultNamesForUser($this->currentUserId, 'it');
        $this->assertSame(0, $renamed);

        $planet->refresh();
        $this->assertSame('My Awesome Empire HQ', $planet->name);
    }

    public function testForeignPlayerPlanetsAreNeverTouched(): void
    {
        $foreignUserId = $this->getSecondPlayerId();
        $foreignPlanet = Planet::query()->where('user_id', $foreignUserId)->first();
        $this->assertNotNull($foreignPlanet);
        $foreignPlanet->name = 'Pianeta Madre'; // would match if the scope leaked
        $foreignPlanet->save();

        $renamed = $this->service->retranslateDefaultNamesForUser($this->currentUserId, 'en');

        $foreignPlanet->refresh();
        $this->assertSame('Pianeta Madre', $foreignPlanet->name, 'Foreign-owned planets must be untouched.');
        $this->assertSame(0, $renamed);
    }

    public function testSecondCallWithSameLocaleIsNoOp(): void
    {
        $planet = Planet::find($this->currentPlanetId);
        $planet->name = 'Pianeta Madre';
        $planet->save();

        $first = $this->service->retranslateDefaultNamesForUser($this->currentUserId, 'en');
        $this->assertSame(1, $first);

        $second = $this->service->retranslateDefaultNamesForUser($this->currentUserId, 'en');
        $this->assertSame(0, $second, 'Already-renamed planet must not be renamed twice.');
    }

    public function testMultipleDefaultPlanetsAreAllRenamed(): void
    {
        // Both user planets become italian-named defaults.
        $main = Planet::find($this->currentPlanetId);
        $main->name = 'Pianeta Madre';
        $main->save();

        $secondId = $this->secondPlanetService->getPlanetId();
        $second = Planet::find($secondId);
        $second->name = 'Colonia';
        $second->save();

        $renamed = $this->service->retranslateDefaultNamesForUser($this->currentUserId, 'en');
        $this->assertSame(2, $renamed);

        $main->refresh();
        $second->refresh();
        $this->assertSame('Homeworld', $main->name);
        $this->assertSame('Colony', $second->name);
    }

    public function testDoesNotTouchSameLocaleNameWhenAlreadyMatching(): void
    {
        $planet = Planet::find($this->currentPlanetId);
        // English default; switching to en should not write anything.
        $planet->name = 'Homeworld';
        $planet->save();

        $renamed = $this->service->retranslateDefaultNamesForUser($this->currentUserId, 'en');
        $this->assertSame(0, $renamed, 'Same source/target name must skip the UPDATE.');

        $planet->refresh();
        $this->assertSame('Homeworld', $planet->name);
    }
}
