<?php

namespace Tests\Feature;

use Tests\AccountTestCase;

/**
 * Test that energy consumption is shown in the building overlay at all levels (including level 0).
 * Regression test for bug #1320.
 */
class BuildingOverlayEnergyDisplayTest extends AccountTestCase
{
    /**
     * Test that the metal mine overlay at level 0 shows the "Energy needed" row.
     * Bug #1320: the check !empty($production_current->energy->get()) wrongly returns false
     * when current level is 0 (energy = 0), hiding the energy row.
     */
    public function testEnergyNeededShownAtLevelZero(): void
    {
        // Ensure metal mine is at level 0 (default for new accounts).
        $this->planetSetObjectLevel('metal_mine', 0);

        // Metal mine ID = 1.
        $response = $this->get('ajax/resources?technology=1');

        $response->assertStatus(200);

        $content = $response->getContent();
        if ($content === false) {
            $content = '';
        }

        // Decode JSON response and get the HTML content.
        $json = json_decode($content, true);
        $html = $json['content']['technologydetails'] ?? '';

        $this->assertStringContainsString(
            'additional_energy_consumption',
            $html,
            'Energy needed row should be visible in the metal mine overlay at level 0.'
        );
    }

    /**
     * Test that the metal mine overlay at level 1+ still shows the "Energy needed" row.
     */
    public function testEnergyNeededShownAtLevelOne(): void
    {
        $this->planetSetObjectLevel('metal_mine', 1);

        $response = $this->get('ajax/resources?technology=1');

        $response->assertStatus(200);

        $content = $response->getContent();
        if ($content === false) {
            $content = '';
        }

        $json = json_decode($content, true);
        $html = $json['content']['technologydetails'] ?? '';

        $this->assertStringContainsString(
            'additional_energy_consumption',
            $html,
            'Energy needed row should be visible in the metal mine overlay at level 1.'
        );
    }
}
