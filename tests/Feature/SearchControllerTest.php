<?php

namespace Tests\Feature;

use OGame\Models\Alliance;
use OGame\Models\Planet;
use OGame\Models\User;
use OGame\Services\AllianceService;
use Tests\AccountTestCase;

/**
 * HTTP end-to-end tests for the player/planet/alliance search overlay:
 *  - GET /overlay/search renders the overlay view (200)
 *  - POST /ajax/search with empty searchtext returns an explicit error payload
 *  - category=2 (players) returns username matches with shape {id,name,type:'player'}
 *  - category=3 (planets) matches by planet name and excludes moons
 *  - category=3 returns the documented "[g:s:p]" coordinates string
 *  - category=4 (alliances) matches by alliance_name OR alliance_tag
 *  - category=4 returns the documented payload (id/name/tag/member_count/rank/points/is_open/type)
 *  - unknown category returns an empty results set with success status
 *
 * Note: this branch contains a small fix to searchPlanets() — the controller used
 * to query the non-existent column `planet_name`; corrected to `name`.
 */
class SearchControllerTest extends AccountTestCase
{
    public function testOverlayReturnsTwoHundred(): void
    {
        $response = $this->get(route('search.overlay'));
        $response->assertStatus(200);
        $response->assertViewIs('ingame.search.overlay');
    }

    public function testEmptySearchTextReturnsErrorPayload(): void
    {
        $response = $this->postJson(route('search.ajax'), [
            'searchtext' => '',
            'category'   => 2,
        ]);
        $response->assertStatus(200);
        $response->assertJson([
            'status'  => 'error',
            'results' => [],
        ]);
    }

    public function testSearchPlayersFindsCurrentUserByExactUsername(): void
    {
        $user = User::find($this->currentUserId);
        // Use the full username — random per AccountTestCase, so the result set fits in limit=50.
        $response = $this->postJson(route('search.ajax'), [
            'searchtext' => $user->username,
            'category'   => 2,
        ]);
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success', 'category' => 2]);

        $results = $response->json('results');
        $this->assertNotEmpty($results);
        $matched = collect($results)->firstWhere('id', $user->id);
        $this->assertNotNull($matched, 'Current user must be in player search results.');
        $this->assertSame('player', $matched['type']);
        $this->assertSame($user->username, $matched['name']);
    }

    public function testSearchPlayersWithNonMatchingNeedleReturnsEmptyForCurrentUser(): void
    {
        $unique = 'NoSuchPlayer' . substr(md5(uniqid()), 0, 10);
        $response = $this->postJson(route('search.ajax'), [
            'searchtext' => $unique,
            'category'   => 2,
        ]);
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success', 'category' => 2]);
        $this->assertEmpty($response->json('results'), 'A unique-by-construction needle must yield no results.');
    }

    public function testSearchPlanetsFindsByPlanetName(): void
    {
        // Rename current planet to a deterministic, easily searchable string.
        $unique = 'Atrebla' . substr(md5(uniqid()), 0, 6);
        $planet = Planet::find($this->currentPlanetId);
        $planet->name = $unique;
        $planet->save();

        $response = $this->postJson(route('search.ajax'), [
            'searchtext' => $unique,
            'category'   => 3,
        ]);
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success', 'category' => 3]);

        $results = $response->json('results');
        $this->assertNotEmpty($results);
        $matched = collect($results)->firstWhere('id', $this->currentPlanetId);
        $this->assertNotNull($matched);
        $this->assertSame('planet', $matched['type']);
        $this->assertSame($this->currentUserId, (int) $matched['owner_id']);
        $this->assertMatchesRegularExpression('/^\[\d+:\d+:\d+\]$/', $matched['coordinates']);
    }

    public function testSearchPlanetsExcludesMoons(): void
    {
        $needle = 'NoMoon' . substr(md5(uniqid()), 0, 6);

        $planet = Planet::find($this->currentPlanetId);
        $planet->name = $needle;
        $planet->save();

        $response = $this->postJson(route('search.ajax'), [
            'searchtext' => $needle,
            'category'   => 3,
        ]);
        $response->assertStatus(200);

        foreach ($response->json('results') as $row) {
            $row_planet = Planet::find($row['id']);
            $this->assertSame(1, (int) $row_planet->planet_type, 'Search results must only contain planets.');
        }
    }

    public function testSearchAlliancesByName(): void
    {
        $allianceService = resolve(AllianceService::class);
        $tag  = 'STG' . substr(md5(uniqid((string) mt_rand(), true)), 0, 5);
        $name = 'UniqueSearchableName' . substr(md5(uniqid()), 0, 8);
        $alliance = $allianceService->createAlliance($this->currentUserId, $tag, $name);

        $response = $this->postJson(route('search.ajax'), [
            'searchtext' => 'UniqueSearchableName',
            'category'   => 4,
        ]);
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success', 'category' => 4]);

        $matched = collect($response->json('results'))->firstWhere('id', $alliance->id);
        $this->assertNotNull($matched);
        $this->assertSame($name, $matched['name']);
        $this->assertSame($tag, $matched['tag']);
        $this->assertSame('alliance', $matched['type']);
        $this->assertArrayHasKey('member_count', $matched);
        $this->assertArrayHasKey('rank', $matched);
        $this->assertArrayHasKey('points', $matched);
        $this->assertArrayHasKey('is_open', $matched);
    }

    public function testSearchAlliancesByTag(): void
    {
        $allianceService = resolve(AllianceService::class);
        $tag  = 'TG' . substr(md5(uniqid()), 0, 5);
        $name = 'Some Other Name ' . substr(md5(uniqid()), 0, 6);
        $alliance = $allianceService->createAlliance($this->currentUserId, $tag, $name);

        $response = $this->postJson(route('search.ajax'), [
            'searchtext' => $tag,
            'category'   => 4,
        ]);
        $response->assertStatus(200);

        $matched = collect($response->json('results'))->firstWhere('id', $alliance->id);
        $this->assertNotNull($matched, 'Alliance must also be findable by its tag.');
    }

    public function testUnknownCategoryReturnsEmptyResults(): void
    {
        $response = $this->postJson(route('search.ajax'), [
            'searchtext' => 'anything',
            'category'   => 999,
        ]);
        $response->assertStatus(200);
        $response->assertJson([
            'status'   => 'success',
            'category' => 999,
            'results'  => [],
        ]);
    }
}
