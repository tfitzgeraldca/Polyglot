<?php

//BCP 47 Language Tag
//-------------------
// Also known as ISO language tag
// Value Syntax: [language]-[region]-[script]-[variation]
// Examples: en (English), en-US (US English), zh-Hant (Traditional Chinese)
// Use as few options as possible.
//     For example, only specify en-US (American English) if you need to distinguish it from
//     another type of English, such as en-GB (British English)
//
// This value is the key used by Polyglot.
// 
// Used to load appropriate CLDR files
// Returned for Content-Language HTTP header, the {lang} early-parsed variable
//
// More info on language tags: http://www.w3.org/International/articles/language-tags/

$lang_config['lang'] = 'en';

// If you want CLDR to use another value, use the following variable
// It can also be available through the {exp:polyglot:cldr_locale}

//  $lang_config['cldr_locale'] = 'en-US';


//URL Language Identifier
//-------------------
// Optional. If none is provided, the 'lang' value will be used.
// Examples: "english", "en", "eng", "en-US", "usa", "uk", "gb"
// 
// Used to determine if this is the language to use if the user accesses mysite.com/uk or uk.mysite.com
// Also used to render alternate language URLs
// It can also be available through the {url_lang} and {exp:polyglot:url_lang}

//  $lang_config['url_lang'] = 'uk';


//Language Name
//-------------------
// The native name of the language
// Examples: US English, Français, Español, Portuges
//
// Used to provide links in different languages.
// 
// Used to load appropriate CLDR files
// Returned in the {exp:polyglot:lang_title} tag
//
// More info on language tags: http://www.w3.org/International/articles/language-tags/

$lang_config['lang_name'] = 'English';


//Text Direction Tag
//-------------------
// Optional. Directionality of the language to be used in presentation.
// Values: 'ltr' (left to right, default); 'rtl' (right to left)
//
// Used to help templating for languages written right-to-left (e.g. Arabic, Hebrew, Farsi)
// Returned in {exp:polyglot:dir} tag
//
// NOTE: This will have no effect on how text is displayed in the ExpressionEngine Control Panel
//
// More info on right-to-left scripts and bidirectional HTML:
//     http://www.w3.org/TR/i18n-html-tech-bidi/

$lang_config['dir'] = 'ltr';


//ExpressionEngine/CodeIgniter Language Mapping
//-------------------
// Optional. Default value: 'english'
//
// The full English name, all lowercase, of a language for the Language Packs EE and CI use
//   Value examples: 'french', 'russian', 'croatian'
//
// Used when the site is configured to override presented language by EE member preference
// Also used to set language when a guest visitor comes to the site.
//
// More information on EE language packs:
//    http://ellislab.com/expressionengine/user-guide/general/languages.html

$lang_config['ee_langauge'] = 'english';


//NOT YET AVAILABLE
//Member Custom Field Mapping
//-------------------
// By default, EE members use the language field (see above) to render the desired language.
// This is not very useful if you have two localizations in the same language.
//    For example, if you have localizations for the US and the UK, both of which use English.
//
// Syntax: [member custom field name]



//Translated Category URL Indicator
//-------------------
// By default, EE uses the category URL indicator such as .../category/abc to load items of category "abc"
// Use this variable to specify a localized indicator.
//  Value examples : "categorie" (for French); "kategoria" (for Polish)
//
// More about EE's Category URL Indicator:
//    http://ellislab.com/expressionengine/user-guide/cp/admin/channels/global_channel_preferences.html#category-url-indicator

$lang_config['segments']['category'] = "category";


//Translated Member Profile URL Indicator
//-------------------
// By default, when EE sees the "Profile Triggering Word" in the URL, it will display your member profile area.
// By default, this value is "member".
// Use this variable to specify a localized indicator.
//  Value examples : "membre" (for French); "mitglied" (for German)
//
// More about EE's Category URL Indicator:
//    http://ellislab.com/expressionengine/user-guide/cp/admin/channels/global_channel_preferences.html#category-url-indicator

$lang_config['segments']['member'] = "member";


//TODO: Timezone!!!
//old code:
//            $this->EE->cache['polyglot']['timezone'][$lang] = (isset($TZ) && ! is_array($TZ) ? $TZ : TRUE);


//Translated Segments
//-------------------
// 
// Syntax: [translated segment] => [physical segment],

$lang_config['segments'] = array(
	'english' => 'mylang',
);


//Path to Polyglot Language config file path
//-------------------
// Polyglot takes two 
// Example of a bilingual 
//   /languages
// 		/english
//			default_site_config.php
//			default_site_lang.php
//      /french
//			default_site_config.php
//			default_site_lang.php


//Path to CLDR JSON data
//-------------------
// Polyglot uses information from the Unicode's Common Locale Data Repository (http://cldr.unicode.com) to define
// how to display numbers, dates, and certain pre-defined strings.
// This information is written in a Javascript Object Notation (JSON) format provided by Unicode.
// Default: themes/third_party/polyglot/json
//          (so that it can be used by Javascript on the client-side as well)

$config['cldr_json_path'] = '';