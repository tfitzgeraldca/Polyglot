# About Polyglot

Polyglot is an ExpressionEngine add-on that enables developers to create sites for multiple languages and locales. It helps you manage the translation of your templates, localize date, time, number and currency formats.

## What Polyglot Does

Polyglot allows you to:

* Set up a language variable on a single instance of ExpressionEngine
* Attriubte that language value dynamically using a rule, such as first segment (e.g. `site.com/espanol `) or sub-domain (e.g. `en.site.com`)
* Move text out of the templates and into language files (glossaries)
* Load text appropriate for a given language
* Present dates and numbers in the appropriate format for a given language
* Keep these settings and translations stored in files for easier version-controlling, using a system like Git or SVN, and easier sharing

## What Polyglot Doesn't Do
Equally important to using a tool is understanding its limitations.

* It doesn't use cookies or session varibales to retain user language preference. This makes for bad SEO.
* It doesn't manage the language of your content for you directly. See techniques on how to do it in different ways.
* It doesn't link entries of different languages together for you
	* This can be done using EE's Relationship fieldtype or a plugin like Playa
* It doesn't store string translations in your database, and does not allow string translation management in your EE Control Panel
* It has not been tested for Multisite Manager (MSM).
	* One of the ideas here is that you can run different langauge versions of a single site without going through multiple instances with MSM.
	* Feedback on that is greatly welcome.
* It won't actually do currency conversions, but it will help ensure any amounts you display are properly formatted for the language you're displaying 

For many of these features, consider the following modules:
* EE Harbor's Transcribe (http://eeharbor.com/transcribe)
* Bold Minded's Publisher full edition (the lite version does not handle Multilingual) (http://boldminded.com/add-ons/publisher)

Not yet but maybe one day in a later version...

* It doesn't detect on full domains (e.g. mydomain.com = 'en', monsiteweb.com = 'fr', mijnwebsite.be = 'nl')
* It doesn't detect on query string variables (e.g. mydomain.com/home?lang=de)

## Design Considerations In Making Polyglot
After studying and using a number of translation solutions available in ExpressionEngine, I concluded there was room for *yet another* multilingual add-on and created Polyglot with the following goals in mind:

* The solution should be file-driven rather than database-driven.
* It should make use of existing industry structures (e.g. CI's $lang files) and solutions (Unicode's CLDR) whenever possible.
* It should be lightweight and only load the data it needs.
* It should be fast. It should keep what it needs in memory to prevent re-reading files or duplication of effort.
* It should keep tags short and re-use familiar terms (e.g. `lang` like in HTML) for maintainable templates.
* It should be permissive; anticipating developers' needs and enabling them to fill it without forcing them into necessarily using the solution it provides. People can take from it what they need and ignore what they don't want.
* The solution should be free and open, for others to learn from it, and for me to from other people's insights.
* It should not directly compete with other paid solutions (which are quality products) but rather look to complement them and help "grow the pie."

## What is CLDR?
From Wikipedia:
>The Common Locale Data Repository Project, often abbreviated as CLDR, is a project of the Unicode Consortium to provide locale data in the XML format for use in computer applications. CLDR contains locale specific information that an operating system will typically provide to applications. ... The information is currently used in International Components for Unicode, Apple's Mac OS X, OpenOffice.org, and IBM's AIX, among other applications and operating systems.
Source: <http://en.wikipedia.org/wiki/CLDR>

Polyglot currently uses a release of CLDR in JSON format for the following applications:
* Translations for currency names, including singular/plural modifications.
* Translations for weekday, month, era, period of day, in full and abbreviated forms.
* Translations for timezones and example cities (or similar) for timezones.
* Patterns for formatting/parsing dates or times of day.
* Patterns for formatting/parsing numbers.

The CLDR data is stored in the themes directory so that if you decide to refer to the same data in your JavaScript, you can.

# Installation and Configuration
## Installing Polyglot

1. Download the code from GitHub. <https://github.com/tfitzgeraldca/Polyglot>
2. Copy `/system/expressionengine/third_party/polyglot` files to your site's system folder.
3. Copy `/themes/third_party/polyglot` files to your site's themes folder.
4. On your site's ExpressionEngine Control Panel, go to Add-ons > Extensions and on the row for Polyglot, click on "Enable?"

## Configure the Extension Using the Control Panel
2. Configure the extension in the settings menu:
	- Language Pattern: select one
		- sub-domain: it will take the first subdomain in the URL the user accessed the site with as language code (e.g. `en` for `en.domain.com` or `fr` for `fr.domain.com`)
		- first segment: it will take the first segment (e.g. `en` for `domain.com/en` or `chinese` for `domain.com/chinese`)
		- none: it will always take your default language and only change languages if you specify a `{exp:polyglot.... lang="xx"}` parameter
   	- Default Language: a code for your default language (e.g. `en` if it should default to English when it can't find a language)

## Configuring the Extension Using the Site Config File
```
$config['polyglot_off'] = 'no';

```
## Configuring the Languages

## Creating a Glossary File

3. Create language files in a format `lang.xx.php` in your EE template folder, where `xx` is your language code
	- Add new text in `'key' => 'value'` format in the $L array (L for language text)
	- If you want to translate your URLs, add keys and values to the $R array (R for routing)
4. Begin using the tags in your code.

## Customizing Localizations in CLDR
TODO

# Tag Reference
## {exp:polyglot} - Translate Text

	{exp:polyglot key="variable_key" [lang="xx"]}

- Inserts text from the file for the language Polyglot has identified using the key you provide
- Language can be overwritten with the lang parameter

```
{exp:polyglot [key="variable_key"] [lang="xx"]}
	Default text
{/exp:polyglot}
```
- Replaces your key with text in the language Polyglot has identified
- If no text can be found, displays the default text
- Language can be overwritten with the lang parameter
- If no key is provided, the content of your tag becomes the key (similar to the `_e` or `trans` function in WordPress)

```
{exp:polyglot}
	(numeric value)
{/exp:polyglot}
```

- See `{exp:polyglot:num}` below

## {exp:polyglot:num} - Format Numbers

	{exp:polyglot:num value="9999.999" [decimals="n"] [currency="USD|EUR|JPY|$|..."] [thousands_sep=" |.|,"] [dec_point=".|,"] [lang="xx"]}

- Formats your number with the appropriate thousands separator, decimal point and if required, currency symbol Inserts text from the file for the language Polyglot has identified using the key you provide
- If `value` is not numeric, it will return the value without any formatting
- If no decimal is provided, by default it is rounded to the nearest whole number (1.999 => 2) or if `currency` is set, nearest hundredth (1.989 => 1.99)
- `currency`is optional, and can be an ISO 4217 format three-letter code (<http://en.wikipedia.org/wiki/ISO_4217>). If provided the amount will be formatted in the proper way with the appropriate number of decimal places (unless you provide a `decimals` parameter value). If the currency can't be found or if you pass a symbol like `'$'` or` '&euro;'`, then it won't do any lookups and simply provide that symbol in the right format with a default of 2 decimal places. By default, Polyglot does not treat your number as a currency amount.
- `thousands_sep` is optional; by default it will take the `thousands_sep` value from your language's setting in CLDR. 
- `thousands_sep` can only be one character; if you put a space, it will be replaced with a `&nbsp;` non-breaking space for you.
- `dec_point` is optional; by default it will take the `dec_point` value from your language's setting in CLDR. It can only be one character long.
- Language can be overwritten with the `lang` parameter

```
{exp:polyglot:num [decimals="n"] [currency="y|n|$|..."] [thousands_sep=" |.|,"] [dec_point=".|,"] [lang="xx"]}
	(numeric value)
{/exp:polyglot:num}
```

- Same as above, but will format the wrapped value
- If the wrapped value is not numeric, it will return the same value without any formatting

## {polyglot:lang} Current Language Code

	{polyglot:lang}

- Returns the language code Polyglot is using while rendering the template, based on the language pattern and default you have provided

## {exp:polyglot:path} - Translate URL

	{exp:polyglot:path uri="/a/b/c" [domain='n'] [lang='xx']}

- Returns the path, with any translated segments required
- Optional: it can also resolve the domain
	- If you are using sub-domain mode for your language pattern, it will always return the full http://xx.domain.com/...
- Optional: you can specify another language. Useful for language toggle links!

## {exp:polyglot:date}, {exp:polyglot:time} and {exp:polyglot:datetime} - Format Date/Time

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

# Techniques And Use Cases
## Creating a Portal
## Autoredirect To Best-Matching Home Page
## Using With Structure
Based on technique documented by Structure, here: <http://buildwithstructure.com/documentation/multi-language_sites/>
## Using Multiple Channel Fields for Multiple Language
http://eeinsider.com/articles/multi-language-solutions-for-expressionengine/
## Using Multiple Channel Entries for Multiple Languages
Based on Carl Crawley's approach here: <http://cwcrawley.co.uk/2010/01/multi-lingual-websites-in-expressionengine/>
## Using Multiple Channels for Multiple Languages

## General Tips
- Translating URLs allows you to optimize your SEO in the language of your audiences
- I recommend using standard language tags and subtags as your `lang` variables. This is practical as it allows you to identify your content (e.g. `<html lang="{current_lang}">` dynamically as you go)
	- W3C has a (surprisingly) good reference doc for this: (http://www.w3.org/International/questions/qa-choosing-language-tags)

## Credits
Developer: Tim FitzGerald <http://tfitzgerald.ca> (@tfitzgee)
The routine used to match your language from the browser was ported from Globalize.js (jQuery licence).
Part of the routine used to translate languages comes from Doug Avery's Freebie add-on (GPL licence).
Default locale data comes from Unicode's Common Locale Data Repository (CLDR) project.

Special thanks go to:
- My wife, Kim, for her patience as I worked through this;
- Patrick Stinnett for making PS Languish, which acted as a starting point for this project, and for his immeasurable help getting me into EE.

I dedicate my work to my daughter Pénélope, who inspires me to strive to make a better world.

# Changelog
## 0.2 beta
* Rewrite of nearly all the code.
* Applied Ellis Labs' coding syntax guidelines <http://ellislab.com/expressionengine/user-guide/development/guidelines/general.html>.
* Added the CLDR JSON of the most-common languages.
* Renamed `current_lang` early-parsed variable with `polyglot:lang`, and added `polyglot:language`.
* Added functionality to route translated URIs to the physical template group/template.
* Move translation files to EE's language folder by default and added setting to move files elsewhere.
* Allow mapping of Polyglot language to EE language pack, and applying it so that EE error messages are properly translated.
* Created language config files, one per site (for MSM compatibility).

## 0.1
Initial release.

# Roadmap
## Short-Term
* add parameter to turn hiding seg_1 on or off
* add param to disable entire add-on
* Bug fixes, as reported
* Apply EE Performance Guidelines <http://ellislab.com/expressionengine/user-guide/development/guidelines/performance.html>
* Handle non-thousands digit grouping (e.g. In India, one million is styled 10,00,000)
* Add other localization functionalities CLDR can facilitate, including:
	* Relative time (e.g. 1 day ago, next week)
* Allow configuration through tools like Focus Lab LLC's Master Config file <https://github.com/focuslabllc/ee-master-config>
* Add a config variable `$config['polyglot_off]'` to disable the add-on programmatically
* Add a tag `{exp:languages_available}` to loop through configured languages
* Reinforce URI translations so that it can handle non-1:1 routing
* Add better logging for troubleshooting purposes
* Create a EE Debug Toolbar addition for simpler troubleshooting

## Medium-Term
*Subject to user feedback.*
* Maintain CLDR data. <http://www.unicode.org/repos/cldr-aux/json/>
* Provide an interface to render glossaries in JSON to be read and used in JavaScript.
* Add other localization functionalities CLDR can facilitate, including:
	* Word numbers (12 million, 18.5k)
	* List patterns
	* Number ranges
	* ellipses, quotation marks
	* language matching for finding suitable substitutes (e.g. If Norwegian is not available, offer Danish before English)
* Create field type to select available language for a given entry
* Evaluate the need for a field type for creating relations between entries
* Create simple module for better admin user experience

## Long-Term
Very subject to user feedback.
* Provide Control Panel interface to provide translations and language-specific config settings.
* Allow glossary files to be stored in more industry-standard formats, including POM and XLIFF.
