<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Polyglot_ext
{

    var $name       = 'Polyglot';
    var $version        = '0.1';
    var $description    = 'Language control tools';
    var $settings_exist = 'y';
    var $docs_url       = 'http://tfitzgerald.ca/'; // 'http://ellislab.com/expressionengine/user-guide/';

    var $settings       = array();
    var $translation_path = '';
    var $lexicon        = array();
    var $segments       = array();
    var $functions      = null;

    /**
     * Constructor
     *
     * @param   mixed   Settings array or empty string if none exist.
     */
    function __construct($settings = '')
    {
        $this->settings = $settings;
        $this->EE =& get_instance();

        $this->EE->cache['polyglot']['default_language'] = ( is_array($settings) ? $settings['default_language'] : '' );

        $this->EE->cache['polyglot']['settings'] = $settings;

        if ( ! class_exists('Polyglot_Helper'))
        {
            require_once PATH_THIRD.'polyglot/polyglot_helper.php';
        }
        $this->functions = new Polyglot_Helper;
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
            'default_language'  => '',
            'cldr_json_path' => PATH_THIRD_THEMES.'polyglot/json',
            'language_path' => APPPATH.'language',
            'variable_prefix' => 'polyglot:'
        );
        $data = array();

        $data[] = array(
            'class'     => __CLASS__,
            'method'    => 'init_polyglot',
            'hook'      => 'sessions_start',
            'settings'  => serialize($this->settings),
            'priority'  => 10,
            'version'   => $this->version,
            'enabled'   => 'y'
        );

        $data[] = array(
            'class'     => __CLASS__,
            'method'    => 'ee_debug_toolbar_add_panel',
            'hook'      => 'ee_debug_toolbar_add_panel',
            'settings'  => '',
            'priority'  => 500,
            'version'   => $this->version,
            'enabled'   => 'y'
        );
    
        foreach ($data AS $ex)
        {
            $this->EE->db->insert('extensions', $ex);   
        }
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

        $settings['language_pattern']   = array('r', array('sd' => 'sub-domain', 'segment' => 'first segment', 'none' => 'none'), 'none');
        $settings['default_language']   = array('i', '', '');
        $settings['cldr_json_path']     = array('i','', PATH_THIRD_THEMES.'polyglot/json');
        $settings['language_path']      = array('i', '', APPPATH.'language');
        $settings['variable_prefix']    = array('i', '', 'polyglot:');

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

    function init_polyglot()
    {
        $var_prefix = $this->settings['variable_prefix'];

        //Get start time (for logging puroses)
        $start_time = microtime(TRUE);

        //Load the plugin's own language file
        $this->EE->lang->loadfile('polyglot');

        //Load available language settings
        $this->load_languages();

        //Get requested language
        $current_lang = $this->get_language_from_pattern();

        //Handle no matches found
        if($current_lang == '')
        {
            //Use default language if one is provided
            if($this->settings['default_language'] != '')
            {
                $current_lang = $this->settings['default_language'];
            }
            //If no default is provided, set variables to empty and stop processing
            else
            {
                $this->EE->config->_global_vars[$var_prefix.'current_lang'] = '';
                $this->EE->config->_global_vars[$var_prefix.'dir'] = 'ltr';
                $this->EE->config->_global_vars[$var_prefix.'lang'] = '';
                $this->EE->config->_global_vars[$var_prefix.'url_lang'] = '';
                $this->EE->config->_global_vars[$var_prefix.'language'] = '';
                for($i = 1; $i <= 10; $i++)
                {
                    $this->EE->config->_global_vars[$var_prefix.'segment_'.$i] = '';
                }
                return;
            }
        }

        $this->EE->cache['polyglot']['current_lang'] = $current_lang;

        //Load default language file
        $this->functions->load_lexicon_file();

        //Set early-parsed variables
        //TODO
        $this->EE->config->_global_vars[$var_prefix.'current_lang'] = $current_lang;
        $this->EE->config->_global_vars[$var_prefix.'lang'] = $current_lang;
        $this->EE->config->_global_vars[$var_prefix.'dir'] = $this->EE->cache['polyglot']['lang_settings'][$current_lang]['dir'];
        $this->EE->config->_global_vars[$var_prefix.'url_lang'] = $this->EE->cache['polyglot']['lang_settings'][$current_lang]['url_lang'];
        $ee_language = $this->EE->cache['polyglot']['lang_settings'][$current_lang]['ee_langauge'];
        $this->EE->config->_global_vars[$var_prefix.'language'] = $ee_language;
        $this->EE->config->set_item('deft_lang', $ee_language);
        $this->EE->config->set_item('xml_lang', $current_lang);


        //Set HTTP Language Header
        $this->functions->set_http_content_language($this->EE->cache['polyglot']['current_lang']);

        //Handle re-routing
        $this->reroute_uri();

        //DEBUG
        //echo 'Profile Trigger: '.$this->EE->config->item('profile_trigger') . '\n'.
        //        'Reserved Category Word: ' . $this->EE->config->item('reserved_category_word').'\n';
        //print_r($this->EE->cache['polyglot']);
        //print_r($this->EE);

        //Report execution time
        log_message('Polyglot: '.sprintf($this->EE->lang->line('polyglot_ext_exec_time'), (microtime(TRUE)-$start_time)), 'INFO');
    }


    /**
     * Returns the language key according to the language pattern selected
     * @return the language detected
     */
    function get_language_from_pattern()
    {
        if(isset($this->settings['language_pattern']))
        {
            $language_pattern = $this->settings['language_pattern'];
            $detected_language_url = '';

            switch ($language_pattern)
            {
                case 'segment':
                    $detected_language_url = array_shift(explode('/', $this->EE->uri->uri_string));
                    break;
                case 'sd':
                    $detected_language_url = array_shift(explode('.', $_SERVER['HTTP_HOST']));
                    break;
                case 'none':
                default:
                    $detected_language_url = '';
                    break;
            }

            if ($detected_language_url == '')
            {
                $detected_language_url = $this->settings['default_language'];
            }
            else
            {
                //Check the available languages, and see if any of them have a lang_url defined that this should correspond to
                $found_language_url = '';
                foreach ($this->EE->cache['polyglot']['url_lang'] as $defined_lang => $defined_lang_url)
                {
                    if ($detected_language_url == $defined_lang_url)
                    {
                        $found_language_url = $defined_lang;
                    }
                }
                if ($found_language_url == '')
                {
                    $detected_language_url = $this->settings['default_language'];
                }
                else
                {
                    $detected_language_url = $found_language_url;
                }
            }

            return $detected_language_url;
        }
    }

    function load_languages()
    {
        $language_dirs = array_filter( glob($this->settings['language_path'].'/*'), 'is_dir' );

        foreach ($language_dirs as $dir)
        {
            //Load config file for this language
            $configfile = $dir.'/'.$this->EE->config->item('site_short_name') . '_config.php';
            if (file_exists($configfile))
            {
                $lang_config = array();
                include($configfile);

                $lang_config['file_path'] = $dir;
                $this->EE->cache['polyglot']['lang_settings'][$lang_config['lang']] = $lang_config;
                if ( ! isset($lang_config['url_lang']))
                {
                    $this->EE->cache['polyglot']['lang_settings'][$lang_config['lang']]['url_lang'] = $lang_config['lang'];
                }
                if ( ! isset($lang_config['dir']))
                {
                    $this->EE->cache['polyglot']['lang_settings'][$lang_config['lang']]['dir'] = 'ltr';
                }
                $this->EE->cache['polyglot']['lang'][$lang_config['lang']] = $lang_config['lang'];

                $this->EE->cache['polyglot']['url_lang'][$lang_config['lang']] = (isset($lang_config['url_lang']) ? $lang_config['url_lang'] : $lang_config['lang']);
            }                
        }
    }

    function reroute_uri()
    {
        //Check if we are a place where we use EE URI template routing (e.g. not in the Control Panel)
        if (isset($this->EE->uri->uri_string))
        {
            $lang = $this->EE->cache['polyglot']['current_lang'];
            $original_uri = $this->EE->uri->uri_string;

            //Store Original URI and make early-parsed variables of them?
            $uri_array = explode('/', $original_uri);
            for ($i = 0; $i < 11; $i++)
            {
                    $value = (isset($uri_array[$i])) ? $uri_array[$i] : '';
                    $count = $i + 1;
                    $this->EE->config->_global_vars[$this->settings['variable_prefix']. 'segment_' . $count] = $value;
            }

            //TODO: replace segment keywords
            //$this->EE->config->item('profile_trigger');
            //$this->EE->config->item('reserved_category_word');

            //If you're using first-segment language identifier (e.g. /en and /fr)
            //Remove that first segment from the equation

            $url_params = $this->functions->remove_and_store_params();

            //Get original URI
            $new_uri = $this->functions->translate_uri($original_uri, $lang, 'to_origin');

            //Apply original URI
            $this->EE->uri->uri_string = $new_uri;
            $this->EE->uri->uri_string .= $url_params;

            //TODO: Necessary? Doug Avery had this...
            //$this->EE->uri->uri_string = $this->EE->uri->uri_string;

            //Submit new URI for parsing
            $this->EE->uri->segments = array();
            $this->EE->uri->rsegments = array();
            $this->EE->uri->_explode_segments();
            $this->EE->uri->_reindex_segments();

            //TODO: Output for EE Debug Toolbar?

        }

    }

    public function ee_debug_toolbar_add_panel(array $panels, array $view)
    {
        $panels = ($this->EE->extensions->last_call != '' ? $this->EE->extensions->last_call : $panels);
        
        $panels['polyglot_panel'] = new Eedt_panel_model();
        $panels['polyglot_panel']->set_name('polyglot_panel');
        $panels['polyglot_panel']->set_button_icon(URL_THIRD_THEMES.'polyglot/images/eedt_icon.png');

        if(isset($this->EE->cache['polyglot']['current_lang']))
        {
            $panels['polyglot_panel']->set_button_label($this->EE->cache['polyglot']['lang_settings'][$this->EE->cache['polyglot']['current_lang']]['lang_name']);
        }
        else
        {
            $panels['polyglot_panel']->set_button_label($this->EE->lang->line("eedt_no_lang"));
        }
        
        $panels['polyglot_panel']->set_panel_contents($this->EE->load->view('partials/eedt', array(), TRUE));
        
        return $panels;
    }

}
// END CLASS
