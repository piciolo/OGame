<?php

namespace OGame\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OGame\Models\ImportExportOffer;
use OGame\Models\Planet;
use OGame\Models\UserItem;
use OGame\Services\ImportExportActivationService;
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
        private readonly ImportExportActivationService $activation,
    ) {
    }

    /**
     * GET /merchant/import-export — pagina principale.
     */
    public function index(PlayerService $player): View
    {
        $this->setBodyId('traderOverview');

        $user   = $player->getUser();
        $planet = $player->planets->current();

        $offer  = $this->service->getOrCreateOffer($user);
        $offer->loadMissing('item');

        $maxInputs = $this->service->calculateMaxInputs($offer, $planet->getPlanet(), $user);

        $planetsList = $player->planets->all() ?? [];

        return view('ingame.importexport.index', [
            'offer'        => $offer,
            'item'         => $offer->item,
            'currentPlanet' => $planet->getPlanet(),
            'maxInputs'    => $maxInputs,
            'darkMatter'   => $user->dark_matter,
            'honorPoints'  => $user->honor_points,
            'planetsList'  => $planetsList,
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

    /**
     * POST /merchant/import-export/activate — attiva un UserItem dall'inventario.
     */
    public function activate(Request $request, PlayerService $player): JsonResponse
    {
        $request->validate([
            'user_item_id'     => 'required|integer',
            'target_planet_id' => 'nullable|integer',
        ]);

        try {
            $user = $player->getUser();
            $item = UserItem::query()->where('id', $request->integer('user_item_id'))->firstOrFail();
            $this->activation->activate($item, $user, $request->integer('target_planet_id') ?: null);
            return response()->json(['ok' => true]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
