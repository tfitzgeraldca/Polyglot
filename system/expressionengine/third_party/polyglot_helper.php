<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/**
 * Playa Helper class for ExpressionEngine 2
*/
class Polyglot_Helper {

    /**
     * Constructor
     */
    function __construct()
    {
        $this->EE =& get_instance();
        $this->settings = $this->EE->cache['polyglot']['settings'];
    }

    /*
    find_closest_locale is based on the method findClosestCulture in Globalize.js

        Copyright 2013 jQuery Foundation and other contributors
        http://jquery.com/

        Permission is hereby granted, free of charge, to any person obtaining
        a copy of this software and associated documentation files (the
        "Software"), to deal in the Software without restriction, including
        without limitation the rights to use, copy, modify, merge, publish,
        distribute, sublicense, and/or sell copies of the Software, and to
        permit persons to whom the Software is furnished to do so, subject to
        the following conditions:

        The above copyright notice and this permission notice shall be
        included in all copies or substantial portions of the Software.
    */
    public function find_closest_locale($name = '') {
    
        if ($name == '')
        {
            $name = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        }

        $match = '';
        $cultures = $this->EE->cache['polyglot']['lang'];

        if ( ! $name) {
            //TODO return default culture;
        }
        if (is_string($name)) {
            $name = explode( ',', $name );
        }
        if (is_array($name)) {
            $lang = '';
            $list = $name;
            $l = count($list);
            $languages = array();
            $languages_priority = array();
            for ( $i = 0; $i < $l; $i++ ) {
                $name = trim( $list[$i] );
                $pri = null;
                $parts = explode( ';', $name );
                $lang = $parts[0];
                if (count($parts) === 1) {
                    $pri = 1;
                }
                else {
                    $name = trim( $parts[1] );
                    if (strpos( $name, 'q=' ) === 0) {
                        $name = substr( $name, 2 );
                        $pri = (float) $name;
                        $pri = is_nan( $pri ) ? 0 : $pri;
                    }
                    else {
                        $pri = 1;
                    }
                }
                array_push($languages, $lang);
                array_push($languages_priority, $pri);
            }
            array_multisort($languages_priority, SORT_DESC, SORT_NUMERIC, $languages);
            
            // exact match
            for ($i = 0; $i < $l; $i++)
            {
                $lang = $languages[ $i ];
                if (in_array( $lang, $cultures))
                {
                    return $lang;
                }
            }

            // neutral language match
            for ($i = 0; $i < $l; $i++) {
                $lang = $languages[ $i ];
                do {
                    $index = strrpos($lang, '-');
                    if ($index === FALSE) {
                        break;
                    }
                    // strip off the last part. e.g. en-US => en
                    $lang = substr( $lang, 0, $index );
                    if (in_array( $lang, $cultures))
                    {
                        return $lang;
                    }
                }
                while ( 1 );
            }

            // last resort: match first culture using that language
            
            for ($i = 0; $i < $l; $i++) {
                $lang = $languages[ $i ];
                $index = strpos( $lang, '-' );
                if ($index !== false)
                {
                    $lang = substr($lang, 0, $index);
                }
                foreach ($cultures as $culture)
                {
                    $index = strpos($culture, '-');
                    if ($index !== false)
                    {
                        $match = substr($culture, 0, $index);
                    }
                    else
                    {
                        $match = $culture;
                    }
                    if ($match === $lang) {
                        return $culture;
                    }
                }
            }
        }
        return false; //TODO or should this return the default language
    }

    function cldr($type, $lang)
    {
        if ( ! isset($this->EE->cache['polyglot']['cldr']) )
        {
            $this->EE->cache['polyglot']['cldr'] = (object) array();
        }
        if ( ! property_exists ($this->EE->cache['polyglot']['cldr'], 'main'))
        {
            $this->EE->cache['polyglot']['cldr']->main = (object) array();
        }
        if ( ! property_exists( $this->EE->cache['polyglot']['cldr']->main, $lang))
        {
            $this->EE->cache['polyglot']['cldr']->main->{$lang} = (object) array();
        }

        switch($type)
        {
            case 'datetime':
                if ( ! property_exists( $this->EE->cache['polyglot']['cldr']->main->{$lang}, 'dates'))
                {
                    $success = $this->load_cldr_file('dateFields', $lang);
                    if ($success)
                    {
                        $this->load_cldr_file('ca-gregorian', $lang);
                    }
                    return $success;
                }
                return true; //Already loaded?
                break;
            case 'timezones':
                if ( ! property_exists( $this->EE->cache['polyglot']['cldr']->main->{$lang}->dates, 'timeZoneNames'))
                {
                    $success = $this->load_cldr_file('timeZoneNames', $lang);
                    if ($success)
                    {
                        $this->load_cldr_file('metaZones', '', 'supplemental');
                    }
                    return $success;
                }
                return true; //Already loaded?
                break;
            case 'numbers':
                if ( ! property_exists( $this->EE->cache['polyglot']['cldr']->main->{$lang}, 'numbers'))
                {
                    $success = $this->load_cldr_file('numbers', $lang);
                    return $success;
                }
                return true; //Already loaded?
                break;
            case 'currency':
                if ( ! property_exists( $this->EE->cache['polyglot']['cldr']->main->{$lang}, 'numbers'))
                {
                    $success = $this->load_cldr_file('numbers', $lang);
                    if ($success)
                    {
                        $this->load_cldr_file('currencies', $lang);
                        $this->load_cldr_file('currencyData', '', 'supplemental');
                    }
                    return $success;
                }
                else if ( ! property_exists( $this->EE->cache['polyglot']['cldr']->main->{$lang}->numbers, 'currencies'))
                {
                    $success = $this->load_cldr_file('currencies', $lang);
                    $success = $this->load_cldr_file('currencyData', '', 'supplemental');
                    return $success;
                }
                return true; //Already loaded?
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * Loads a requested CLDR JSON file
     * @param string $file
     * @param string $lang
     * @return null
     */
    function load_cldr_file($file, $lang = '', $branch = 'main')
    {
        $path = $this->EE->cache['polyglot']['settings']['cldr_json_path'].'/'.$branch.'/'.$lang.($lang != ''?'/':'').$file.'.json';

        if (file_exists($path))
        {
            if ($branch == 'main')
            {
                if ( ! property_exists($this->EE->cache['polyglot']['cldr']->main, $lang))
                {
                    $this->EE->cache['polyglot']['cldr']->main->{$lang} = (object) array();
                }
                $json = file_get_contents($path);
                $object = json_decode($json);
                $object = $object->main->{$lang};                
            }
            else
            {
                if ( ! property_exists($this->EE->cache['polyglot']['cldr'], $branch))
                {
                    $this->EE->cache['polyglot']['cldr']->{$branch} = (object) array();
                }
                $json = file_get_contents($path);
                $object = json_decode($json);
                $object = $object->{$branch};
            }
        }
        else
        {
            return false;
        }

        if (property_exists($object, 'dates'))
        {
            if ( ! property_exists($this->EE->cache['polyglot']['cldr']->main->{$lang}, 'dates'))
            {
                $this->EE->cache['polyglot']['cldr']->main->{$lang}->dates = (object) array();
            }

            if (property_exists($object->dates, 'calendars'))
            {
                $this->EE->cache['polyglot']['cldr']->main->{$lang}->dates->calendars = $object->dates->calendars;
            }
            else if (property_exists($object->dates, 'fields'))
            {
                $this->EE->cache['polyglot']['cldr']->main->{$lang}->dates->fields = $object->dates->fields;
            }
            else if (property_exists($object->dates, 'timeZoneNames'))
            {
                $this->EE->cache['polyglot']['cldr']->main->{$lang}->dates->timeZoneNames = $object->dates->timeZoneNames;
            }
        }

        if (property_exists($object, 'numbers'))
        {
            if (property_exists($object->numbers, 'currencies') )
            {
                $this->EE->cache['polyglot']['cldr']->main->{$lang}->numbers->currencies = $object->numbers->currencies;
            }
            else
            {
                $this->EE->cache['polyglot']['cldr']->main->{$lang}->numbers = $object->numbers;
            }
        }

        if (property_exists($object, 'currencyData'))
        {
            $this->EE->cache['polyglot']['cldr']->supplemental->currencyData = $object->currencyData;
        }

        if (property_exists($object, 'metaZones'))
        {
            $this->EE->cache['polyglot']['cldr']->supplemental->metaZones = $object->metaZones;
        }
        return true;
    }

    function load_lexicon_file($lang_key = '', $file = '')
    {
        //If no language is provided, figure out the language to use
        if ( ! is_string($lang_key) OR $lang_key == '')
        {
            $lang_key = $this->EE->cache['polyglot']['current_lang'];
        }

        //If no file is provided, assume it's the site short name
        if ( ! is_string($file) OR $file == '')
        {
            $file = $this->EE->config->item('site_short_name');
        }

        //
        if (file_exists($this->EE->cache['polyglot']['lang_settings'][$lang_key]['file_path'].'/'.$file.'_lang.php'))
        {
            $lang = array();

            include($this->EE->cache['polyglot']['lang_settings'][$lang_key]['file_path'].'/'.$file.'_lang.php');

            if ( ! isset($this->EE->cache['polyglot']['lexicon']) )
            {
                $this->EE->cache['polyglot']['lexicon'] = array();
            }
            if ( ! isset($this->EE->cache['polyglot']['lexicon'][$lang_key]) )
            {
                $this->EE->cache['polyglot']['lexicon'][$lang_key] = array();
            }
            $this->EE->cache['polyglot']['lexicon'][$lang_key][$file] = (is_array($lang) ? $lang : array($lang));

        }
    }


    /**
     * Sets the HTTP Content-Language header for the return
     * @param string $lang  One or more languages
     * @return null
     */
    function set_http_content_language($lang = '')
    {
        if (is_array($lang))
        {
            $lang = implode(', ', $lang);
        }
        header('Content-Language: '.$lang);
    }

    function translate_uri($uri, $lang, $direction = 'to_translation', $absolute_path = FALSE)
    {
        //Load Routes to Rewrite
        $paths = array();
        $paths = $this->EE->cache['polyglot']['lang_settings'][$lang]['segments'];

        //If we're looking to go from translated path to original path, then invert these variables
        if ($direction == 'to_origin')
        {
            $paths = array_flip($paths);
        }

        $uri_segments = explode('/', $uri);
        $new_uri_segments = $uri_segments;

        //Cycle through each route translation
        foreach ($paths as $original_path => $translated_path)
        {
            $original_path_array = explode('/', $original_path);
            $translated_path_array = explode('/', $translated_path);

            //Cycle through the URI segments and see if you find a match for the first item in the original path
            for ($i = 0; $i < count($uri_segments); $i++)
            {
                $is_match = false;

                if ($uri_segments[$i] == $original_path_array[0])
                {
                    //If there's only one segment it's automatically a match
                    if (count($original_path_array) == 1)
                    {
                        $is_match = true;
                        $j = 1;
                    }
                    else
                    {
                        $not_match = false;
                        //Check subsequent segments and ensure all of them are a match
                        for ($j = 1; $j < count($original_path_array); $j++)
                        {
                            if ($uri_segments[$i+$j] != $original_path_array[$j])
                            {
                                $not_match = true;
                            }
                        }
                        $is_match = !$not_match;
                    }
                }
                if ($is_match)
                {
                    for ($k = 0; $k < $j; $k++)
                    {
                        $new_uri_segments[$k+$i] = $translated_path_array[$k];
                    }
                }
            }
        }

        //If moving to the original path and language detection pattern is by first segment, you need to remove that segment
        $url_lang = $this->EE->cache['polyglot']['url_lang'][$lang];
        $pattern = $this->EE->cache['polyglot']['settings']['language_pattern'];
        $new_uri = "";

        if ($direction == 'to_origin')
        {
            if ($pattern == 'segment' && $url_lang == $new_uri_segments[0])
            {
                array_shift($new_uri_segments);
                $new_uri = '/'.implode('/',$new_uri_segments);
            }
            else
            {
                $new_uri = '/'.implode('/',$new_uri_segments);
            }
        }
        //If you're putting a translated path, add the language pattern
        else
        {
            if ($pattern == 'segment' && $absolute_path == FALSE)
            {
                array_unshift($new_uri_segments, $url_lang);
                $new_uri = '/'.implode('/',$new_uri_segments);
            }
            else if ($pattern == 'sd' OR $absolute_path == TRUE)
            {
                if (array_shift(explode('.',$_SERVER['HTTP_HOST'])) != $url_lang)
                {
                    $domain = explode('.',$_SERVER['HTTP_HOST']);
                    array_shift($domain);
                    $uri_prefix = 'http'.(isset($_SERVER['HTTPS'])?'s':'').'://'.$url_lang.implode('.',$domain);
                    $new_uri = $base_uri.$new_uri_segments;
                }
            }
        }

        return $new_uri;
    }

    /**
     * Based on ExpressionEngine's Freeway add-on by Doug Avery
     * Copyright (C) 2011-2013 Doug Avery <doug.avery@viget.com> 
     * Published under GNU General Public Licence version 3 <http://www.gnu.org/licenses/gpl-3.0.html>
     * Repository: <https://github.com/averyvery/Freebie>
     */
    function remove_and_store_params()
    {
        // Store URI for debugging
        $param_pattern  = '#(';    // begin match group
        $param_pattern .=   '\?';    // match a '?';
        $param_pattern .=   '|';   // OR
        $param_pattern .=   '\&';    // match a '?';
        $param_pattern .= ')';    // end match group
        $param_pattern .= '.*$';   // continue matching characters until end of string
        $param_pattern .= '#';    // end match

        $matches = Array();
        preg_match($param_pattern, $this->EE->uri->uri_string, $matches);
        $url_params = (isset($matches[0])) ? $matches[0] : '';
        $this->EE->uri->uri_string = preg_replace($param_pattern, '', $this->EE->uri->uri_string);

        return $url_params;
    }
}