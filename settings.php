<?php
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

$opts = array('accepted_types' => array('.mbz'));
$settings->add(new admin_setting_configstoredfile(
        'coursesuite_api/coursebackup',
        get_string('labelbackup', 'block_coursesuite_api'),
        get_string('descbackup', 'block_coursesuite_api'),
        'backup',
        0,
        $opts
    ));
