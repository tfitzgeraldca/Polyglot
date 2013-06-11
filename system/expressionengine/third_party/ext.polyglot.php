<?php

class Polyglot_ext {

    var $name       = 'Polyglot';
    var $version        = '0.1';
    var $description    = 'Language control tools';
    var $settings_exist = 'y';
    var $docs_url       = 'http://tfitzgerald.ca/'; // 'http://ellislab.com/expressionengine/user-guide/';

    var $settings       = array();
    var $translation_path = "";
    var $language       = "";
    var $lexicon        = array();
    var $segments       = array();

    /**
     * Constructor
     *
     * @param   mixed   Settings array or empty string if none exist.
     */
    function __construct($settings = '')
    {
        $this->settings = $settings;
        $this->EE =& get_instance();

        $this->translation_path = $this->EE->config->config['tmpl_file_basepath'];
    }

    /**
     * Activate Extension
     *
     * This function enters the extension into the exp_extensions table
     *
     * @see http://ellislab.com/codeigniter/user-guide/database/index.html for
     * more information on the db class.
     *
     * @return void
     */
    function activate_extension()
    {
        $this->settings = array(
            'language_pattern'   => 'none',
            'default_language'  => ''
        );


        $data = array(
            'class'     => __CLASS__,
            'method'    => 'load_language_file',
            'hook'      => 'sessions_start',
            'settings'  => serialize($this->settings),
            'priority'  => 10,
            'version'   => $this->version,
            'enabled'   => 'y'
        );
    
        $this->EE->db->insert('extensions', $data);
    }

    /**
     * Update Extension
     *
     * This function performs any necessary db updates when the extension
     * page is visited
     *
     * @return  mixed   void on update / false if none
     */
    function update_extension($current = '')
    {
        if ($current == '' OR $current == $this->version)
        {
            return FALSE;
        }

        if ($current < '0.1')
        {
            // Update to version 0.1
        }

        $this->EE->db->where('class', __CLASS__);
        $this->EE->db->update(
                    'extensions',
                    array('version' => $this->version)
        );
    }

    /**
     * Disable Extension
     *
     * This method removes information from the exp_extensions table
     *
     * @return void
     */
    function disable_extension()
    {
        $this->EE->db->where('class', __CLASS__);
        $this->EE->db->delete('extensions');
    }

    // --------------------------------
    //  Settings
    // --------------------------------

    function settings()
    {
        $settings = array();

        // Creates a set of radio buttons, one for "Yes" (y), one for "No" (n) and a default of "Yes"
        $settings['language_pattern']      = array('r', array('sd' => "sub-domain", 'segment' => "first segment", 'none' => "none"), 'none');
        $settings['default_language']      = array('i', "", '');

        // General pattern:
        //
        // $settings[variable_name] => array(type, options, default);
        //
        // variable_name: short name for the setting and the key for the language file variable
        // type:          i - text input, t - textarea, r - radio buttons, c - checkboxes, s - select, ms - multiselect
        // options:       can be string (i, t) or array (r, c, s, ms)
        // default:       array member, array of members, string, nothing

        return $settings;
    }

    function get_language() {
        if(isset($this->settings['language_pattern'])) {
            $language_pattern = $this->settings['language_pattern'];
            $detected_language = "";

            switch ($language_pattern) {
                case 'segment':
                    $detected_language = array_shift(explode("/",substr($_SERVER['REQUEST_URI'],1)));
                    break;
                case 'sd':
                    $detected_language = array_shift(explode(".",$_SERVER['HTTP_HOST']));
                    break;
                case 'none':
                default:
                    $detected_language = "";
                    break;
            }

            if($detected_language == "") {
                $detected_language = $this->settings['default_language'];
            }

            $this->language = $detected_language;
            $this->EE->config->_global_vars['current_lang'] = $this->language;
            return $detected_language;
        }
    }

    function load_language_file($lang = "") {
        if (!is_string($lang)) {
            $lang = $this->get_language();
        }

        if( file_exists($this->translation_path.'/lang.'.$lang.'.php') )
        {
            include($this->translation_path.'/lang.'.$lang.'.php');

            $this->lexicon[$lang] = (is_array($L)?$L : array());

            $this->segments[$lang] = (is_array($S)?$S : array());
        }
    }


}
// END CLASS

?>