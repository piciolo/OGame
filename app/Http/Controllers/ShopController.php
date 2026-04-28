<?php

namespace OGame\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OGame\Enums\InventoryCategory;
use OGame\Models\UserItem;
use OGame\Services\InventoryService;
use OGame\Services\PlayerService;

class ShopController extends OGameController
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    public function index(PlayerService $player): View
    {
        $this->setBodyId('shop');

        $payload = $this->inventory->shopPayload($player->getUser());

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

        return view('ingame.shop.index', [
            'inventoryItems' => $payload['items'],
            'inventoryOrders' => $payload['orders'],
            'categories' => $categories,
            'allCategoryRef' => InventoryCategory::Items->ref(),
            'activateToken' => csrf_token(),
        ]);
    }

    /**
     * Detail overlay (slideIn) AJAX — returns itemDetails HTML for a given ref.
     */
    public function detail(Request $request, PlayerService $player): JsonResponse
    {
        $ref = (string) $request->input('ref', '');
        $payload = $this->inventory->shopPayload($player->getUser());
        if (!isset($payload['items'][$ref])) {
            return response()->json(['success' => false, 'message' => __('t_shop_items.activate_not_found')], 404);
        }

        $item = $payload['items'][$ref];
        $tooltipParts = explode('|', $item['title'], 2);
        $title = $tooltipParts[0] ?? '';
        $body = $tooltipParts[1] ?? '';

        return response()->json([
            'success' => true,
            'item' => [
                'ref' => $ref,
                'item_type' => $item['item_type'],
                'tier' => $item['tier'],
                'amount' => $item['amount'],
                'rarity' => $item['rarity'],
                'imageLarge' => $item['imageLarge'],
                'title' => $title,
                'body' => $body,
                'canBeActivated' => $item['canBeActivated'] && $item['amount'] > 0,
                'activation_type' => $item['activation_type'],
            ],
        ]);
    }

    /**
     * Activate a single item from the inventory stack (consumable for now).
     * The real effect of the activation lands in a future PR — this endpoint
     * just marks the item as consumed so the count decrements in the UI.
     */
    public function activate(Request $request, PlayerService $player): JsonResponse
    {
        $ref = (string) $request->input('ref', '');
        if ($ref === '') {
            return response()->json(['error' => true, 'message' => __('t_shop_items.activate_not_found')], 400);
        }

        $user = $player->getUser();

        // Find the (item_type,tier) for this ref by scanning the user's inventory
        $available = UserItem::query()
            ->where('user_id', $user->id)
            ->where('status', 'available')
            ->get();

        $match = $available->first(fn (UserItem $i) => $i->stackRef() === $ref);
        if ($match === null) {
            return response()->json(['error' => true, 'message' => __('t_shop_items.activate_not_found')], 404);
        }

        $consumed = $this->inventory->consumeOne($user, $match->item_type, $match->tier);
        if ($consumed === null) {
            return response()->json(['error' => true, 'message' => __('t_shop_items.activate_not_found')], 404);
        }

        $newCount = $this->inventory->countStack($user, $match->item_type, $match->tier);

        return response()->json([
            'error' => false,
            'newToken' => csrf_token(),
            'message' => [
                'message' => __('t_shop_items.activate_success'),
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
