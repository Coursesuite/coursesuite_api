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
 * Defines the global settings for coursesuite_api block.
 *
 * @package    block_coursesuite_api
 * @copyright  2020 tim st.clair
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading(
                'headerconfig',
                get_string('headerconfig', 'block_coursesuite_api'),
                get_string('descconfig', 'block_coursesuite_api')
            ));

    $settings->add(new admin_setting_configtext(
                'coursesuite_api/apikey',
                get_string('labelapikey', 'block_coursesuite_api'),
                get_string('descapikey', 'block_coursesuite_api'),
                '',
                PARAM_NOTAGS
            ));

    $settings->add(new admin_setting_configpasswordunmask(
                'coursesuite_api/secretkey',
                get_string('labelsecretkey', 'block_coursesuite_api'),
                get_string('descsecretkey', 'block_coursesuite_api'),
                '',
                PARAM_NOTAGS
            ));

    $settings->add(new admin_setting_configcheckbox(
                'coursesuite_api/debug',
                get_string('labeldebug', 'block_coursesuite_api'),
                get_string('labeldebugdesc', 'block_coursesuite_api'),
                0
            ));
}

//
// $opts = array('accepted_types' => array('.mbz'));
// $settings->add(new admin_setting_configstoredfile(
//         'coursesuite_api/coursebackup',
//         get_string('labelbackup', 'block_coursesuite_api'),
//         get_string('descbackup', 'block_coursesuite_api'),
//         'backup',
//         0,
//         $opts
//     ));
