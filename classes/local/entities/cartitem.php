<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The cartitem class.
 *
 * @package    local_shopping_cart
 * @copyright  2022 Georg Maißer Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_shopping_cart\local\entities;

/**
 * The cartitem class.
 *
 * @copyright  2022 Georg Maißer Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cartitem {

    /**
     * Item id
     *
     * @var int
     */
    private $itemid;

    /**
     * Name of the item
     *
     * @var string
     */
    private $itemname;

    /**
     * Price of the item as a float value including tax (gross)
     *
     * @var float
     */
    private $price;

    /**
     * Tax category applied to this item.
     *
     * @var string|null
     */
    private $taxcategory;

    /**
     * Currency must be the same for all items in cart.
     *
     * @var string
     */
    private $currency;

    /**
     * Name of component (like local_shopping_cart)
     *
     * @var string
     */
    private $componentname;

    /**
     * Description of the item
     *
     * @var string
     */
    private $description;

    /**
     * Link to image
     *
     * @var string|null
     */
    private $imageurl;

    /**
     * A timestamp until when canceling is possible.
     *
     * @var ?int
     */
    private $canceluntil;

    /**
     * Service period start timestamp
     *
     * @var ?int
     */
    private $serviceperiodstart;

    /**
     * Service period end timestamp
     *
     * @var ?int
     */
    private $serviceperiodend;

    /**
     * Constructor for creating a cartitem.
     *
     * @param int $itemid id of cartitem
     * @param string $itemname name of item
     * @param float $price item price
     * @param string $currency currency for purchase
     * @param string $componentname moodle compoment that sells the item
     * @param string $description item description
     * @param string $imageurl url to the item image
     * @param int|null $canceluntil cancellation possible until
     * @param int|null $serviceperiodstart start of service period
     * @param int|null $serviceperiodend end of service period
     * @param string|null $taxcategory the tax category of this item
     */
    public function __construct(int $itemid,
            string $itemname,
            float $price,
            string $currency,
            string $componentname,
            string $description = '',
            string $imageurl = '',
            ?int $canceluntil = null,
            ?int $serviceperiodstart = null,
            ?int $serviceperiodend = null,
            ?string $taxcategory = null) {
        $this->itemid = $itemid;
        $this->itemname = $itemname;
        $this->price = $price;
        $this->currency = $currency;
        $this->componentname = $componentname;
        $this->description = $description;
        $this->imageurl = $imageurl;
        $this->canceluntil = $canceluntil;
        $this->serviceperiodstart = $serviceperiodstart;
        $this->serviceperiodend = $serviceperiodend;
        $this->taxcategory = $taxcategory;
    }

    /**
     * Returns all the values as array.
     *
     * @return array
     */
    public function as_array(): array {
        $item = array();
        $item['itemid'] = $this->itemid;
        $item['itemname'] = $this->itemname;
        $item['price'] = $this->price;
        $item['currency'] = $this->currency;
        $item['componentname'] = $this->componentname;
        $item['description'] = $this->description;
        $item['imageurl'] = $this->imageurl;
        $item['canceluntil'] = $this->canceluntil;
        $item['serviceperiodstart'] = $this->serviceperiodstart;
        $item['serviceperiodend'] = $this->serviceperiodend;
        $item['taxcategory'] = $this->taxcategory;
        return $item;
    }

    /**
     * Get the gross price of the cartitem including any tax.
     *
     * @return float
     */
    public function price(): float {
        return $this->price;
    }

    /**
     * @return string|null the tax category for this item
     */
    public function tax_category(): ?string {
        return $this->taxcategory;
    }

    /**
     * Get the currency of the cartitem price.
     *
     * @return string
     */
    public function currency(): string {
        return $this->currency;
    }

    /**
     * Get the itemid.
     *
     * @return int
     */
    public function itemid(): int {
        return $this->itemid;
    }

    /**
     * Get the canceluntil timestamp.
     *
     * @return int|null
     */
    public function cancel_until_timestamp(): ?int {
        return $this->canceluntil;
    }

    /**
     * @return string|null
     */
    public function imageurl(): ?string {
        return $this->imageurl;
    }
}
