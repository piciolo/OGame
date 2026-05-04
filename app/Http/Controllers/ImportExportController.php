<?php

namespace OGame\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
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
    public function index(Request $request, PlayerService $player): View
    {
        $this->setBodyId('traderOverview');

        $user = $player->getUser();

        // Sorgente selezionata: query string ?planet_id=N (default = pianeta corrente)
        $requestedPlanetId = (int) $request->query('planet_id', 0);
        if ($requestedPlanetId > 0) {
            $currentPlanet = Planet::query()->where('id', $requestedPlanetId)->where('user_id', $user->id)->first();
        }
        if (empty($currentPlanet)) {
            $currentPlanet = Planet::query()->find($player->planets->current()->getPlanetId());
        }

        $offer = $this->service->getOrCreateOffer($user);
        $offer->loadMissing('item');

        $maxInputs = $this->service->calculateMaxInputs($offer, $currentPlanet, $user);

        // Lista corpi del giocatore (planets + moons separati per i tab)
        $allBodies = Planet::query()->where('user_id', $user->id)->orderBy('id')->get();
        $planets = $allBodies->where('planet_type', 1)->values();
        $moons   = $allBodies->where('planet_type', 3)->values();

        return view('ingame.importexport.index', [
            'offer'         => $offer,
            'item'          => $offer->item,
            'currentPlanet' => $currentPlanet,
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
