<?php

$plugin_info = array(
  'pi_name' => 'Polyglot',
  'pi_version' =>'0.1',
  'pi_author' =>'Tim FitzGerald',
  'pi_author_url' => 'https://github.com/tfitzgeraldca/Polyglot',
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
		$this->EE->load->library('logger');

		$TMPL = $this->EE->TMPL;


        if (! class_exists('Polyglot_Helper'))
        {
            require_once PATH_THIRD.'polyglot/polyglot_helper.php';
        }
        $this->functions = new Polyglot_Helper;

		$key = $TMPL->fetch_param('key');
		if ($key == '')
			$key = $TMPL->tagdata;

		$data = '';

		//Handle string conversion
		//Limiting key lengths to 50, to prevent longer encapsulated pair tag data (such as perhaps within {exp:polyglot:languages} from entering here)
		if ( ! is_numeric($key) && $key != '' && strlen($key) < 50)
		{
			//Get parameters
			$lang = $TMPL->fetch_param('lang', $this->lang());
			
			//Return tag contents if there is no lang value
			if ($lang == '')
			{
				$this->return_data = $TMPL->tagdata;
				return false;
			}

			if ( ! isset($this->EE->cache['polyglot']['lexicon'][$lang]))
				$this->EE->cache['polyglot']['lexicon'][$lang] = array();

			$lexicon = $this->EE->cache['polyglot']['lexicon'][$lang];

			$glossary = $TMPL->fetch_param('glossary', $this->EE->config->item('site_short_name'));

			if ( ! isset($lexicon[$glossary]))
			{
				$this->functions->load_lexicon_file($lang, $glossary);
				$lexicon[$glossary] = $this->EE->cache['polyglot']['lexicon'][$lang][$glossary];
			}
			if (isset($lexicon[$glossary]))
			{
				if (isset($lexicon[$glossary][$key]))
				{
					$data = $lexicon[$glossary][$key];
				}
			}
		}

		if ($data == '')
		{
			//if it can be a date
				//$data = $this->date($key);
			//if it is a number
			if (is_numeric($key))
				$data = $this->num($key);
			//if it is not anything else
			else
				$data =  $TMPL->tagdata;
		}

		//if ($data == '')
		//	$this->return_data = $this->EE->TMPL->no_results();
			$this->return_data = $data;
	}

	public function lang()
	{
		if (isset($this->EE->cache['polyglot']['current_lang']))
			return $this->EE->cache['polyglot']['current_lang'];
		else
			return '';
	}

	public function num()
	{
		global $TMPL;
		$lang = ($TMPL->fetch_param('lang') ? $TMPL->fetch_param('lang') : $this->lang());

		//Return tag contents if there is no lang value
		if ($lang == '')
		{
			return $TMPL->tagdata;
		}

		$numbers_loaded = $this->functions->cldr('numbers', $lang);
		$cldr_locale = (isset($this->EE->cache['polyglot']['lang_settings'][$lang]['cldr_locale']) ? $this->EE->cache['polyglot']['lang_settings'][$lang]['cldr_locale'] : $lang);

		$decimals = ((int)$TMPL->fetch_param('decimals') ? (int)$TMPL->fetch_param('decimals') : 0);
		
		if ($numbers_loaded)
		{
			$dec_point = $this->EE->cache['polyglot']['cldr']->main->{$cldr_locale}->numbers->{'symbols-numberSystem-latn'}->decimal;
			$percent_sign = $this->EE->cache['polyglot']['cldr']->main->{$cldr_locale}->numbers->{'symbols-numberSystem-latn'}->percentSign;
			$thousands_sep =  $this->EE->cache['polyglot']['cldr']->main->{$cldr_locale}->numbers->{'symbols-numberSystem-latn'}->group;
		}
		else
		{
			$dec_point = '.';
			$percent_sign = '%';
			$thousands_sep = ' ';
		}

		if ($TMPL->fetch_param('dec_point'))
		{
			$dec_point = $TMPL->fetch_param('dec_point');
		}
		if ($TMPL->fetch_param('thousands_sep'))
		{
			$thousands_sep = $TMPL->fetch_param('thousands_sep');
		}

		
		$currency_unit = $TMPL->fetch_param('currency');
		$is_currency = $currency_unit != '';
		$currency_pos = $TMPL->fetch_param('currency_pos');
		$value = ($TMPL->fetch_param('value') ? $TMPL->fetch_param('value') : $TMPL->tagdata);

		//Only bother with this if the value is numeric; repeat the given value if not
		if (is_numeric($value))
		{
			$numbers_data = $this->EE->cache['polyglot']['cldr']->main->{$cldr_locale}->numbers;

			//If we're handling a currency
			if ($is_currency)
			{
				//Load the currency information
				$currency_loaded = $this->functions->cldr('currency', $lang);
				if ($currency_loaded)
				{
					$currency_data = $this->EE->cache['polyglot']['cldr']->supplemental->currencyData;
					if (property_exists($numbers_data->currencies, strtoupper($currency_unit)))
					{
						$currency_unit = strtoupper($currency_unit);
						$currency_symbol = $numbers_data->currencies->{$currency_unit}->symbol;
					}
					else
					{
						$currency_symbol = $currency_unit;
					}
					$currency_format = $numbers_data->{'currencyFormats-numberSystem-latn'}->{'standard'}->currencyFormat;
					if (property_exists($currency_data->fractions, $currency_unit))
					{
						$decimals = $currency_data->fractions->{$currency_unit}->{'@digits'};
						$rounding = $currency_data->fractions->{$currency_unit}->{'@rounding'};
					}
				}

				//Override default decimals to 2
				if ($TMPL->fetch_param('decimals') == '')
					$decimals = 2;

				//TODO Get position of currency symbol and position it
				$currency_pos = 'before';
			}

			//TODO Handle cases where the format is other than #,##0.# (such as Indic languages)

			$formatted_number = number_format($value, $decimals, $dec_point, $thousands_sep);

			//Add currency symbol
			if ($is_currency)
			{
				if ($currency_pos == 'before')
					$formatted_number = $currency_symbol.$formatted_number;
				else if ($currency_pos == 'after' OR ($currency_pos == 'decimal' && $decimals == 0))
					$formatted_number = $formatted_number.$currency_symbol;
				else if ($currency_pos == 'decimal' && $decimals > 0)
					$formatted_number = str_replace($dec_point, $currency_symbol, $formatted_number);
			}

			return str_replace(' ', '&nbsp;', $formatted_number);
		}
		else
		{
			return $value;
		}
	}

	public function datetime()
	{
		return $this->date('datetime');
	}

	public function time()
	{
		return $this->date('time');
	}

	public function date($period = 'date')
	{
		global $TMPL;
		$lang = ($TMPL->fetch_param('lang') ? $TMPL->fetch_param('lang') : $this->lang());

		//Return tag contents if there is no lang value
		if ($lang == '')
		{
			return $TMPL->tagdata;
		}

		$format = $TMPL->fetch_param('format');
		$value =  ($TMPL->fetch_param('value') ? $TMPL->fetch_param('value') : $TMPL->tagdata);
		$wrap_html5 = strncasecmp($TMPL->fetch_param('wrap_html5'), 'y', 1) == 0;
		$timezone = ($TMPL->fetch_param('tz') ? $TMPL->fetch_param('tz') : $this->EE->config->item('default_site_timezone'));
		$cldr_locale = (isset($this->EE->cache['polyglot']['lang_settings'][$lang]['cldr_locale']) ? $this->EE->cache['polyglot']['lang_settings'][$lang]['cldr_locale'] : $lang);


		//Load a date-time object, adjusted for date-time (PHP 5)
		try
		{
			$tz_obj = new DateTimeZone($timezone);
		} catch (Exception $e) {
			//TODO Log invalid date time error
			$this->EE->TMPL->log_item('Polyglot: Warning, timezone provided ('.$timezone.') couldn\'t be found, using EE default timezone.');
			$tz_obj = new DateTimeZone($this->EE->config->item('default_site_timezone'));
			//Assuming that EE's timezone is legit.
		}

		//Get date-time
			//If var is not set, load current date/time
			if ( ! $value)
			{
				$datetime = $this->EE->localize->now;
			}
			else
			{
				//If numeric, we assume it's a Unix timestamp in UTC time
				if (is_numeric($value))
				{
					$datetime = (int) $value;
				}
				//If not we convert the text date/time, using EE's default timezone reference
				else
				{
					//EE 2.6+
					if (version_compare(APP_VER, '2.6', '>='))
					{
						$datetime = $this->EE->localize->string_to_timestamp($value);
					}
					else
					{
						$datetime = $this->EE->localize->convert_human_date_to_gmt($value);
					}
				}

				//If there's no timestamp as a result, then use the current time in UTC
				if ( ! $datetime)
				{
					$datetime = $this->EE->localize->now;
					$this->EE->logger->developer("Warning: time couldn't be read, using current time instead.");
				}
			}

		//Build date-time object
		$dt_obj = new DateTime(date('r', $datetime));
		$dt_obj->setTimezone($tz_obj);

		//Load CLDR data
		$datetime_cldr = $this->functions->cldr('datetime', $lang);

		//If we can't load CLDR data
		if ( ! $datetime_cldr)
		{
			//TODO Log error
			//Return ISO format
			return $dt_obj->format('c');
		}
		$calendar = $this->EE->cache['polyglot']['cldr']->main->{$cldr_locale}->dates->calendars->gregorian;


		//If no format is provided, treat it as a short CLDR format
		if ($format == '')
		{
			$format = 'medium';
		}

		//CLDR format codes
		$cldr_short_formats = array('full', 'long', 'medium', 'short');
		$cldr_available_formats = array('d', 'Ed', 'Ehm', 'EHm', 'Ehms', 'EHms', 'Gy', 'GyMMM', 'GyMMMd', 'GyMMMEd', 'h', 'H', 'hm', 'Hm', 'hms', 'Hms', 'M', 'Md', 'MEd', 'MMM', 'MMMd', 'MMMEd', 'ms', 'y', 'yM' , 'yMd', 'yMEd', 'yMMM', 'yMMMd', 'yMMMEd', 'yQQQ', 'yQQQQ');

		if (in_array($format, $cldr_short_formats))
		{
			if ($period == 'datetime')
			{
				$cldr_format_pattern = $calendar->dateTimeFormats->{$format};
				$cldr_format_pattern = str_replace('{1}', $calendar->dateFormats->{$format}, $cldr_format_pattern);
				$cldr_format_pattern = str_replace('{0}', $calendar->timeFormats->{$format}, $cldr_format_pattern);
				$cldr_format_code = $cldr_format_pattern;
			}
			else if ($period == 'date')
			{
				$cldr_format_code = $calendar->dateFormats->{$format};
			}
			else if ($period == 'time')
			{
				$cldr_format_code = $calendar->timeFormats->{$format};
			}
			$format = $cldr_format_code;
			$output = $this->_cldr_dt_format($format, $cldr_locale, $dt_obj);
		}
		else if (in_array($format, $cldr_available_formats))
		{
			//TODO Log replacement in format
			$format = $calendar->dateTimeFormats->availableFormats->{$format};
			$output = $this->_cldr_dt_format($format, $cldr_locale, $dt_obj);
		}
		//If none of the default CLDR format codes are used, then use EE's code formatting (using EE's localization)
		else
		{
			$output = $this->EE->localize->format_date($format, $dt_obj->getTimestamp(), $dt_obj->getTimezone());
		}


		if ($wrap_html5)
		{
			$timestamp = '';
			if ($period == 'date')
				$timestamp = $dt_obj->format('Y-m-j');
			else if ($period == 'datetime')
				$timestamp = $dt_obj->format('c');
			else if ($period == 'time')
				$timestamp = $dt_obj->format('G:i:s');
			if ($timestamp)
			{
				$output = '<time datetime="'.$timestamp.'">'.$output.'</time>';
			}
		}
		return $output;
	}

	private function _cldr_dt_format($fmt, $lang, $dt_obj = null)
	{
		if (is_null($dt_obj))
		{
			$dt_obj = new DateTime($this->EE->localize->now, $this->EE->config->item('default_site_timezone'));
		}
		$dt = $dt_obj->getTimestamp();
		$calendar = $this->EE->cache['polyglot']['cldr']->main->{$lang}->dates->calendars->gregorian;

		//Insert # as a marker of unprocessed CLDR formattag, and escape letters with ''
		$fmt = preg_replace("/'?./", "#$0", $fmt);
		$fmt = preg_replace("/#'.#'/", "$0", $fmt);

		//Always return AD/CE Common Era; Unix datetime doesn't allow for BC/BCE Before Common Era
		$fmt = preg_replace($this->cldr_dtfmt_pattern('G', 5), $calendar->eras->eraNarrow->{1}, $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('G', 4), $calendar->eras->eraAbbr->{1}, $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('G', 1, 3), $calendar->eras->eraNames->{1}, $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('y', 1, 4), $dt_obj->format('Y'), $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('Y', 1, 4), $dt_obj->format('o'), $fmt);
		// u, U, Q, and q not supported
		$fmt = preg_replace($this->cldr_dtfmt_pattern('M', 5), $calendar->months->format->narrow->{$dt_obj->format('n')}, $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('M', 4), $calendar->months->format->wide->{$dt_obj->format('n')}, $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('M', 3), $calendar->months->format->abbreviated->{$dt_obj->format('n')}, $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('M', 2), $dt_obj->format('m'), $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('M', 1), $dt_obj->format('n'), $fmt);

		$fmt = preg_replace($this->cldr_dtfmt_pattern('L', 5), $calendar->months->{'stand-alone'}->narrow->{$dt_obj->format('n')}, $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('L', 4), $calendar->months->{'stand-alone'}->wide->{$dt_obj->format('n')}, $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('L', 3), $calendar->months->{'stand-alone'}->abbreviated->{$dt_obj->format('n')}, $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('L', 2), $dt_obj->format('m'), $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('L', 1), $dt_obj->format('n'), $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('L', 1), $dt_obj->format('W'), $fmt);
		//W: not supported
		$fmt = preg_replace($this->cldr_dtfmt_pattern('D', 1, 3), $dt_obj->format('z'), $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('d', 1, 2), $dt_obj->format('d'), $fmt);
		//F: not supported
		//g: not supported
		$fmt = preg_replace($this->cldr_dtfmt_pattern('[Ee]', 6), $calendar->days->format->short->{strtolower($dt_obj->format('D'))}, $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('[Ee]', 5), $calendar->days->format->narrow->{strtolower($dt_obj->format('D'))}, $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('[Ee]', 4), $calendar->days->format->wide->{strtolower($dt_obj->format('D'))}, $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('[E]', 1, 3), $calendar->days->format->abbreviated->{strtolower($dt_obj->format('D'))}, $fmt);
		$fmt = preg_replace('/#e(#e)?/', $dt_obj->format('w'), $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('c', 6), $calendar->days->{'stand-alone'}->short->{strtolower($dt_obj->format('D'))}, $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('c', 5), $calendar->days->{'stand-alone'}->narrow->{strtolower($dt_obj->format('D'))}, $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('c', 4), $calendar->days->{'stand-alone'}->wide->{strtolower($dt_obj->format('D'))}, $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('c', 3), $calendar->days->{'stand-alone'}->abbreviated->{strtolower($dt_obj->format('D'))}, $fmt);
		$fmt = preg_replace('/#c(#c)?/', $dt_obj->format('w'), $fmt);

		$fmt = preg_replace($this->cldr_dtfmt_pattern('a', 1), $calendar->dayPeriods->format->wide->{($dt_obj->format('G')<12?'am':'pm')}, $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('h', 2), $dt_obj->format('h'), $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('h', 1), $dt_obj->format('g'), $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('H', 2), $dt_obj->format('H'), $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('H', 1), $dt_obj->format('G'), $fmt);
		//K: not supported
		//k: not supported
		//J: ? More research required
		//j: idem
		$fmt = preg_replace($this->cldr_dtfmt_pattern('m', 2), $dt_obj->format('i'), $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('m', 1), (int) $dt_obj->format('i'), $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('s', 2), $dt_obj->format('s'), $fmt);
		$fmt = preg_replace($this->cldr_dtfmt_pattern('s', 1), (int) $dt_obj->format('s'), $fmt);
		//S: not supported
		//A: not supported

		//Handle timezones
		if (preg_match("/#(z|v|Z|V|O|x|X)/", $fmt))
		{
			$tz = $dt_obj->format('e');
			$tz_offset = $dt_obj->getOffset();
			$is_daylight = $dt_obj->format('I');
			$daylight = ($is_daylight ? 'daylight' : 'standard');

			$success = $this->functions->cldr('timezones', $lang);
			if ($success)
			{
				$tz_array = explode('/', $tz);
				$tznames_obj = $this->EE->cache['polyglot']['cldr']->main->{$lang}->dates->timeZoneNames;
				if ( ! property_exists($tznames_obj->zone, $tz_array[0]))
				{
					$tz_array[0] = 'Etc';
					$tz_array[1] = 'Unknown';
				}
				else if ( ! property_exists($tznames_obj->zone->{$tz_array[0]}, $tz_array[1]))
				{
					$tz_array[0] = 'Etc';
					$tz_array[1] = 'Unknown';
				}

				if (property_exists($this->EE->cache['polyglot']['cldr']->supplemental->metaZones->metazoneInfo->timezone->{$tz_array[0]}, $tz_array[1]))
				{
					$meta_tz_array = $this->EE->cache['polyglot']['cldr']->supplemental->metaZones->metazoneInfo->timezone->{$tz_array[0]}->{$tz_array[1]};	
				}
				if (isset($meta_tz_array))
				{
					foreach ($meta_tz_array as $meta_tz)
					{
						if ( ! property_exists($meta_tz->usesMetazone, '@to'))
						{
							$meta_tz_name = $meta_tz->usesMetazone->{'@mzone'};
							break;
						}
					}
					$meta_tz_obj = $this->EE->cache['polyglot']['cldr']->main->{$lang}->dates->timeZoneNames->metazone->{$meta_tz_name};
				}
			}

			//zzzz
			if (preg_match($this->cldr_dtfmt_pattern('z', 4), $fmt))
			{
				if (isset($meta_tz_obj))
				{
					$fmt = preg_replace($this->cldr_dtfmt_pattern('z', 4), $meta_tz_obj->long->{$daylight}, $fmt);
				}
				else
				{
					$fmt = preg_replace($this->cldr_dtfmt_pattern('z', 4), '#O#O#O#O', $fmt);
				}
			}

			//z to zzz
			if (preg_match($this->cldr_dtfmt_pattern('z', 1, 3), $fmt))
			{
				if (property_exists($meta_tz_obj, 'short'))
				{
					$fmt = preg_replace($this->cldr_dtfmt_pattern('z', 1, 3), $meta_tz_obj->short->{$daylight}, $fmt);
				}
				else
				{
					$fmt = preg_replace($this->cldr_dtfmt_pattern('z', 1, 3), '#O', $fmt);
				}
			}

			//vvvv
			if (preg_match($this->cldr_dtfmt_pattern('v', 4), $fmt))
			{
				if (property_exists($meta_tz_obj, 'long'))
				{
					if (property_exists($meta_tz_obj->long, 'generic'))
						$fmt = preg_replace($this->cldr_dtfmt_pattern('v', 4), $meta_tz_obj->long->generic, $fmt);
					else
						$fmt = preg_replace($this->cldr_dtfmt_pattern('v', 4), $meta_tz_obj->long->standard, $fmt);
				}
				else
				{
					$fmt = preg_replace($this->cldr_dtfmt_pattern('v', 4), '#V#V#V#V', $fmt);
				}
			}
			//v
			if (preg_match($this->cldr_dtfmt_pattern('v', 1), $fmt))
			{
				if (property_exists($meta_tz_obj, 'short'))
				{
					if (property_exists($meta_tz_obj->short, 'generic'))
						$fmt = preg_replace($this->cldr_dtfmt_pattern('v', 1), $meta_tz_obj->short->generic, $fmt);
					else
						$fmt = preg_replace($this->cldr_dtfmt_pattern('v', 1), $meta_tz_obj->short->standard, $fmt);
				}
				else
				{
					$fmt = preg_replace($this->cldr_dtfmt_pattern('v', 1), '#V#V#V#V', $fmt);
				}
			}

			if (preg_match($this->cldr_dtfmt_pattern('V', 4), $fmt))
			{
				$fmt = preg_replace($this->cldr_dtfmt_pattern('V', 4), preg_replace('/\{0\}/', $tznames_obj->zone->{$tz_array[0]}->{$tz_array[1]}->exemplarCity, $tznames_obj->regionFormat), $fmt);
			}
			$fmt = preg_replace($this->cldr_dtfmt_pattern('V', 3), $tznames_obj->zone->{$tz_array[0]}->{$tz_array[1]}->exemplarCity, $fmt);
			$fmt = preg_replace($this->cldr_dtfmt_pattern('V', 2), $tz, $fmt);
			//V (not supported)

			//ZZZZZ: -08:00 (or Z if 0)
			$fmt = preg_replace($this->cldr_dtfmt_pattern('Z', 5), $dt_obj->format('P'), $fmt);
			$fmt = preg_replace($this->cldr_dtfmt_pattern('Z', 4), '#O#O#O#O', $fmt);
			$fmt = preg_replace($this->cldr_dtfmt_pattern('Z', 1, 3), '#x#x#x#x', $fmt);

			//XXXXX: -08:00, -07:52:58 (optional seconds field, with colons)
			$fmt = preg_replace($this->cldr_dtfmt_pattern('X', 5), '#'.$dt_obj->format('P'), $fmt);
			//XXXX: -0800, -080025 (with optional seconds field)
			$fmt = preg_replace($this->cldr_dtfmt_pattern('X', 4), '#'.$dt_obj->format('O'), $fmt);
			//XXX: -08:00 (minutes with colon)
			$fmt = preg_replace($this->cldr_dtfmt_pattern('X', 3), '#'.$dt_obj->format('P'), $fmt);
			//XX: -0800 (always with minute fields)
			$fmt = preg_replace($this->cldr_dtfmt_pattern('X', 2), '#'.$dt_obj->format('O'), $fmt);
			//X: -08, +0530, Z (for UTC)
			$fmt = preg_replace($this->cldr_dtfmt_pattern('X', 1), '#'.preg_replace('/00$/', '', $dt_obj->format('O')), $fmt);

			if ($tz_offset == 0)
			{
				$fmt = preg_replace("/#\+.*\b/", 'Z', $fmt);
			}
			//XXXXX: -08:00, -07:52:58 (optional seconds field, with colons)
			$fmt = preg_replace($this->cldr_dtfmt_pattern('x', 5), $dt_obj->format('P'), $fmt);
			//XXXX: -0800, -080025 (with optional seconds field)
			$fmt = preg_replace($this->cldr_dtfmt_pattern('x', 4), $dt_obj->format('O'), $fmt);
			//XXX: -08:00 (minutes with colon)
			$fmt = preg_replace($this->cldr_dtfmt_pattern('x', 3), $dt_obj->format('P'), $fmt);
			//XX: -0800 (always with minute fields)
			$fmt = preg_replace($this->cldr_dtfmt_pattern('x', 2), $dt_obj->format('O'), $fmt);
			//X: -08, +0530, Z (for UTC)
			$fmt = preg_replace($this->cldr_dtfmt_pattern('x', 1), preg_replace("/00$/", '', $dt_obj->format('O')), $fmt);

			//OOOO
			if (preg_match($this->cldr_dtfmt_pattern('O', 4), $fmt))
			{
				if ($tz_offset == 0)
				{
					$fmt = preg_replace($this->cldr_dtfmt_pattern('O', 4), $tznames_obj->gmtZeroFormat, $fmt);
				}
				else
				{
					$fmt = preg_replace($this->cldr_dtfmt_pattern('O', 4), preg_replace("/\{0\}/", $dt_obj->format('O'), $tznames_obj->gmtFormat), $fmt);
				}
			}

			//O
			if (preg_match($this->cldr_dtfmt_pattern('O', 1), $fmt))
			{
				if ($tz_offset == 0)
				{
					$fmt = preg_replace($this->cldr_dtfmt_pattern('O', 1), $tznames_obj->gmtZeroFormat, $fmt);
				}
				else
				{
					$fmt = preg_replace($this->cldr_dtfmt_pattern('O', 1), preg_replace("/\{0\}/", sprintf("%+d", $tz_offset/3600), $tznames_obj->gmtFormat), $fmt);
				}
			}
		}

		$fmt = preg_replace("/#'/", '', $fmt);
		$fmt = preg_replace("/#/", '', $fmt);

		return $fmt;
	}

	private function cldr_dtfmt_pattern($letter, $count, $optional_count = 0)
	{
		$pattern = '';
		for ($i = 0; $i < $count; $i++)
		{
			$pattern = $pattern . '#'. $letter;
		}
		$optional_count = ($optional_count - $count);
		for ($i = 0; $i < $optional_count; $i++)
		{
			$pattern = $pattern . '(#'. $letter.')?';
		}
		$pattern = '/'.$pattern.'/';
		return $pattern;
	}

	public function path()
	{
		global $TMPL;
		$output_abs_path = (strncasecmp($TMPL->fetch_param('absolute'), 'y', 1) == 0);
		$uri = $TMPL->fetch_param('uri');
		$lang = $TMPL->fetch_param('lang', $this->lang());

		//Return path given if there is no lang value
		if ($lang == '')
		{
			return $uri;
		}

		if($uri == '')
		{
			$uri = '/';
		}

		return $this->functions->translate_uri($uri, $lang, 'to_translation', $output_abs_path);
	}


	public function debug()
	{
		print_r($this->EE->cache['polyglot']);

		return '';
	}


	public function detect_browser_lang()
	{
		return $this->functions->find_closest_locale();
	}

	public function languages()
	{
		global $TMPL;
		$variables = array();
		$current_lang = $this->lang();
		$exclude_current_lang = (strncasecmp($TMPL->fetch_param('exclude_active', 'n'), 'y', 1) == 0);

		foreach ($this->EE->cache['polyglot']['lang_settings'] as $lang => $settings)
		{
			if( ! ($exclude_current_lang && $lang == $current_lang))
			{
				$variable_row = array(
					'lang'  => $lang,
					'is_active' => ($lang == $current_lang),
					'cldr_locale' => isset($settings['cldr_locale']) ? $settings['cldr_locale'] : $lang,
					'url_lang' => isset($settings['url_lang']) ? $settings['url_lang'] : $lang,
					'lang_url' => $this->functions->translate_uri('/', $lang),
					'name' => $settings['lang_name'],
					'dir' => $settings['dir'],
					'ee_language' => isset($settings['ee_language']) ? $settings['ee_language'] : "english"
				);

				$variables[] = $variable_row;
			}
		}

		return $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, $variables);
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
3. Create language files in a format xxx_lang.php in your EE template folder, where xxx is your site's name (usually default_site)
	- Use lang.sample.php in the Git repository as a starting point
	- Add new text in 'key' => 'value' format in the $lang array
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
{polyglot:lang}
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
