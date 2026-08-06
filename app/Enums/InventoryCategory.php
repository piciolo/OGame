<?php

namespace OGame\Enums;

enum InventoryCategory: string
{
    case SpecialOffers = 'special_offers';
    case ClassSelection = 'class_selection';
    case Construction = 'construction';
    case Resources = 'resources';
    case Booster30 = 'booster30';
    case Booster90 = 'booster90';
    case Profile = 'profile';
    case Items = 'items';

    /**
     * OGame-compatible SHA-1 category ref (for the JS inventoryObj).
     * Generated from the enum value — stable and deterministic.
     */
    public function ref(): string
    {
        return sha1('inventory_category:' . $this->value);
    }

    public function langKey(): string
    {
        return 'category_' . $this->value;
    }
}
