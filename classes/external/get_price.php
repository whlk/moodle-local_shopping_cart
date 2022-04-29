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
 * This class contains a list of webservice functions related to the Shopping Cart Module by Wunderbyte.
 *
 * @package    local_shopping_cart
 * @copyright  2022 Georg Maißer <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_shopping_cart\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use local_shopping_cart\shopping_cart;
use local_shopping_cart\shopping_cart_credits;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class get_price extends external_api {

    /**
     * Describes the paramters for add_item_to_cart.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(array(
            'userid'  => new external_value(PARAM_INT, 'userid', VALUE_DEFAULT, 0),
            'usecredit'  => new external_value(PARAM_INT, 'use credit', VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Webservice for shopping_cart class to add a new item to the cart.
     *
     * @param string $component
     * @param int $itemid
     * @param int $userid
     *
     * @return array
     */
    public static function execute(int $userid, int $usecredit): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'userid' => $userid,
            'usecredit' => $usecredit
        ]);

        $usecredit = $params['usecredit'] == 1 ? true : false;

        shopping_cart::save_used_credit_state($params['userid'], $usecredit);

        $data = shopping_cart::local_shopping_cart_get_cache_data($params['userid'], $usecredit);

        // For the webservice, we must make sure that the keys exist.

        $data['remainingcredit'] = $data['remainingcredit'] ?? 0;
        $data['deductible'] = $data['deductible'] ?? 0;
        $data['usecredit'] = $data['usecredit'] ?? 0;

        return $data;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(array(
            'price' => new external_value(PARAM_RAW, 'Total price'),
            'credit' => new external_value(PARAM_RAW, 'Credit'),
            'currency' => new external_value(PARAM_RAW, 'Currency'),
            'initialtotal' => new external_value(PARAM_RAW, 'Initial price before deduced credits'),
            'remainingcredit' => new external_value(PARAM_RAW, 'Credits after reducation'),
            'deductible' => new external_value(PARAM_RAW, 'Deductible amount'),
            'usecredit' => new external_value(PARAM_INT, 'If we want to use the credit or not'),
            )
        );
    }
}
