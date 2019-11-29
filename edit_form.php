<?php

class block_coursesuite_api_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

	    $mform->addElement('text', 'config_title', get_string('blocktitle', 'block_coursesuite_api'));
	    $mform->setDefault('config_title', get_string('blockdefault', 'block_coursesuite_api'));
	    $mform->setType('config_title', PARAM_TEXT);

    }
}