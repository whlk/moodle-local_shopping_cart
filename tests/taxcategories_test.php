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

use local_shopping_cart\local\entities\cartitem;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class taxcategories_test extends TestCase {

    public function test_complex_raw_string_is_valid() {
        // we test different valid permutations of a complex configuration string
        $validrawstring = 'at A:20 B:10 C:0
    de A:19 B:10 C:0
    default A:0 B:0 C:0';
        $this->assertTrue(taxcategories::is_valid_raw_string($validrawstring));

        $validrawstring = 'at A:20 B:10 C:0
    de C:0
    default A:0 B:0 C:0';
        $this->assertTrue(taxcategories::is_valid_raw_string($validrawstring));

        $validrawstring = '
    at A:20 B:10 C:0
    de C:0
    default A:0 B:0 C:0';
        $this->assertTrue(taxcategories::is_valid_raw_string($validrawstring));

        $validrawstring = 'default A:0 B:0 C:0
    at A:20
    de C:0';
        $this->assertTrue(taxcategories::is_valid_raw_string($validrawstring));
    }

    public function test_complex_raw_string_without_default_category_is_invalid() {
        $invalidrawstring = 'at A:20 B:10 C:0
    de A:19 B:10 C:0
    other A:0 B:0 C:0';
        $this->assertFalse(taxcategories::is_valid_raw_string($invalidrawstring));
    }

    public function test_empty_raw_string_is_invalid() {
        $raw = '';
        $this->assertFalse(taxcategories::is_valid_raw_string($raw));
    }

    public function test_single_value_raw_string_is_valid() {
        $raw = '20';
        $this->assertTrue(taxcategories::is_valid_raw_string($raw));
    }

    public function test_single_line_raw_string_is_valid() {
        $raw = 'A:20 B:10 C:0';
        $this->assertTrue(taxcategories::is_valid_raw_string($raw), "'$raw' is not a valid raw string");
    }

    public function test_multi_line_raw_string_is_valid() {
        $raw = "at A:20 B:10 C:0\nde A:19 B:9 C:0\ndefault A:0 B:0 C:0";
        $this->assertTrue(taxcategories::is_valid_raw_string($raw), "'$raw' is not a valid raw string");
    }

    public function test_single_line() {
        $raw = 'A:20 B:10 C:0';
        $fromraw = taxcategories::from_raw_string("A", $raw);

        $this->assertEquals("A", $fromraw->defaultcategory(), "Unexpected default category");
        $this->assertEqualsCanonicalizing(array('A', 'B', 'C'), $fromraw->validcategories());
        $expected = [
                taxcategories::DEFAULT_COUNTRY_INDEX => ["A" => 0.2, "B" => 0.1, "C" => 0.0]
        ];
        $this->assertEqualsCanonicalizing($expected, $fromraw->taxmatrix());
    }

    public function test_single_value_empty_default_category() {
        $raw = '20';
        $fromraw = taxcategories::from_raw_string("", $raw);

        $this->assertEquals(taxcategories::DEFAULT_CATEGORY_KEY, $fromraw->defaultcategory(), "Unexpected default category");
        $this->assertEqualsCanonicalizing(array(taxcategories::DEFAULT_CATEGORY_KEY), $fromraw->validcategories());
        $expected = [
                taxcategories::DEFAULT_COUNTRY_INDEX => [taxcategories::DEFAULT_CATEGORY_KEY => 0.2]
        ];
        $this->assertEqualsCanonicalizing($expected, $fromraw->taxmatrix());
    }

    public function test_multi_line() {
        $raw = '
        default A:0 B:0 C:0
        at A:20 B:10 C:0';
        $fromraw = taxcategories::from_raw_string("A", $raw);

        $this->assertEquals("A", $fromraw->defaultcategory(), "Unexpected default category");
        $this->assertEqualsCanonicalizing(array('A', 'B', 'C'), $fromraw->validcategories());
        $expected = [
                taxcategories::DEFAULT_COUNTRY_INDEX => ["A" => 0, "B" => 0, "C" => 0.0],
                "at" => ["A" => 0.2, "B" => 0.1, "C" => 0.0]
        ];
        $this->assertEqualsCanonicalizing($expected, $fromraw->taxmatrix());
    }

    public function test_tax_for_category_no_country_code() {
        $raw = 'A:25 B:10 C:1';
        $taxcategories = taxcategories::from_raw_string("A", $raw);

        $this->assertEquals(0.25, $taxcategories->tax_for_category("A"));
        $this->assertEquals(0.10, $taxcategories->tax_for_category("B"));
        $this->assertEquals(0.01, $taxcategories->tax_for_category("C"));

        // unknown category
        $this->assertEquals(-1, $taxcategories->tax_for_category("X"));
    }

    public function test_tax_for_no_category_no_country_code() {
        $raw = 'A:25 B:10 C:1';
        $taxcategories = taxcategories::from_raw_string("A", $raw);

        // default category is used
        $this->assertEquals(0.25, $taxcategories->tax_for_category(""));
    }

    public function test_tax_for_no_category_but_country_code() {
        $raw = 'default A:25
        mycountry A:30';
        $taxcategories = taxcategories::from_raw_string("A", $raw);

        $this->assertEquals(0.30, $taxcategories->tax_for_category("", "mycountry"));
        $this->assertEquals(0.25, $taxcategories->tax_for_category("", "other"));
    }

    public function test_tax_for_category_and_country_code() {
        $raw = 'default A:25
        mycountry A:30';
        $taxcategories = taxcategories::from_raw_string("A", $raw);

        $this->assertEquals(0.3, $taxcategories->tax_for_category("A", "mycountry"));
        $this->assertEquals(0.25, $taxcategories->tax_for_category("A", "other"));
    }

    public function test_tax_for_category_and_country_code_use_default_fallback() {
        $raw = 'default A:25 C:10
        mycountry A:30';
        $taxcategories = taxcategories::from_raw_string("A", $raw);

        $this->assertEquals(0.1, $taxcategories->tax_for_category("C", "mycountry"));
        $this->assertEquals(0.1, $taxcategories->tax_for_category("C", "other"));
    }

}