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
 * language strings used in coursesuite_api block.
 *
 * @package    block_coursesuite_api
 * @copyright  2020 tim st.clair
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Coursesuite API block';
$string['coursesuite_api'] = 'Coursesuite API';
$string['coursesuite_api:addinstance'] = 'Add a new Coursesuite API block';

$string['blocktitle'] = 'Block title';
$string['blockdefault'] = 'Create a Coursesuite Course';

$string['headerconfig'] = 'Configure Coursesuite API Block';
$string['descconfig'] = '<p class="alert alert-info">You need to purchase a licence or subscription at <a href="https://www.coursesuite.com/" target="_blank">www.coursesuite.com <i class="fa fa-external-link"></i></a> to get your apikey</p>

<p class="alert alert-warning">The HTTP_AUTHORIZATION header needs to be passed through for this web server. You may need your web server administrators\' assistance to set the values as below:
<br/><i>nginx</i>: <code>fastcgi_param HTTP_AUTHORIZATION $http_authorization;</code>
<br/><i>apache</i>: <code>SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1</code></p>';

$string['labelapikey'] = 'API key';
$string['descapikey'] = 'Identifies you to the Coursesuite server';

$string['labelsecretkey'] = 'Secret key';
$string['descsecretkey'] = 'A private password which verifies your ownership of the API Key';

$string['labelbackup'] = 'Course Backup Template';
$string['descbackup'] = 'A moodle course backup to inject the content into. Needs more information written here.';

$string['labeldebug'] = 'Debug mode';
$string['labeldebugdesc'] = 'Force re-caching, show warnings & errors';