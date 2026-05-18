<?php

namespace OGame\Models;

use OGame\Facades\AppUtil;

class Resource
{
    public function __construct(protected float $rawValue)
    {
    }

    /**
     * Add another resource to this one.
     *
     * @param Resource $other
     * @return void
     */
    public function add(Resource $other): void
    {
        $this->rawValue += $other->get();
    }

    /**
     * Multiply resource by a factor.
     *
     * @param float $factor
     * @return void
     */
    public function multiply(float $factor): void
    {
        $this->rawValue = $this->rawValue * $factor;
    }

    /**
     * Get the raw value of the resource as integer.
     *
     * @return float
     */
    public function get(): float
    {
        return $this->rawValue;
    }

    /**
     * Get the floored value of the resource as integer.
     *
     * Always round DOWN, never up: callers (notably the fleet dispatcher JS,
     * "send all" cargo computations, tooltips) must never expose more resources
     * than the planet actually holds. Using round() would report 1065158324.8
     * as 1065158325, and the atomic backend deduction guarded by
     * `WHERE crystal >= 1065158325` would then reject the dispatch with
     * "Not enough resources or units on the planet to send the fleet." (#1131).
     *
     * @return int
     */
    public function getRounded(): int
    {
        return (int) floor($this->rawValue);
    }

    /**
     * Get the formatted value of the resource as string (short, e.g. 5M).
     *
     * @return string
     */
    public function getFormatted(): string
    {
        return AppUtil::formatNumberShort($this->rawValue);
    }

    /**
     * Get the formatted value of the resource as string (longer, e.g. 5.33M).
     *
     * @return string
     */
    public function getFormattedLong(): string
    {
        return AppUtil::formatNumberLong($this->rawValue);
    }

    /**
     * Get the formatted value of the resource as string (all digits, no shortening e.g. 5,000,000).
     *
     * @return string
     */
    public function getFormattedFull(): string
    {
        return AppUtil::formatNumber($this->rawValue);
    }

    /**
     * Set the raw value of the resource.
     *
     * @param float $value
     * @return void
     */
    public function set(float $value): void
    {
        $this->rawValue = $value;
    }
}
