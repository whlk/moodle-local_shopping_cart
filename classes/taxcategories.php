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

namespace local_shopping_cart;

/**
 * The tax categories class - holds tax category information
 *
 * @package    local_shopping_cart
 * @copyright  2022 Maurice Wohlkönig <maurice@whlk.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class taxcategories {
    /**
     * @var array<string>
     */
    private $categories = [];

    /**
     * @var string
     */
    private $defaultcategory = "";

    /**
     * Hold the tax percentage (float) per tax category per country
     *
     * @var array[]
     */
    private $taxmatrix = [[]];

    /**
     * @param $categories array<string> list of all possible tax categories
     * @param $defaultcategory string the default category to use when a cartitem does not supply it
     * @param $taxmatrix
     */
    private function __construct($categories, $defaultcategory, $taxmatrix) {
        $this->categories = $categories;
        $this->defaultcategory = $defaultcategory;
        $this->taxmatrix = $taxmatrix;
    }

    /**
     * Gets the tax percentage in float (0=0%, 1=100%) for the given category and country
     *
     * @param $category string|null
     * @param $countrycode string|null
     * @return float the tax percentage in float (0.0-1.0), or -1 if the given $category is invalid
     */
    public function tax_for_category(?string $category = null, ?string $countrycode = null): float {
        if (empty($category)) {
            // use default category as a fallback
            $category = $this->defaultcategory;
        }
        if (in_array($category, $this->categories)) {
            $taxdata = $this->taxdata_for_countrycode($countrycode);
            if (key_exists($category, $taxdata)) {
                // given category exists for country
                return $taxdata[$category];
            } else {
                // use category from default fallback
                return $this->taxdata_for_countrycode(null)[$category];
            }
        }
        // this $category is invalid
        return -1;
    }

    public function taxdata_for_countrycode(?string $countrycode = null): array {
        if (key_exists($countrycode, $this->taxmatrix)) {
            return $this->taxmatrix[$countrycode];
        } else {
            return $this->taxmatrix[self::DEFAULT_COUNTRY_INDEX];
        }
    }

    public function defaultcategory(): string {
        return $this->defaultcategory;
    }

    public function validcategories(): array {
        return $this->categories;
    }

    /**
     * @return array[]
     */
    public function taxmatrix(): array {
        return $this->taxmatrix;
    }

    /**
     * Creates a new taxcategories from a default category and the raw tax categories string.
     *
     * @param $defaultcategory
     * @param $rawcategories
     * @return taxcategories|null the newly created taxcategories or null if the input can not be parsed
     */
    public static function from_raw_string($defaultcategory, $rawcategories): ?taxcategories {
        if (!self::is_valid_raw_string($rawcategories)) {
            return null;
        }
        if (empty($defaultcategory)) {
            $defaultcategory = self::DEFAULT_CATEGORY_KEY;
        }
        $categories = self::extract_categories($rawcategories);
        if (!in_array($defaultcategory, $categories)) {
            return null;
        }
        $taxmatrix = self::taxmatrix_from_raw_string($rawcategories, $categories);
        return new taxcategories(
                $categories,
                $defaultcategory,
                $taxmatrix,
        );
    }

    /**
     * Tests if a given raw categories string is valid syntactically
     *
     * @param $rawcategories string
     * @return bool true when valid, false otherwise
     */
    public static function is_valid_raw_string(string $rawcategories): bool {
        $categories = self::extract_categories($rawcategories);
        // categories have to be existing and not empty
        if (!empty($categories)) {
            $matrix = self::taxmatrix_from_raw_string($rawcategories, $categories);
            // there has to be a default key
            if (key_exists(self::DEFAULT_COUNTRY_INDEX, $matrix)) {
                $defaultValues = $matrix[self::DEFAULT_COUNTRY_INDEX];
                // default key categories have to match the categories
                return is_array($defaultValues) && array_keys($defaultValues) == $categories;
            }
        }
        return false;
    }

    private static function extract_categories(string $rawcategories): array {
        if ($rawcategories === "") { // special case of empty line
            return array(); // no categories
        }
        if (is_numeric(trim($rawcategories))) { // special case of value only
            return array(self::DEFAULT_CATEGORY_KEY); // just one category which is default
        }
        $rows = preg_split('/\n/', trim($rawcategories));
        if ($rows === false) {
            return array(); // no categories
        }
        $firstrow = $rows[0];
        $catandvalue = self::categories_from_raw_line($firstrow);
        return array_keys(array_values($catandvalue)[0]);
    }

    private static function taxmatrix_from_raw_string(string $rawcategories, array $categories): array {
        $rows = preg_split('/\n/', trim($rawcategories));
        if ($rows === false) {
            return array(); // no categories
        }

        $matrix = [];
        foreach ($rows as $row) {
            $rowcategories = self::categories_from_raw_line($row);
            $countrycode = key($rowcategories);
            $matrix[$countrycode] = $rowcategories[$countrycode];
        }

        return $matrix;
    }

    private static function categories_from_raw_line(string $rawline): ?array {
        $trimmedrawline = trim($rawline);
        $linevalues = explode(' ', $trimmedrawline);
        if ($linevalues === false || count($linevalues) < 2) {
            if (is_numeric($trimmedrawline)) { // this might be a single value row
                return array(self::DEFAULT_COUNTRY_INDEX => array(self::DEFAULT_CATEGORY_KEY => floatval($trimmedrawline) / 100));
            }
            // lines with no data are invalid
            return null;
        }
        if (!str_contains($linevalues[0], ':')) { // assume first value is country code
            $countrycode = array_shift($linevalues);
        } else {
            $countrycode = self::DEFAULT_COUNTRY_INDEX;
        }

        $validcats = [];
        foreach ($linevalues as $linevalue) {
            $catandvalue = explode(":", trim($linevalue));
            if (is_string($catandvalue[0]) && is_numeric($catandvalue[1]) && count($catandvalue) == 2) {
                $validcats[$catandvalue[0]] = floatval($catandvalue[1]) / 100;
            }
        }

        return array($countrycode => $validcats);
    }

    public const DEFAULT_COUNTRY_INDEX = "default";
    public const DEFAULT_CATEGORY_KEY = "cat";
}


// based on original work from the PHP Laravel framework
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}
