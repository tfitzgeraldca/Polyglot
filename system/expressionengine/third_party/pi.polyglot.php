<?php

$plugin_info = array(
  'pi_name' => 'Polyglot',
  'pi_version' =>'0.1',
  'pi_author' =>'Tim FitzGerald',
  'pi_author_url' => 'http://tfitzgerald.ca/',
  'pi_description' => 'Language control tools',
  'pi_usage' => Polyglot::usage()
  );

class Polyglot {
	
	public 	$return_data = '';
	private $_ph = array();
	
	/** 
	 * Constructor
	 *
	 * Evaluates case values and extracts the content of the 
	 * first case that matches the variable parameter
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() 
	{
		global $TMPL;
		$this->EE =& get_instance();
		$TMPL = $this->EE->TMPL;

		$lang = $this->lang();
		$lexicon = $this->EE->extensions->OBJ['Polyglot_ext']->lexicon[$lang];

		$key = $TMPL->fetch_param('key');
		if($key == '')
			$key = $TMPL->tagdata;

		$data = "";

		if( !empty( $lexicon ) ) {
			foreach($lexicon as $k => $result) {
				if( $k == $key)
					$data = $result;
			}
		}


		if($data == "") {
			//if it can be a date
				//$data = $this->date($key);
			//if it is a number
			if(is_numeric($key))
				$data = $this->num($key);
			//if it is not anything else
			else
				$data =  $this->EE->TMPL->tagdata;
		}
		$this->return_data = $data;
	}

	public function lang() {
		if(isset($this->EE->config->_global_vars['current_lang']))
			return $this->EE->config->_global_vars['current_lang'];
		else
			return '';
	}

	public function num() {
		global $TMPL;
		$lang = ($TMPL->fetch_param('lang') ? $TMPL->fetch_param('lang') : $this->lang());
		$decimals = ((int)$TMPL->fetch_param('decimals') ? (int)$TMPL->fetch_param('decimals') : 0);
		$dec_point = $TMPL->fetch_param('dec_point');
		$is_currency = $TMPL->fetch_param('currency');
		$currency_pos = $TMPL->fetch_param('currency_pos');
		$thousands_sep = $TMPL->fetch_param('thousands_sep');
		$value = ($TMPL->fetch_param('value') ? $TMPL->fetch_param('value') : $TMPL->tagdata);

		//Only bother with this if the value is numeric; repeat the given value if not
		if(is_numeric($value)) {

			//TODO This should only be run if $this->EE->extensions->OBJ['Polyglot_ext'] doesn't have the language already loaded
			$this->EE->extensions->OBJ['Polyglot_ext']->load_language_file($lang);

			//Open up glossary lexicon
			if(isset($this->EE->extensions->OBJ['Polyglot_ext']->lexicon[$lang])) {
				$L = $this->EE->extensions->OBJ['Polyglot_ext']->lexicon[$lang];
			}
			else {
				$L = array( dec_point => ".", thousands_sep => "&nbsp;");
			}

			//If we're handling a currency
			if($is_currency != false && strtolower($is_currency) != "no" && strtolower($is_currency) != "n") {
				//Override default decimals to 2
				if($TMPL->fetch_param('decimals') == "")
					$decimals = 2;

				//Select symbol
				if(strtolower($is_currency) == 'yes' || strtolower($is_currency) == 'y') {
					$currency_symbol = (isset($L['currency_symbol']) ? $L['currency_symbol'] : "&curren;");
				}
				else {
					$currency_symbol = $is_currency;
				}
				$is_currency = true;

				//Get position
				if($currency_pos == "" && isset($L['currency_pos'])) {
					$currency_pos = $L['currency_pos'];
				}
				$currency_pos = strtolower($currency_pos);
				if($currency_pos != "before" && $currency_pos != "after" && $currency_pos != "decimal") {
					$currency_pos = "before";
				}

				//Set decimal if need be
				if($currency_pos == "decimal" && $dec_point == "")
					$dec_point = $currency_symbol;
			}

			//Get decimal point
			if($dec_point == "") {
				$dec_point = (isset($L['dec_point']) ? $L['dec_point'] : ".");
			}

			//Get thousand separator
			if($thousands_sep == "") {
				$thousands_sep =  (isset($L['thousands_sep']) ? $L['thousands_sep'] : " ");
			}

			$formatted_number = str_replace(" ", "&nbsp;", number_format($value, $decimals, $dec_point, $thousands_sep));

			//Add currency symbol
			if($is_currency) {
				if($currency_pos == 'before')
					$formatted_number = $currency_symbol.$formatted_number;
				else if($currency_pos == 'after' || ($currency_pos == 'decimal' && $decimals == 0))
					$formatted_number = $formatted_number.$currency_symbol;
			}

			return $formatted_number;
		}
		else {
			return $value;
		}

}

	public function date() {
		global $TMPL;
		$lang = ($TMPL->fetch_param('lang') ? $TMPL->fetch_param('lang') : $this->lang());
		$format = $TMPL->fetch_param('format');
		$var = $TMPL->fetch_param('var');

		//Format parameter is required, do not proceed if not set
		if($format) {

			//If var is not set, load current date/time
			if(!$var) {
				$datetime = time();
			}
			else {
				if(is_numeric($var))
					$datetime = (int) $var;
				else
					$datetime = strtotime($var);
				if(!$datetime)
					$datetime = time();
			}

			//TODO This should only be run if $this->EE->extensions->OBJ['Polyglot_ext'] doesn't have the language already loaded
			$this->EE->extensions->OBJ['Polyglot_ext']->load_language_file($lang);
			
			//Open up glossary lexicon
			if(isset($this->EE->extensions->OBJ['Polyglot_ext']->lexicon[$lang])) {
				$L = $this->EE->extensions->OBJ['Polyglot_ext']->lexicon[$lang];

				//If format is actually a glossary key, replace it with the glossary value
				if(isset($L[$format])) {
					$format = $L[$format];
				}

				$formatted_date = date($format, $datetime);

				//Run through and replace any possible words in the formatted date
				$keywords = array("January","February","March","April","May","June","July","August","September","October","November","December","Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday","Mon","Tue","Wed","Thu","Fri","Sat","Sun","st","nd","rd","th","am","pm","AM","PM");

				for($i = 0; $i < count($keywords); $i++) {
					if(isset($L[$keywords[$i]])) {
						$formatted_date = str_replace($keywords[$i], $L[$keywords[$i]], $formatted_date);
					}
				}
			}

	        return $formatted_date;
        }
        else {
        	return false;
        }
	}
	public function path() {
		global $TMPL;
		$output_abs_path = (strtolower($TMPL->fetch_param('domain')) == 'yes' ? TRUE : FALSE);
		$uri = $TMPL->fetch_param('uri');
		$pathlang = ($TMPL->fetch_param('lang') ? $TMPL->fetch_param('lang') : $this->lang());

		//TODO This should only be run if $this->EE->extensions->OBJ['Polyglot_ext'] doesn't have the language already loaded
		$this->EE->extensions->OBJ['Polyglot_ext']->load_language_file($pathlang);
		
		//Replace any translated segments
		if(isset($this->EE->extensions->OBJ['Polyglot_ext']->segments[$pathlang])) {
			$segments = $this->EE->extensions->OBJ['Polyglot_ext']->segments[$pathlang];

			$uri_array = explode("/",$uri);

			if(!count($uri_array)) {
				if(isset($segments[$uri]))
					$uri = $segments[$uri];
			}
			for($i = 0; $i < count($uri_array); $i++) {
				if(isset($segments[$uri_array[$i]]))
					$uri_array[$i] = $segments[$uri_array[$i]];
			}
			$uri = implode("/",$uri_array);
		}

		//Add language handling
        switch ($this->EE->extensions->OBJ['Polyglot_ext']->settings['language_pattern']) {
            case 'segment':
                $path = ($output_abs_path ? 'http://'.$_SERVER['HTTP_HOST']:'').'/'.$pathlang.$uri;
                break;
            case 'sd':
            default:
                $path = 'http://'.$pathlang.implode(".",array_shift(explode(".",$_SERVER['HTTP_HOST']))).$uri;
                break;
        }
        return $path;
	}

	// usage instructions
	public function usage() 
	{
  		ob_start();
?>
-------------------
HOW TO USE
-------------------
1. Install the extension.
2. Configure the extension in the settings menu:
	- Language Pattern: select one
		- sub-domain: it will take the first subdomain in the URL the user accessed the site with as language code (e.g. 'en' for en.domain.com or 'fr' for fr.domain.com)
		- first segment: it will take the first segment (e.g. 'en' for domain.com/en or 'chinese' for domain.com/chinese)
		- none: it will always take your default language and only change languages if you specify a {exp:polyglot.... lang="xx"} parameter
   	- Default Language: a code for your default language (e.g. 'en' if it should default to English when it can't find a language)
3. Create language files in a format lang.xx.php in your EE template folder, where xx is your language code
	- Use lang.sample.php in the Git repository as a starting point
	- Add new text in 'key' => 'value' format in the $L array (L for language text)
	- If you want to translate your URLs, add keys and values to the $R array (R for routing)
4. Begin using the tags in your code.

REPLACE TEXT
---------------
{exp:polyglot key="variable_key" [lang="xx"]}
- Inserts text from the file for the language Polyglot has identified using the key you provide
- Language can be overwritten with the lang parameter

{exp:polyglot [key="variable_key"] [lang="xx"]}
	Default text
{/exp:polyglot}

- Replaces your key with text in the language Polyglot has identified
- If no text can be found, displays the default text
- Language can be overwritten with the lang parameter
- If no key is provided, the content of your tag becomes the key (similar to the _e or trans function in WordPress)

CURRENT LANGUAGE
----------------
{current_lang}
- Returns the language code Polyglot is using while rendering the template, based on the language pattern and default you have provided

TRANSLATE URL
----------------
{exp:polyglot:path uri="/a/b/c" [domain='n']}
- Returns the path, with any translated segments required
- Optional: it can also resolve the domain
	- If you are using sub-domain mode for your language pattern, it will always return the full http://xx.domain.com/...

TIPS
---------------
- When in first-segment mode, consider using the add-on Freebie (http://dvt.ee/freebie) by Doug Avery. You can tell EE to ignore your language codes
- I use the add-on Template Routes (http://dvt.ee/temprout) to handle translated segments and keep them pointing to the same files.
- Translating URLs allows you to optimize your SEO in the language of your audiences

CREDITS
---------------
Thanks go to:
- Patrick Stinnet for making PS Languish, which acted as a starting point for this project;
- Doug Avery for giving the world Freebie, which I use hand in hand with for this in first-segment mode.

	<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}	
}
