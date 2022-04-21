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
 * Payment subsystem callback implementation for local_shopping_cart.
 *
 * @package    local_shopping_cart
 * @category   payment
 * @copyright  2022 Georg Maißer <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_shopping_cart\payment;

use local_shopping_cart\shopping_cart;
use local_shopping_cart\shopping_cart_history;
use moodle_url;
use stdClass;

/**
 * Payment subsystem callback implementation for local_shopping_cart.
 *
 * @copyright  2022 Georg Maißer <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service_provider implements \core_payment\local\callback\service_provider {

    /**
     * Callback function that returns the costs and the accountid
     * for the course that $userid of the buying user.
     *
     * @param string $paymentarea Payment area
     * @param int $cartidentifier
     * @return \core_payment\local\entities\payable
     */
    public static function get_payable(string $paymentarea, int $cartidentifier): \core_payment\local\entities\payable {
        global $DB;

        // Instead of an item or user id, we use a cart identifier which basically is just a timestamp.
        // This timestamp will give us a key to store the cart of a user in session cache and hold the items of the cart together.

        $sc = new shopping_cart_history();
        $shoppingcart = $sc->fetch_data_from_schistory_cache($cartidentifier, true);

        if (!$accountid = get_config('local_shopping_cart', 'accountid')) {
            $accountid = 1;
        }

        return new \core_payment\local\entities\payable($shoppingcart->price, $shoppingcart->currency, $accountid);
    }

    /**
     * Callback function that returns the URL of the page the user should be redirected to in the case of a successful payment.
     *
     * @param string $paymentarea Payment area
     * @param int $identifier The transaction id which was just successfully terminated.
     * @return \moodle_url
     */
    public static function get_success_url(string $paymentarea, int $identifier): moodle_url {
        global $DB;

        return new \moodle_url('/local/shopping_cart/checkout.php', ['success' => 1, 'identifier' => $identifier]);
    }

    /**
     * Callback function that delivers what the user paid for to them.
     *
     * @param string $paymentarea
     * @param int $identifier The id of the transaction
     * @param int $paymentid payment id as inserted into the 'payments' table, if needed for reference
     * @param int $userid The userid the order is going to deliver to
     * @return bool Whether successful or not
     */
    public static function deliver_order(string $paymentarea, int $identifier, int $paymentid, int $userid): bool {
        global $DB;

         // First, look in shopping cart history to identify the payment and what users have bought.
         // Now run through all the optionids (itemids) and confirm payment.

        $data['items'] = shopping_cart_history::return_data_via_identifier($identifier);

        if (count($data['items']) == 0) {
            return false;
        }

        shopping_cart::confirm_payment($userid, $data);

        $success = shopping_cart_history::set_success_in_db($data['items']);
        return $success;
    }
}
