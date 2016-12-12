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
 * local_catdeleter scheduler tests.
 *
 * @package   local_catdeleter
 * @author    Kenneth Hendricks <kennethhendricks@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use tool_sssfs\renderables\sss_file_status;
use tool_sssfs\sss_file_system;
use tool_sssfs\sss_file_pusher;
require_once(__DIR__ . '/../lib.php');
require_once(__DIR__ . '/testlib.php');
require_once(__DIR__ . '/sss_mock_client.php');



class tool_sssfs_sss_file_status_testcase extends advanced_testcase {

    protected function setUp() {
        global $CFG;
        $this->resetAfterTest(true);
        $CFG->filesystem_handler_class = '\tool_sssfs\sss_file_system';
    }

    protected function tearDown() {

    }

    private function check_state_file_summary($data, $state, $expectedcount, $expectedsum) {
        $this->assertEquals($expectedcount, $data[$state]->filecount);
        $this->assertEquals($expectedsum, $data[$state]->filesum);
    }

    public function test_can_get_status () {
        global $DB;
        $states = array(SSS_FILE_STATE_LOCAL, SSS_FILE_STATE_DUPLICATED, SSS_FILE_STATE_EXTERNAL);

        $status = new sss_file_status();

        // Should all be 0 duplicated and external states.
        foreach ($states as $state) {
            $this->check_state_file_summary($status->data, SSS_FILE_STATE_DUPLICATED, 0, 0);
            $this->check_state_file_summary($status->data, SSS_FILE_STATE_EXTERNAL, 0, 0);
        }

        $client = new sss_mock_client();
        $filesystem = sss_file_system::instance();
        $config = generate_config(10); // 10 MB size threshold.
        $pusher = new sss_file_pusher($client, $filesystem, $config);

        $singlefilesize = 100 * 1024; // 100mb.
        for ($i = 1; $i <= 10; $i++) {
            save_file_to_local_storage(1024 * 100, "test-{$i}.txt", "test-{$i} content"); // 100 mb files.
        }

        $pusher->push();

        $status = new sss_file_status();

        $postpushcount = $DB->count_records('tool_sssfs_filestate');
        $this->assertEquals(10, $postpushcount);
        $this->check_state_file_summary($status->data, SSS_FILE_STATE_DUPLICATED, 10, $singlefilesize * 10);

        // TODO: when clean task implemented. Check External portion.
    }

}