<?php

namespace OGame\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OGame\Enums\InventoryCategory;
use OGame\Models\ShopItem;
use OGame\Models\UserItem;
use OGame\Services\InventoryActivationService;
use OGame\Services\InventoryService;
use OGame\Services\PlayerService;
use OGame\Services\ShopService;
use RuntimeException;

class ShopController extends OGameController
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly ShopService $shop,
        private readonly InventoryActivationService $activation,
    ) {
    }

    public function index(PlayerService $player): View
    {
        $this->setBodyId('shop');

        $user = $player->getUser();
        $payload = $this->inventory->shopPayload($user);

        $categories = [];
        foreach (InventoryCategory::cases() as $cat) {
            $count = 0;
            foreach ($payload['items'] as $it) {
                if (in_array($cat->ref(), $it['category'], true)) {
                    $count += (int) $it['amount'];
                }
            }
            $categories[] = [
                'ref' => $cat->ref(),
                'lang_key' => $cat->langKey(),
                'count' => $count,
            ];
        }

        $shopCatalog = $this->shop->catalog();

        return view('ingame.shop.index', [
            'inventoryItems' => $payload['items'],
            'inventoryOrders' => $payload['orders'],
            'categories' => $categories,
            'allCategoryRef' => InventoryCategory::Items->ref(),
            'activateToken' => csrf_token(),
            'shopCatalog' => $shopCatalog,
            'darkMatter' => (int) $user->dark_matter,
        ]);
    }

    /**
     * Detail overlay (slideIn) AJAX — returns itemDetails HTML for a given ref.
     * Supports both inventory stack refs and shop item refs (sha1 hashes).
     */
    public function detail(Request $request, PlayerService $player): JsonResponse
    {
        $ref = (string) $request->input('ref', '');
        $user = $player->getUser();

        // Try inventory first
        $payload = $this->inventory->shopPayload($user);
        if (isset($payload['items'][$ref])) {
            $item = $payload['items'][$ref];
            $tooltipParts = explode('|', $item['title'], 2);
            return response()->json([
                'success' => true,
                'item' => [
                    'ref' => $ref,
                    'source' => 'inventory',
                    'item_type' => $item['item_type'],
                    'tier' => $item['tier'],
                    'amount' => $item['amount'],
                    'rarity' => $item['rarity'],
                    'imageLarge' => $item['imageLarge'],
                    'image_override_url' => $item['image_override_url'] ?? null,
                    'title' => $tooltipParts[0] ?? '',
                    'body' => $tooltipParts[1] ?? '',
                    'canBeActivated' => $item['canBeActivated'] && $item['amount'] > 0,
                    'activation_type' => $item['activation_type'],
                ],
            ]);
        }

        // Then try shop catalog
        $shop = ShopItem::query()->where('ref', $ref)->first();
        if ($shop !== null) {
            $imgDir = '/cdn/img/item-images/';
            // Inventory count for shop items: how many UserItem stacks the user has with same ref
            $invCount = (int) UserItem::query()
                ->where('user_id', $user->id)
                ->where('source', 'shop')
                ->where('source_ref', $shop->id)
                ->where('status', 'available')
                ->count();

            return response()->json([
                'success' => true,
                'item' => [
                    'ref' => $shop->ref,
                    'source' => 'shop',
                    'name' => $shop->name,
                    'description' => $shop->description,
                    'extended_description' => $shop->extended_description,
                    'effect_description' => $shop->effect_description,
                    'rules_description' => $shop->rules_description,
                    'price_dm' => (int) $shop->price_dm,
                    'price_dm_original' => $shop->price_dm_original ? (int) $shop->price_dm_original : null,
                    'price_label' => $shop->price_label,
                    'price_label_original' => $shop->price_label_original,
                    'duration_seconds' => $shop->duration_seconds,
                    'duration_label' => $shop->duration_label,
                    'rarity' => $shop->rarity,
                    'image_url' => $imgDir . $shop->image,
                    'image_fallback_url' => $shop->image_fallback ? $imgDir . $shop->image_fallback : null,
                    'is_lifeform' => (bool) $shop->is_lifeform,
                    'booster_type' => $shop->booster_type,
                    'tier_key' => $shop->tier_key,
                    'inventory_count' => $invCount,
                    'user_dark_matter' => (int) $user->dark_matter,
                    'can_afford' => (int) $user->dark_matter >= (int) $shop->price_dm,
                ],
            ]);
        }

        return response()->json(['success' => false, 'message' => __('t_shop_items.activate_not_found')], 404);
    }

    /**
     * Purchase a shop item — deducts dark matter and grants into inventory.
     */
    public function buy(Request $request, PlayerService $player): JsonResponse
    {
        $ref = (string) $request->input('ref', '');
        // Validate format: 40-char lowercase hex (sha1)
        if (!preg_match('/^[a-f0-9]{40}$/', $ref)) {
            return response()->json(['error' => true, 'message' => __('t_shop_items.activate_not_found')], 400);
        }

        $shop = ShopItem::query()->where('ref', $ref)->first();
        if ($shop === null) {
            return response()->json(['error' => true, 'message' => __('t_shop_items.activate_not_found')], 404);
        }

        // Reject items currently disabled in shop UI (e.g. Lifeform variants)
        if (str_contains($shop->name, '(Forme di vita)')) {
            return response()->json(['error' => true, 'message' => __('t_shop_items.activate_not_supported')], 422);
        }

        $user = $player->getUser();

        try {
            $this->shop->purchase($user, $shop, $request->ip());
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'insufficient_dm') {
                return response()->json(['error' => true, 'message' => __('t_shop_items.buy_insufficient_dm')], 422);
            }
            throw $e;
        }

        $user->refresh();
        return response()->json([
            'error' => false,
            'newToken' => csrf_token(),
            'message' => __('t_shop_items.buy_success'),
            'dark_matter' => (int) $user->dark_matter,
        ]);
    }

    /**
     * Activate a single item from the inventory stack (consumable for now).
     */
    public function activate(Request $request, PlayerService $player): JsonResponse
    {
        $ref = (string) $request->input('ref', '');
        if ($ref === '') {
            return response()->json(['error' => true, 'message' => __('t_shop_items.activate_not_found')], 400);
        }

        $user = $player->getUser();

        $available = UserItem::query()
            ->where('user_id', $user->id)
            ->where('status', 'available')
            ->get();

        $match = $available->first(fn (UserItem $i) => $i->stackRef() === $ref);
        if ($match === null) {
            return response()->json(['error' => true, 'message' => __('t_shop_items.activate_not_found')], 404);
        }

        $planetId = $player->planets->current()->getPlanetId();
        $result = $this->activation->activate($user, $match->item_type, $match->tier, $planetId);

        if (!$result['ok']) {
            $messageKey = match ($result['code']) {
                'no_effect_target' => 't_shop_items.activate_no_target',
                'invalid_planet' => 't_shop_items.activate_not_supported',
                'not_supported', 'unknown_item' => 't_shop_items.activate_not_supported',
                default => 't_shop_items.activate_not_found',
            };
            $status = $result['code'] === 'not_found' ? 404 : 422;
            return response()->json(['error' => true, 'message' => __($messageKey)], $status);
        }

        $newCount = $this->inventory->countStack($user, $match->item_type, $match->tier);
        $messageKey = $result['code'] === 'deactivated'
            ? 't_shop_items.deactivate_success'
            : 't_shop_items.activate_success';

        return response()->json([
            'error' => false,
            'newToken' => csrf_token(),
            'message' => [
                'message' => __($messageKey),
                'item' => [
                    'ref' => $ref,
                    'amount' => $newCount,
                    'title' => '',
                    'activationTitle' => '',
                    'buyTitle' => '',
                    'hasEnoughCurrency' => false,
                    'canBeActivated' => $newCount > 0,
                    'canBeBoughtAndActivated' => false,
                    'isAnUpgrade' => false,
                    'extendable' => false,
                    'timeLeft' => 0,
                ],
            ],
        ]);
    }
}
