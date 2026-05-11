<?php

namespace OGame\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use OGame\Enums\DarkMatterTransactionType;
use OGame\Models\Resources;
use RuntimeException;

/**
 * Procura risorse — Resource Market "Buy Resources" tab.
 *
 * Replicates OGame ufficiale's productionBasedPackages mechanic: the player can spend
 * Dark Matter to buy a package equal to up to one day of the current planet's hourly
 * production, capped at the storage headroom of that resource on that planet. Per the
 * OGame backend, the unit price in DM is a fixed per-resource coefficient (universe-
 * wide constant, independent of mine level / character class / officers). The package
 * quantity scales with production; the unit cost does not.
 *
 * Anti-tamper: the server ALWAYS recomputes both Q and P from authoritative planet
 * state. Client-supplied price/quantity is never trusted — only the package_type.
 */
class BuyResourcesService
{
    /**
     * Per-unit DM cost coefficients confirmed against the live ufficiale UI
     * (snapshot from a real planet on s275-it: derived empirically and validated
     * by an OGame expert as universe-wide constants for the standard OGame ratio).
     *
     * Future: move to a `universe_settings` table once we support per-universe pricing.
     */
    public const COEFFICIENTS = [
        'metal'     => 0.153856,
        'crystal'   => 0.650939,
        'deuterium' => 2.477391,
    ];

    /**
     * Minimum DM cost per buy action. Floor for tiny packages (mirrors OGame's
     * `data-min-premium-costs="500"` attribute on the buy buttons).
     */
    public const MIN_COST_DM = 500;

    /**
     * Valid package keys the client may request.
     */
    public const PACKAGE_METAL     = 'metal';
    public const PACKAGE_CRYSTAL   = 'crystal';
    public const PACKAGE_DEUTERIUM = 'deuterium';
    public const PACKAGE_ALL       = 'allLocalResources';

    /**
     * Compute a single-resource package: how much would be credited and how many DM
     * it would cost, given the current planet's production and storage headroom.
     *
     * @return array{
     *     resource: string,
     *     amount: int,
     *     daily_production: int,
     *     storage_headroom: int,
     *     is_capped: bool,
     *     cost_dm: int,
     *     coefficient: float
     * }
     */
    public function calculatePackage(PlanetService $planet, string $resource): array
    {
        if (!isset(self::COEFFICIENTS[$resource])) {
            throw new \InvalidArgumentException("Invalid resource: {$resource}");
        }

        $hourly = match ($resource) {
            'metal'     => $planet->getMetalProductionPerHour(),
            'crystal'   => $planet->getCrystalProductionPerHour(),
            'deuterium' => $planet->getDeuteriumProductionPerHour(),
        };
        $rawDailyProduction = max(0, (int) floor($hourly) * 24);

        $storageMax = (int) floor($planet->{$resource . 'Storage'}()->get());
        $stockNow = (int) floor($planet->{$resource}()->get());
        $headroom = max(0, $storageMax - $stockNow);

        // "Procura risorse" mechanic: by default a player can buy up to ONE DAY of
        // their own production. Fallback: when production = 0 (e.g. deuterium on a
        // planet without a fusion plant) but the deposit has free space, allow buying
        // up to the storage headroom anyway — otherwise the player has no way to top
        // up an empty deposit on planets that lack the right mine. The unit price
        // (coefficient × amount) is unchanged, so this is purely a UX relaxation.
        $maxPurchasable = $rawDailyProduction > 0 ? $rawDailyProduction : $headroom;

        $amount = min($maxPurchasable, $headroom);
        $isCapped = $headroom < $maxPurchasable;

        $coef = self::COEFFICIENTS[$resource];
        // Cost is always computed from the FULL package size (not the capped amount):
        // OGame charges the full price even when storage forces partial delivery,
        // matching the warning tooltip "le eccedenze non verranno immagazzinate".
        $cost = $maxPurchasable > 0
            ? max(self::MIN_COST_DM, (int) ceil($maxPurchasable * $coef))
            : 0;

        return [
            'resource'         => $resource,
            'amount'           => $amount,
            // `daily_production` here is the MAX PURCHASABLE in this transaction —
            // either the real production*24 OR the storage headroom when production
            // is zero (DM top-up fallback above). Kept under the original key for
            // backwards compatibility with the Blade / JS data-* contract.
            'daily_production' => $maxPurchasable,
            'storage_headroom' => $headroom,
            'is_capped'        => $isCapped,
            'cost_dm'          => $cost,
            'coefficient'      => $coef,
            'hourly_production_per_hour' => max(0, (int) floor($hourly)),
        ];
    }

    /**
     * Compute the "all resources" combined package.
     *
     * @return array{
     *     packages: array<string, array<string, mixed>>,
     *     total_cost_dm: int
     * }
     */
    public function calculateAllPackage(PlanetService $planet): array
    {
        $packages = [];
        $total = 0;
        foreach (array_keys(self::COEFFICIENTS) as $resource) {
            $pkg = $this->calculatePackage($planet, $resource);
            $packages[$resource] = $pkg;
            $total += $pkg['cost_dm'];
        }

        return [
            'packages'      => $packages,
            'total_cost_dm' => $total,
        ];
    }

    /**
     * Execute a purchase: debit DM, credit resources to the planet, atomically.
     *
     * Anti-tamper: any client-side $costDm or $amountRequested for a *cost* is
     * ignored — server recomputes both Q and P from authoritative state. The
     * client may request a *partial* package by passing $amountRequested (capped
     * to the daily production); the cost scales linearly via `ceil(amount × coef)`.
     *
     * @param string|null $amountRequested  optional per-resource amount (only meaningful
     *                                      for single-resource packages; ignored for "all")
     * @return array{success: bool, message: string, credited?: array<string,int>, cost_dm?: int}
     */
    public function executePurchase(PlayerService $player, PlanetService $planet, string $package, ?int $amountRequested = null): array
    {
        $validPackages = [self::PACKAGE_METAL, self::PACKAGE_CRYSTAL, self::PACKAGE_DEUTERIUM, self::PACKAGE_ALL];
        if (!in_array($package, $validPackages, true)) {
            return [
                'success' => false,
                'code' => 'invalid_package',
                'message' => __('t_merchant.error.buy.invalid_package'),
            ];
        }

        // Recompute server-side. For partial single-resource purchases, scale Q and
        // recompute P from the requested amount (clamped to [1, daily_production]).
        if ($package === self::PACKAGE_ALL) {
            $bundle = $this->calculateAllPackage($planet);
            $items = $bundle['packages'];
            $totalCost = $bundle['total_cost_dm'];
            $totalDailyProduction = (int) array_sum(array_column($items, 'daily_production'));
        } else {
            $pkg = $this->calculatePackage($planet, $package);

            // Partial buy: recompute amount + cost from the user-requested integer.
            // Cost is based on the REQUESTED amount (mirror OGame: pay for what you
            // ask, even if storage can't fit it all). Deliverable caps the credited
            // resources. The client cap min($requested, daily_production) is also
            // enforced server-side as a sanity guard.
            if ($amountRequested !== null && $amountRequested > 0) {
                $coef = self::COEFFICIENTS[$package];
                $requested = min($amountRequested, $pkg['daily_production']);
                $deliverable = min($requested, $pkg['storage_headroom']);
                $cost = $requested > 0
                    ? max(self::MIN_COST_DM, (int) ceil($requested * $coef))
                    : 0;
                $pkg['amount'] = $deliverable;
                $pkg['cost_dm'] = $cost;
                $pkg['is_capped'] = $deliverable < $requested;
            }

            $items = [$package => $pkg];
            $totalCost = $pkg['cost_dm'];
            $totalDailyProduction = (int) $pkg['daily_production'];
        }

        // Differentiate the failure modes so the client can react appropriately
        // (e.g., trigger the "Acquista la Materia Oscura" upsell on insufficient DM).
        if ($totalDailyProduction <= 0) {
            return [
                'success' => false,
                'code' => 'no_production',
                'message' => __('t_merchant.error.buy.no_production'),
            ];
        }

        $totalAmount = array_sum(array_column($items, 'amount'));
        if ($totalAmount <= 0) {
            return [
                'success' => false,
                'code' => 'storage_full',
                'message' => __('t_merchant.error.buy.storage_full'),
            ];
        }

        if ($totalCost <= 0) {
            // Defensive: should not happen given the checks above, but keep a
            // dedicated branch so we never silently charge nothing.
            return [
                'success' => false,
                'code' => 'nothing_to_buy',
                'message' => __('t_merchant.error.buy.nothing_to_buy'),
            ];
        }

        $user = $player->getUser();
        // Refresh from DB: PlayerService may cache the User model across requests
        // within the same PHP process (singleton-ish), so the in-memory dark_matter
        // can lag behind the latest transaction. Reading authoritative DM here
        // eliminates a class of "you have the DM but server says you don't" bugs.
        $user->refresh();
        if ($user->dark_matter < $totalCost) {
            return [
                'success' => false,
                'code' => 'insufficient_dark_matter',
                'message' => __('t_merchant.error.buy.insufficient_dark_matter', [
                    'cost' => number_format($totalCost),
                ]),
                'available_dm' => (int) $user->dark_matter,
                'required_dm'  => $totalCost,
            ];
        }

        $darkMatterService = resolve(DarkMatterService::class);

        try {
            $credited = DB::transaction(function () use ($items, $planet, $user, $totalCost, $package, $darkMatterService) {
                // Debit DM first; throws on race-condition shortfall and rolls back the credit.
                $darkMatterService->debit(
                    $user,
                    $totalCost,
                    DarkMatterTransactionType::MERCHANT->value,
                    'Procura risorse: ' . $package . ' on planet ' . $planet->getPlanetId()
                );

                $credited = ['metal' => 0, 'crystal' => 0, 'deuterium' => 0];
                foreach ($items as $resource => $pkg) {
                    if ($pkg['amount'] <= 0) {
                        continue;
                    }
                    $resources = new Resources(
                        $resource === 'metal' ? $pkg['amount'] : 0,
                        $resource === 'crystal' ? $pkg['amount'] : 0,
                        $resource === 'deuterium' ? $pkg['amount'] : 0
                    );
                    $planet->addResources($resources, true);
                    $credited[$resource] = $pkg['amount'];
                }

                return $credited;
            });
        } catch (RuntimeException $e) {
            // PlanetService throws RuntimeException when resource deduction hits the
            // atomic guard; in this flow we are crediting, not deducting, so this
            // branch is mostly defensive.
            return [
                'success' => false,
                'code' => 'execution_failed',
                'message' => __('t_merchant.error.buy.execution_failed', ['error' => $e->getMessage()]),
            ];
        } catch (Exception $e) {
            // DarkMatterService::debit() throws a plain Exception when the atomic
            // balance check inside the locked transaction sees insufficient DM
            // (race condition between our pre-check and the row lock). Map that
            // specific case to the upsell code so the UI morphs the button.
            if (str_contains($e->getMessage(), 'Insufficient Dark Matter')) {
                return [
                    'success' => false,
                    'code' => 'insufficient_dark_matter',
                    'message' => __('t_merchant.error.buy.insufficient_dark_matter', [
                        'cost' => number_format($totalCost),
                    ]),
                ];
            }
            return [
                'success' => false,
                'code' => 'execution_failed',
                'message' => __('t_merchant.error.buy.execution_failed', ['error' => $e->getMessage()]),
            ];
        }

        return [
            'success'  => true,
            'message'  => __('t_merchant.success.buy_completed'),
            'credited' => $credited,
            'cost_dm'  => $totalCost,
        ];
    }
}
