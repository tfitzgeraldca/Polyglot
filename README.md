POLYGLOT ADD-ON FOR EXPRESSIONENGINE, READ-ME
==============================================

by Tim FitzGerald
* Twitter: @tfitzgee
* http://tfitzgerald.ca/

What Polyglot Does
-------------------
Polyglot allows you to:

* Set up a language variable on a single instance of ExpressionEngine
* Attriubte that language value dynamically using a rule, such as first segment (e.g. `site.com/en`) or sub-domain (e.g. `en.site.com`)
* Move text out of the templates and into language files (glossaries)
* Load text appropriate for a given language
* Present dates and numbers in the appropriate format for a given language

What it doesn't do
-------------------

Equally important to using a tool is to understand its limitations.

* It doesn't use cookies or session varibales to retain user language preference
* It doesn't manage the language of your content
	* For my own site I've create a channel field called `language` with a P&T dropdown list, that holds the language code corresponding to the content, then include `search:language="{current_lang}"` parameter in my `{exp:channel:entries}` tags
* It doesn't link entries of different languages together for you
	* This can be done using EE's Relationship fieldtype or a plugin like Playa
* It doesn't store string translations in your database, and does not allow string translation management in your EE Control Panel
* It has not been tested for Multisite Manager (MSM).
	* One of the ideas here is that you can run different langauge versions of a single site without going through multiple instances with MSM.
	* Feedback on that is greatly welcome.

For many of these features, consider the following modules:
* EE Harbor's Transcribe (http://eeharbor.com/transcribe)
* Bold Minded's Publisher full edition (the lite version does not handle Multilingual) (http://boldminded.com/add-ons/publisher)


Not yet but maybe one day in a later version...

* It doesn't detect on full domains (e.g. mydomain.com = 'en', monsiteweb.com = 'fr', mijnwebsite.be = 'nl')
* It doesn't detect on query string variables (e.g. mydomain.com/home?lang=de)

Set Up
-------------------

1. Install the extension.
2. Configure the extension in the settings menu:
	- Language Pattern: select one
		- sub-domain: it will take the first subdomain in the URL the user accessed the site with as language code (e.g. `en` for `en.domain.com` or `fr` for `fr.domain.com`)
		- first segment: it will take the first segment (e.g. `en` for `domain.com/en` or `chinese` for `domain.com/chinese`)
		- none: it will always take your default language and only change languages if you specify a `{exp:polyglot.... lang="xx"}` parameter
   	- Default Language: a code for your default language (e.g. `en` if it should default to English when it can't find a language)
3. Create language files in a format `lang.xx.php` in your EE template folder, where `xx` is your language code
	- Add new text in `'key' => 'value'` format in the $L array (L for language text)
	- If you want to translate your URLs, add keys and values to the $R array (R for routing)
4. Begin using the tags in your code.

Replace Text
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

```
{exp:polyglot}
	(numeric value)
{/exp:polyglot}
```

- See `{exp:polyglot:num}` below

Format Numbers
---------------

	{exp:polyglot:num value="9999.999" [decimals="n"] [currency="y|n|$|..."] [currency_pos="before|after|decimal"] [thousands_sep=" |.|,"] [dec_point=".|,"] [lang="xx"]}

- Formats your number with the appropriate thousands separator, decimal point and if required, currency symbol Inserts text from the file for the language Polyglot has identified using the key you provide
- If `value` is not numeric, it will return the value without any formatting
- If no decimal is provided, by default it is rounded to the nearest whole number (1.999 => 2) or if `currency` is set, nearest hundredth (1.989 => 1.99)
- `currency` can be a boolean (y|n) switch, or a value such as '$' or '&euro;'. If y, it will take the key `'currency_symbol'` from your $L array in your glossary file, or else `&curren;` by default. Otherwise default value is n.
- `currency_pos` will either place the currency symbol before ($125.99), after (125.99&euro;) or in the place of the decimal (125Fr99). By default it will take the `currency_pos` value from your $L array in your glossary file; if unset it will place it before the number
- `thousands_sep` is optional; by default it will take the `thousands_sep` value from your $L array, or if unset will place a non-breaking space `&nbsp;`. 
- `thousands_sep` can only be one character; if you put a space, it will be replaced with a `&nbsp;` non-breaking space for you.
- `dec_point` is optional; by default it will take the `dec_point` value from your $L array, or if unset will place a `.` period decimal point. It can only be one character long.
- Language can be overwritten with the lang parameter

```
{exp:polyglot:num [decimals="n"] [currency="y|n|$|..."] [currency_pos="before|after|decimal"] [thousands_sep=" |.|,"] [dec_point=".|,"] [lang="xx"]}
	(numeric value)
{/exp:polyglot:num}
```

- Same as above, but will format the wrapped value
- If the wrapped value is not numeric, it will return the same value without any formatting

Current Language
----------------

	{current_lang}

- Returns the language code Polyglot is using while rendering the template, based on the language pattern and default you have provided

Translate URL
----------------

	{exp:polyglot:path uri="/a/b/c" [domain='n'] [lang='xx']}

- Returns the path, with any translated segments required
- Optional: it can also resolve the domain
	- If you are using sub-domain mode for your language pattern, it will always return the full http://xx.domain.com/...
- Optional: you can specify another language. Useful for language toggle links!

Format Date/Time
----------------

	{exp:polyglot:date var="datetime" format="x" [lang='xx']}

- Returns the date in the format specified
- `var` can be any date/time field that PHP's strtotime() function can interpret. See here: http://www.php.net/manual/en/function.strtotime.php
- If `var` is not specified, it will use the current date/time
- `format` can be:
	- A format string as used by PHP's date() function. See options here: http://www.php.net/manual/en/function.date.php
	- A key in your glossary that Polyglot will use (e.g. if you have a key `'long_format' => 'j M Y, G:i'` and you call `{exp:polyglot:date format="long_format"}`, you will get the current date in a format like `8 June 2013, 23:34`)
	- If no valid format is provided, it will print out in PHP's default format;
- Any month names (e.g. 'January'), weekday names ('Monday') or abbreviations ('Jan', 'Mon') will be translated using your glossary file (e.g. if you have `'Jan' => 'Jän.', 'Thu' => 'Do.'` and your format was `D j. M Y` then 2013-01-03 ("Thu 3 Jan 2013" in English) would be rendered `Do. 3 Jän. 2013`)
- Note: this function does nothing special with handling different timezones (e.g. won't convert a date/time in US Eastern Standard Time (GMT-5) to Central European Time (GMT+1))

Tips
---------------

- When in first-segment mode, consider using the add-on Freebie (http://dvt.ee/freebie) by Doug Avery. You can tell EE to ignore your language codes
- I use the add-on Template Routes (http://dvt.ee/temprout) to handle translated segments and keep them pointing to the same template files.
- Translating URLs allows you to optimize your SEO in the language of your audiences
- I recommend using standard language tags and subtags as your `lang` variables. This is practical as it allows you to identify your content (e.g. `<html lang="{current_lang}">` dynamically as you go)
	- W3C has a (surprisingly) good reference doc for this: (http://www.w3.org/International/questions/qa-choosing-language-tags)

Credits
---------------

Special thanks go to:

- Patrick Stinnett for making PS Languish, which acted as a starting point for this project, and for his immeasurable help getting me into EE;
- Doug Avery for giving the world Freebie, which I use hand in hand with for this in first-segment mode.
