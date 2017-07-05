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
 * @package     cleaner_muc
 * @subpackage  local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use cleaner_muc\controller;
use cleaner_muc\dml\muc_config_db;

defined('MOODLE_INTERNAL') || die();

class  local_cleanurls_cleaner_muc_controller_test extends advanced_testcase {
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        // Trigger classloaders.
        class_exists(controller::class);
    }

    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
        self::setAdminUser();
    }

    /**
     * @expectedException \moodle_exception
     * @expectedExceptionMessage sesskey
     */
    public function test_it_requires_sesskey_to_download_current_config_file() {
        $_GET = ['action' => 'current'];
        (new controller())->index();
    }

    /**
     * @expectedException \moodle_exception
     * @expectedExceptionMessage sesskey
     */
    public function test_it_requires_sesskey_to_download_environment_config_file() {
        $this->markTestSkipped('Test/Feature not yet implemented.');
    }

    /**
     * @expectedException \moodle_exception
     * @expectedExceptionMessage sesskey
     */
    public function test_it_requires_sesskey_to_delete_config() {
        $this->markTestSkipped('Test/Feature not yet implemented.');
    }

    /**
     * @expectedException \moodle_exception
     * @expectedExceptionMessage Only admins can download MUC configuration
     */
    public function test_it_does_not_allow_download_current_config_if_not_admin() {
        // It should already be blocked by downloader page, but add one more layer of check.

        self::setUser($this->getDataGenerator()->create_user());

        $_GET = ['action' => 'current', 'sesskey' => sesskey()];
        (new controller())->index();
    }

    public function test_it_does_not_allow_download_environment_config_if_not_admin() {
        $this->markTestSkipped('Test/Feature not yet implemented.');
    }

    public function test_it_generates_the_correct_filename() {
        $expected = rawurlencode('http://thesite.url.to-use') . '.muc';
        $actual = controller::get_download_filename('http://thesite.url.to-use');
        self::assertSame($expected, $actual);
    }

    /**
     * @expectedException \moodle_exception
     * @expectedExceptionMessage Invalid action: somethinginvalid
     */
    public function test_it_throws_an_exception_for_invalid_action() {
        $_GET = ['action' => 'somethinginvalid', 'sesskey' => sesskey()];
        (new controller())->index();
    }

    public function test_it_deletes_environment_config() {
        $wwwroot = 'http://www.moodle.test/sub';
        muc_config_db::save($wwwroot, 'New Config');

        $_GET = [
            'action'      => 'delete',
            'environment' => rawurlencode($wwwroot),
            'sesskey'     => sesskey(),
        ];

        try {
            (new controller())->index();
            self::fail('Should throw exception (redirect).');
        } catch (moodle_exception $exception) {
            self::assertSame('Unsupported redirect detected, script execution terminated', $exception->getMessage());
        }

        $found = muc_config_db::get($wwwroot);
        self::assertNull($found);
    }
}
