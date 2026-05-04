<?php

namespace OGame\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OGame\Factories\PlanetServiceFactory;
use OGame\Models\Planet;
use OGame\Services\ImportExportService;
use OGame\Services\PlayerService;
use Throwable;

/**
 * Mercante > Import / Export.
 */
class ImportExportController extends OGameController
{
    public function __construct(
        private readonly ImportExportService $service,
    ) {
    }

    /**
     * GET /merchant/import-export — pagina principale.
     */
    public function index(Request $request, PlayerService $player, PlanetServiceFactory $planetServiceFactory): View
    {
        $this->setBodyId('traderOverview');

        $user          = $player->getUser();
        $currentPlanetService = $player->planets->current();
        $currentPlanet = Planet::query()->find($currentPlanetService->getPlanetId());

        $offer = $this->service->getOrCreateOffer($user);
        $offer->loadMissing('item');

        $maxInputs = $this->service->calculateMaxInputs($offer, $currentPlanet, $user);

        // Costruisce dati ricchi per il dropdown (icone biome/image come Battitore,
        // pattern AuctioneerController). Per ogni corpo: id, name, coords, icon path
        // calcolato via PlanetService, risorse correnti per data-attributes.
        $allBodies = Planet::query()->where('user_id', $user->id)->orderBy('id')->get();
        $planets   = [];
        $moons     = [];
        foreach ($allBodies as $b) {
            $svc = $planetServiceFactory->make($b->id);
            $isMoon = (int) $b->planet_type === 3;
            $icon = $isMoon
                ? '/img/moons/small/1.gif'
                : '/img/planets/small/' . $svc->getPlanetBiomeType() . '_' . $svc->getPlanetImageType() . '.png';
            $row = [
                'id'        => (int) $b->id,
                'name'      => $b->name,
                'galaxy'    => $b->galaxy,
                'system'    => $b->system,
                'planet'    => $b->planet,
                'metal'     => (int) floor($b->metal),
                'crystal'   => (int) floor($b->crystal),
                'deuterium' => (int) floor($b->deuterium),
                'icon'      => $icon,
            ];
            if ($isMoon) {
                $moons[] = $row;
            } else {
                $planets[] = $row;
            }
        }

        // Icona del corpo corrente per il toggleLink
        $isMoonCurrent = (int) $currentPlanet->planet_type === 3;
        $currentIcon   = $isMoonCurrent
            ? '/img/moons/small/1.gif'
            : '/img/planets/small/' . $currentPlanetService->getPlanetBiomeType() . '_' . $currentPlanetService->getPlanetImageType() . '.png';

        return view('ingame.importexport.index', [
            'offer'         => $offer,
            'item'          => $offer->item,
            'currentPlanet' => $currentPlanet,
            'currentIcon'   => $currentIcon,
            'maxInputs'     => $maxInputs,
            'darkMatter'    => $user->dark_matter,
            'honorPoints'   => $user->honor_points,
            'planets'       => $planets,
            'moons'         => $moons,
        ]);
    }

    /**
     * POST /merchant/import-export/pay
     */
    public function pay(Request $request, PlayerService $player): RedirectResponse
    {
        $request->validate([
            'planet_id' => 'required|integer',
            'metal'     => 'integer|min:0',
            'crystal'   => 'integer|min:0',
            'deuterium' => 'integer|min:0',
            'honor'     => 'integer|min:0',
        ]);

        try {
            $user   = $player->getUser();
            $offer  = $this->service->getOrCreateOffer($user);
            $planet = Planet::query()
                ->where('id', $request->integer('planet_id'))
                ->where('user_id', $user->id)
                ->firstOrFail();

            $this->service->pay($user, $offer, $planet, [
                'metal'     => (int) $request->input('metal', 0),
                'crystal'   => (int) $request->input('crystal', 0),
                'deuterium' => (int) $request->input('deuterium', 0),
                'honor'     => (int) $request->input('honor', 0),
            ]);

            return redirect()->route('importexport.index')->with('success', __('t_ingame.import_export.bought_success'));
        } catch (Throwable $e) {
            return redirect()->route('importexport.index')->with('error', $e->getMessage());
        }
    }

    /**
     * POST /merchant/import-export/change — skip via DM.
     */
    public function change(PlayerService $player): RedirectResponse
    {
        try {
            $user  = $player->getUser();
            $offer = $this->service->getOrCreateOffer($user);
            $this->service->change($user, $offer);
            return redirect()->route('importexport.index');
        } catch (Throwable $e) {
            return redirect()->route('importexport.index')->with('error', $e->getMessage());
        }
    }

    /**
     * POST /merchant/import-export/take — acquisto diretto via 500 DM.
     */
    public function take(PlayerService $player): RedirectResponse
    {
        try {
            $user  = $player->getUser();
            $offer = $this->service->getOrCreateOffer($user);
            $this->service->takeWithDm($user, $offer);
            return redirect()->route('importexport.index')->with('success', __('t_ingame.import_export.bought_success'));
        } catch (Throwable $e) {
            return redirect()->route('importexport.index')->with('error', $e->getMessage());
        }
    }

}
