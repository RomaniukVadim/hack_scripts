<?php if(!defined('IN_DBSEO')) die('Access denied.');

/**
 * DBSEO_Filter
 *
 * @package DBSEO
 * @access public
 */
class DBSEO_Filter
{
	/**
	* Duplicated config array
	*
	* @public	array
	*/
	public static $config = array();

	/**
	* Array of configuration items
	*
	* @public	array
	*/
	protected static $cache = array();

	/**
	* The translation table
	*
	* @public	array
	*/
	public static $translationTable	= NULL;


	/**
	 * Initialises the relevant stuff
	 */
	public static function __init()
	{
		// Compatibility
		self::$config =& DBSEO::$config;
		self::$cache =& DBSEO::$cache;
	}

	/**
	 * Applies filtering to the text in question
	 *
	 * @param string $uri
	 * @param boolean $force404
	 *
	 * @return string
	 */
	public static function filterText($text, $allowedCharacters = null, $filterStopWords = true, $reversable = false, $keepTailSpaces = false, $appendA = true)
	{
//$text = 'S&#7919;aâ„¢';
//$filterStopWords = true; $reversable = false; $keepTailSpaces = false;

		static $translationTable, $translationTable2;

		if (!is_array(self::$config['dbtech_dbseo_filter_chars_custom']))
		{
			$customChars = preg_split('#\r?\n#s', self::$config['dbtech_dbseo_filter_chars_custom'], -1, PREG_SPLIT_NO_EMPTY);
			self::$config['dbtech_dbseo_filter_chars_custom'] = array();
			foreach ($customChars as $key => $val)
			{
				// Ensure we split this properly
				$val = preg_split('#\s*=>\s*#s', $val, -1, PREG_SPLIT_NO_EMPTY);
				self::$config['dbtech_dbseo_filter_chars_custom'][str_replace("'", '', $val[0])] = str_replace("'", '', $val[1]);
			}
		}

		if (!$translationTable)
		{
			$translationTable = $translationTable2 = array();

			foreach (self::$config['dbtech_dbseo_filter_chars_custom'] as $key => $val)
			{
				if (strpos($key, '%') !== false AND strlen($key) > 1)
				{
					$translationTable2[$key] = $val;
				}
				else
				{
					$translationTable[$key] = $val;
				}
			}

			if (self::$config['dbtech_dbseo_filter_nonlatin_chars'] == 2)
			{
				$otherChars = array(
					'Þ' => 'th', 'þ' => 'th', 'Ð' => 'dh', 'ð' => 'dh',
					'ß' => 'ss', 'Œ' => 'oe', 'œ' => 'oe', 'Æ' => 'ae',
					'æ' => 'ae', 'Ä' => 'ae', 'ä' => 'ae', 'ö' => 'oe',
					'ü' => 'ue'
				);

				if (self::$config['dbtech_dbseo_enable_utf8'])
				{
					foreach ($otherChars as $key => $replacement)
					{
						// Convert the string to our current charset
						$key = DBSEO::_toCharset($key, 'windows-1252', 'utf-8');

						// Make sure these are all UTF8
						$translationTable[$key] = $replacement;
					}
				}
				else
				{
					// Convert some special characters
					$translationTable = @array_merge($otherChars, $translationTable);
				}
			}
		}

		if (!isset($translationTable['\'']))
		{
			$text = str_replace('\'', ($reversable ? self::$config['dbtech_dbseo_rewrite_separator'] : ''), $text);
		}

		if ($allowedCharacters)
		{
			// Ensure the allowed characters are preg safe
			$allowedCharacters = preg_quote($allowedCharacters, '#');
		}

		// Set the valid characters
		$validCharacters = 'a-z\d\_' . $allowedCharacters;

		// Quote the separator for safety's sake
		$quotedUrlSeparator = preg_quote(self::$config['dbtech_dbseo_rewrite_separator'], '#');

		switch (self::$config['dbtech_dbseo_filter_nonlatin_chars'])
		{
			case 0:
				if (self::$config['dbtech_dbseo_utf8_convert'])
				{
					$text2 = preg_replace_callback(
						'#&\#?(\d+);#iu',
						array('DBSEO', 'toUtf8'),
						$text
					);
					if ($text2 AND $text != $text2)
					{
						// Successful convert
						$text = $text2;
					}
					else
					{
						// Convert the string to our current charset
						$text = DBSEO::_toCharset($text, 'utf-8');
					}
				}

				if (self::$config['dbtech_dbseo_enable_utf8'])
				{
					// Pretty much anything goes
					$validCharacters = '[^;]';
				}
				else
				{
					// Stop a few specific symbols
					$validCharacters = '[^/\\#\,\.\+\!\?:\s\)\(]';
				}

				// Finally do the translation
				$text = strtr($text, $translationTable);
				break;

			case 1:
				if (!$reversable)
				{
					$text = str_replace('\'', '', $text);
				}
				$validCharacters = 'a-z\d\_';
				//$text = strtr($text, $translationTable);
				break;

			default:
				if (self::$config['dbtech_dbseo_enable_utf8'])
				{
					require_once(DBSEO_CWD . '/dbtech/dbseo/includes/3rdparty/portable-utf8/voku/helper/UTF8.php');
					require_once(DBSEO_CWD . '/dbtech/dbseo/includes/3rdparty/portable-utf8/voku/helper/Bootup.php');
					require_once(DBSEO_CWD . '/dbtech/dbseo/includes/3rdparty/portable-utf8/Patchwork/PHP/Shim/Iconv.php');
					require_once(DBSEO_CWD . '/dbtech/dbseo/includes/3rdparty/portable-utf8/Patchwork/PHP/Shim/Intl.php');
					require_once(DBSEO_CWD . '/dbtech/dbseo/includes/3rdparty/portable-utf8/Patchwork/PHP/Shim/Mbstring.php');
					require_once(DBSEO_CWD . '/dbtech/dbseo/includes/3rdparty/portable-utf8/Patchwork/PHP/Shim/Normalizer.php');
					require_once(DBSEO_CWD . '/dbtech/dbseo/includes/3rdparty/portable-utf8/Patchwork/PHP/Shim/Xml.php');
					require_once(DBSEO_CWD . '/dbtech/dbseo/includes/3rdparty/portable-utf8/Patchwork/Utf8/Bootup.php');

					$text = \voku\helper\UTF8::toAscii($text);
				}
				else
				{
					// Strip out a few characters
					$text = strtr(
						strtr($text, $translationTable),
						'ŠŽšžŸÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöøùúûüýÿµ',
						'szszyaaaaaaceeeeiiiinoooooouuuuyaaaaaaceeeeiiiinoooooouuuuyyu'
					);

					if (self::$translationTable === NULL)
					{
						// Ensure this is set
						self::$translationTable = array_flip(get_html_translation_table(HTML_ENTITIES));
					}

					// Now do this translation
					$text = strtr($text, self::$translationTable);
				}
				break;
		}

		// Use whatever strtolower is supported
		if (self::$config['dbtech_dbseo_enable_utf8'])
		{
			$text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);

			$validCharacters = str_replace('\\#', '', $validCharacters);

			// Sort out what our invalid characters should be
			$invalidCharacters = (substr($validCharacters, 1, 1) == '^') ? "[" . substr($validCharacters, 2) : "[^$validCharacters]";
		}
		else
		{
			// Sort out what our invalid characters should be
			$invalidCharacters = (substr($validCharacters, 1, 1) == '^') ? "[" . substr($validCharacters, 2) : "[^$validCharacters]";

			$text = str_replace('&amp;', ' and ', strtolower($text));
			$text2 = preg_replace('#&\#?[a-z\d]+;#i', ' ', $text);

			if (preg_match('#\S#', $text2))
			{
				$text = $text2;
			}

			$text = str_replace('&', ' and ', $text);
		}

		if ($validCharacters)
		{
			$text = preg_replace('#' . $invalidCharacters . '+#s', self::$config['dbtech_dbseo_rewrite_separator'], $text);
		}

		$doReplaceWordlist = true;
		$keyWordCount = self::$config['dbtech_dbseo_rewrite_urlkeywords'] + 1;
		if (self::$config['dbtech_dbseo_rewrite_separator'] == '_')
		{
			$text = str_replace('_', ' ', $text);
		}

		if (
			!$reversable AND
			self::$config['dbtech_dbseo_removestopwords'] == 2 AND
			$filterStopWords AND
			self::$config['dbtech_dbseo_stopwordlist'] AND
			self::$config['dbtech_dbseo_rewrite_urlkeywords'] > 0
		)
		{
			preg_match_all('#([^' . $quotedUrlSeparator . ' ]+)#s' . (self::$config['dbtech_dbseo_enable_utf8'] ? 'u' : ''), $text, $wordMatches);
			preg_match_all('#\b(' . self::$config['dbtech_dbseo_stopwordlist'] . ')\b#s', $text, $stopWordMatches);
			$keyWordCount = count($wordMatches[1]) - count($stopWordMatches[1]);
		}
		else if ($reversable OR !self::$config['dbtech_dbseo_rewrite_urlkeywords'])
		{
			$doReplaceWordlist = false;
		}

		if ($filterStopWords)
		{
			// Sort out the stopword filtering
			self::filterStopWords($text, $keyWordCount, $reversable);
		}

		if ($doReplaceWordlist)
		{
			$text = preg_replace('#(([^' . $quotedUrlSeparator . ']+' . $quotedUrlSeparator . '*){' . self::$config['dbtech_dbseo_rewrite_urlkeywords'] . '}).*$#s' . (self::$config['dbtech_dbseo_enable_utf8'] ? 'u' : ''), '\\1', $text);
		}

		//$text = urlencode($text);

		if ($translationTable2)
		{
			// Translate the string
			$text = strtr($text, $translationTable2);
		}

		if (self::$config['dbtech_dbseo_rewrite_separator'] AND !($reversable AND !$keepTailSpaces))
		{
			$expression = ($reversable AND $keepTailSpaces) ? array('#(' . $quotedUrlSeparator . '){2,}#') : array('#(' . $quotedUrlSeparator . '){2,}#', '#^(' . $quotedUrlSeparator . ')+#', '#(' . $quotedUrlSeparator . ')$#');
			$replacement = array(self::$config['dbtech_dbseo_rewrite_separator'], '', '');
			$text = preg_replace($expression, $replacement, $text);
		}

		if (!$text)
		{
			// Fallback value
			$text = 'a';
		}
		else if ($appendA AND preg_match('#[-_\s](?:post|print)?\d+$#', $text))
		{
			// Only do this in certain cases
			$text .= ($text ? self::$config['dbtech_dbseo_rewrite_separator'] : '') . 'a';
		}

		if (self::$config['dbtech_dbseo_rewrite_separator'] == '_')
		{
			$text = str_replace(' ', '_', $text);
		}

		return $text;
	}

	/**
	 * Creates a regexp that matches the original string
	 *
	 * @param string $text
	 * @param boolean $anySpacer
	 *
	 * @return string
	 */
	public static function unFilterText($text, $anySpacer = false)
	{
		if (!is_array(self::$config['dbtech_dbseo_filter_chars_custom']))
		{
			$customChars = preg_split('#\r?\n#s', self::$config['dbtech_dbseo_filter_chars_custom'], -1, PREG_SPLIT_NO_EMPTY);
			self::$config['dbtech_dbseo_filter_chars_custom'] = array();
			foreach ($customChars as $key => $val)
			{
				// Ensure we split this properly
				$val = preg_split('#\s*=>\s*#s', $val, -1, PREG_SPLIT_NO_EMPTY);
				self::$config['dbtech_dbseo_filter_chars_custom'][str_replace("'", '', $val[0])] = str_replace("'", '', $val[1]);
			}
		}

		if (self::$config['dbtech_dbseo_filter_nonlatin_chars'] == 1)
		{
			$replace = array(
				(self::$config['dbtech_dbseo_rewrite_separator'] . 'and' . self::$config['dbtech_dbseo_rewrite_separator']) => '#**%**#',
				//self::$config['dbtech_dbseo_rewrite_separator'] => '(&[\#\da-z]*;|[^a-z\d])+'
				self::$config['dbtech_dbseo_rewrite_separator'] => '(&[\#\da-z]*;|[^a-z\d])*'
			);
		}
		else
		{
			$replace = array(
				'ue' 	=> '(ue|ü)',
				'oe' 	=> '(oe|ö|_|_)',
				'ae' 	=> '(ae|ä|Æ|æ)',
				'ss' 	=> '(ss|ß)',
				(self::$config['dbtech_dbseo_rewrite_separator'] . 'and' . self::$config['dbtech_dbseo_rewrite_separator']) => '#**%**#',
				's' 	=> '[sŠš]',
				'z' 	=> '[zŽž]',
				'y' 	=> '[yŸÝýÿ]',
				'a' 	=> '[aÀÁÂÃÄÅàáâãäå]',
				'c' 	=> '[cÇc]',
				'e' 	=> '[eÈÉÊËèéêë]',
				'i' 	=> '[iÌÍÎÏìíîï]',
				'n' 	=> '[nÑñ]',
				'o' 	=> '[oÒÓÔÕÖØòóôõöø]',
				'th' 	=> '(th|Þ|þ)',
				'dh' 	=> '(dh|ð|Ð)',
				'u' 	=> '([uÙÚÛÜùúûüµ]|u|Æ|æ)',
				self::$config['dbtech_dbseo_rewrite_separator'] => '(&[\#\da-z]*;|[^a-z\d])*',
			);

			if (self::$config['dbtech_dbseo_enable_utf8'])
			{
				foreach ($replace as $key => &$replacement)
				{
					// Convert the string to our current charset
					$replacement = DBSEO::_toCharset($replacement, 'windows-1252', 'utf-8');
				}
			}
		}

		if ($anySpacer)
		{
			// The spacer can be any char
			$replace[self::$config['dbtech_dbseo_rewrite_separator']] = '.*';
		}

		$textSearch = array('(', ')');
		$textReplace = array('\\\(', '\\\)');

		$mysqlSearch = array('%', '*', '(', ')', '?', '"', "'");
		$mysqlReplace = array('\\\%', '\\\*', '\\\(', '\\\)', '\\\?', "\\\'");

		// Make sure the base text is MySQL regexp safe
		$text = str_replace($textSearch, $textReplace, $text);

		$replace2 = array();
		foreach (self::$config['dbtech_dbseo_filter_chars_custom'] as $key => $value)
		{
			$key = str_replace($mysqlSearch, $mysqlReplace, $key);
			$value = str_replace($mysqlSearch, $mysqlReplace, $value);

			if (isset($replace[$value]))
			{
				if ($replace[$value][0] == '(')
				{
					$replace[$value] = "($key|" . substr($replace[$value], 1);
				}
				else
				{
					$replace[$value] = "($key|" . $replace[$value] . ")";
				}
			}
			else
			{
				if (isset($replace[$value[0]]))
				{
					$replace2[$value] = "($key|$value)";
				}
				else
				{
					$replace[$value] = "($key|$value)";
				}
			}
		}

		$replace = array_merge($replace2, $replace);
		$replace['#**%**#'] = '[^a-z\d]*(and|&amp;|&)[^a-z\d]*';

		// Split up the str_replace to make double dog sure the * is replaced first
		$text = str_replace(array_keys($replace), $replace, $text);

		//return '^(&[\#\da-z]*;|[^a-z\d])*' . $text . '(&[a-z]*;|[^a-z\d])*$';
		return '^(&[\#\da-z]*;|[^a-z\d])*' . $text . '(&[\#\da-z]*;|[^a-z\d])*$';
	}

	/**
	 * Filters the selected tag
	 *
	 * @param string $tag
	 *
	 * @return string
	 */
	public static function filterTag($tag)
	{
		// Ensure we have the correct data set
		$tag = str_replace(array('%2F', '+'), array('/', '%20'), $tag);

		if (!self::$config['dbtech_dbseo_filter_blogtag'])
		{
			// We aint filterin nothin
			return $tag;
		}

		// Do ze filtering
		return self::filterText(urldecode($tag), NULL, false, false, false, false);
	}

	/**
	 * Filters the stop words from the text
	 *
	 * @param string $text
	 *
	 * @return void
	 */
	public static function filterStopWords(&$text, $keyWordCount = NULL, $reversable = false)
	{
		if (!self::$config['dbtech_dbseo_removestopwords'] OR !self::$config['dbtech_dbseo_stopwordlist'])
		{
			return;
		}

		if ($reversable)
		{
			// Hack to make the replace make sense
			self::$config['dbtech_dbseo_stopwordlist'] = '|' . self::$config['dbtech_dbseo_stopwordlist'] . '|';

			// Get rid of the "and" word
			self::$config['dbtech_dbseo_stopwordlist'] = str_replace('|and|', '|', self::$config['dbtech_dbseo_stopwordlist']);

			if (substr(self::$config['dbtech_dbseo_stopwordlist'], 0, 1) == '|')
			{
				// Chop off beginning | if it exists
				self::$config['dbtech_dbseo_stopwordlist'] = substr(self::$config['dbtech_dbseo_stopwordlist'], 1);
			}

			if (substr(self::$config['dbtech_dbseo_stopwordlist'], -1) == '|')
			{
				// Chop off closing | if it exists
				self::$config['dbtech_dbseo_stopwordlist'] = substr(self::$config['dbtech_dbseo_stopwordlist'], 0, -1);
			}
		}

		if ($keyWordCount === NULL)
		{
			$keyWordCount = self::$config['dbtech_dbseo_rewrite_urlkeywords'] + 1;

			if (
				!$reversable AND
				self::$config['dbtech_dbseo_removestopwords'] == 2 AND
				self::$config['dbtech_dbseo_rewrite_urlkeywords'] > 0
			)
			{
				preg_match_all('#([^' . preg_quote(self::$config['dbtech_dbseo_rewrite_separator'], '#') . ' ]+)#s' . (self::$config['dbtech_dbseo_enable_utf8'] ? 'u' : ''), $text, $wordMatches);
				preg_match_all('#\b(' . self::$config['dbtech_dbseo_stopwordlist'] . ')\b#s', $text, $stopWordMatches);
				$keyWordCount = count($wordMatches[1]) - count($stopWordMatches[1]);
			}
		}

		$textBackup = $text;
		if ($keyWordCount >= self::$config['dbtech_dbseo_rewrite_urlkeywords'])
		{
			// Just remove all stopwords
			$text = preg_replace('#\b(' . self::$config['dbtech_dbseo_stopwordlist'] . ')\b#i', '', $text);
		}
		else
		{
			// Replace stopwords while we have enough keywords
			self::$cache['keyWordCount2'] = self::$config['dbtech_dbseo_rewrite_urlkeywords'] - $keyWordCount;

			// Replace stopwords
			$text = preg_replace_callback(
				'#\b(' . self::$config['dbtech_dbseo_stopwordlist'] . ')\b#i',
				array('DBSEO', 'replaceStopWords'),
				$text
			);
		}

		// Determine first and last char
		$firstChar = substr($text, 0, 1);
		$lastChar = substr($text, -1);

		// Set some important stuff
		$start = 0;
		$length = strlen($text);

		// Whether we should replace
		$doReplace = false;

		if ($firstChar == self::$config['dbtech_dbseo_rewrite_separator'])
		{
			// We need a replacement
			$doReplace = true;
			$start = 1;
		}

		if ($lastChar == self::$config['dbtech_dbseo_rewrite_separator'])
		{
			// We need a replacement
			$doReplace = true;
			$length = -1;
		}

		if ($doReplace)
		{
			// Do a substr
			$text = substr($text, $start, $length);
		}

		if (self::$config['dbtech_dbseo_rewrite_separator'] != '' AND !$reversable)
		{
			// Quote the separator for safety's sake
			$quotedUrlSeparator = preg_quote(self::$config['dbtech_dbseo_rewrite_separator'], '#');
			$expression = ($reversable AND $keepTailSpaces) ? array('#(' . $quotedUrlSeparator . '){2,}#') : array('#(' . $quotedUrlSeparator . '){2,}#', '#^(' . $quotedUrlSeparator . ')+#', '#(' . $quotedUrlSeparator . ')$#');
			$replacement = array(self::$config['dbtech_dbseo_rewrite_separator'], '', '');
			$text = preg_replace($expression, $replacement, $text);
		}

		if (!$text)
		{
			$text = $textBackup;
		}
	}

	/**
	 * Generates a SEO title based on the text in question
	 *
	 * @param array $info
	 *
	 * @return string
	 */
	public static function contentFilter($text, $maxKeywords = -1, $mergeText = true)
	{
		// Init this
		$title = array();

		// Set the word list
		$wordList = preg_split('#[ \r\n\t]+#', $text, -1, PREG_SPLIT_NO_EMPTY);

		// Remove bbcode from word list
		$wordList = preg_replace('#\[.*/?\]#siU', '', $wordList);

		// Remove invalid characters from the end of the string
		$wordList = preg_replace('#\W+$#siU', '', $wordList);

		// Remove dupes
		$wordList = array_map('strtolower', array_unique($wordList));

		// Ensure we put a cap on the amount of keywords to extract
		$maxKeywords = $maxKeywords == -1 ? (self::$config['dbtech_dbseo_rewrite_urlkeywords'] ? self::$config['dbtech_dbseo_rewrite_urlkeywords'] : 6) : $maxKeywords;

		if (($keywordsByPriority = DBSEO::$datastore->fetch('keywords')) === false)
		{
			$keywordsByPriority = array();

			$keywords = DBSEO::$db->generalQuery('
				SELECT *
				FROM $dbtech_dbseo_keyword
			', false);

			foreach ($keywords as $keyword)
			{
				if (!$keyword['active'])
				{
					// Inactive keyword
					continue;
				}

				// Index
				$keywordsByPriority[$keyword['priority']][] = strtolower($keyword['keyword']);
			}
		}

		// Sort by higher priority first
		krsort($keywordsByPriority);

		foreach ($keywordsByPriority as $priority => $keywords)
		{
			foreach ($keywords as $keyword)
			{
				if (count($title) >= $maxKeywords)
				{
					// Stahp.
					break 2;
				}

				if (array_search($keyword, $wordList) !== false)
				{
					// We got dis.
					$title[] = $keyword;
				}
			}
		}

		// And we're done here
		return $mergeText ? implode(self::$config['dbtech_dbseo_rewrite_separator'], $title) : $title;
	}

	/**
	 * Generates a SEO title based on the text in question
	 *
	 * @param array $info
	 *
	 * @return string
	 */
	public static function vBContentFilter(&$keywords, $text, $maxKeywords, $minLength, $maxLength, $forceLower = false, $extraGoodWords = array(), $extraBadWords = array())
	{
		if (empty($text))
		{
			return;
		}

		require_once(DIR . '/includes/functions_databuild.php');

		// remove all bbcode tags
		$text = strip_bbcode($text);

		// there are no guarantees that any of the words will be delimeted by spaces so lets change that
		$text = implode(' ', split_string($text));

		// make lower case and pad with spaces
		//$text = strtolower(" $text ");
		$text = " $text ";

		$find = array(
			'#[()"\'!{}<>]|\\\\|:(?!//)#s',				// allow through +- for boolean operators and strip colons that are not part of URLs
			'#([.,?&/_]+)( |\.|\r|\n|\t)#s',			// \?\&\,
			'#\s+(-+|\++)+([^\s]+)#si',					// remove leading +/- characters
			'#(\s?\w*\*\w*)#s',							// remove words containing asterisks
			'#[ \r\n\t]+#s',							// whitespace to space
		);
		$replace = array(
			'',		// allow through +- for boolean operators and strip colons that are not part of URLs
			' ',	// \?\&\,
			' \2',	// remove leading +/- characters
			'',		// remove words containing asterisks
			' ',	// whitespace to space
		);

		$text = strip_tags($text); // clean out HTML as it's probably not going to be indexed well anyway

		// use regular expressions above
		$text = preg_replace($find, $replace, $text);

		// Finally create "words"
		$words = trim(vbstrtolower($text));

		// title has already been htmlspecialchar'd, pagetext has not
		$wordarray = split_string($words);

		$sorted_counts = array_count_values($wordarray);
		arsort($sorted_counts);

		require(DIR . '/includes/searchwords.php'); // get the stop word list; allow multiple requires
		$badwords = array_merge($badwords, $extraBadWords, preg_split('/\s+/s', self::$config['badwords'], -1, PREG_SPLIT_NO_EMPTY), explode('|', self::$config['dbtech_dbseo_stopwordlist']));
		$goodwords = array_merge($goodwords, $extraGoodWords);

		foreach (DBSEO::$cache['keywords'] as $keyword)
		{
			if (array_key_exists(vbstrtolower($keyword['keyword']), $sorted_counts))
			{
				$keywords[] = ($forceLower ? vbstrtolower($keyword['keyword']) : $keyword['keyword']);
			}
		}

		foreach ($sorted_counts AS $word => $count)
		{
			$word = trim($word);

			if (!$word)
			{
				// Blank
				continue;
			}

			if (in_array(vbstrtolower($word), $badwords))
			{
				// Stopword, don't include
				continue;
			}

			if (preg_match("/^https?:/i", $word))
			{
				// Don't include links
				continue;
			}

			if (!in_array(vbstrtolower($word), $goodwords))
			{
				if (self::$config['dbtech_dbseo_tagging_keywordonly'])
				{
					// Skip this
					continue;
				}

				if (!self::$config['dbtech_dbseo_tagging_allownumbers'] AND is_numeric($word))
				{
					// Skip this
					continue;
				}

				$char_strlen = vbstrlen($word, true);
				if ($minLength AND $char_strlen < $minLength)
				{
					// Too short
					continue;
				}

				// Correct potentially odd value.
				$maxLength = $maxLength > 100 ? 100 : $maxLength;

				if ($char_strlen > $maxLength)
				{
					// only have 100 bytes to store a tag
					continue;
				}

				if (strlen($word) > 100)
				{
					// only have 100 bytes to store a tag
					continue;
				}
			}

			//$word = str_replace('&amp;', '&', htmlspecialchars_uni($word));

			if (!in_array($word, $keywords))
			{
				$keywords[] = ($forceLower ? vbstrtolower($word) : $word);
			}

			if (sizeof($keywords) >= $maxKeywords)
			{
				// Too many keywords
				break;
			}
		}
	}

	/**
	 * Reverses filtering to the username in question
	 *
	 * @param string $uri
	 * @param boolean $force404
	 *
	 * @return boolean
	 */
	public static function reverseUsername($username)
	{
		if (($userId = DBSEO::$datastore->fetch('username.' . hash('crc32b', $username))) === false)
		{
			if (self::$config['dbtech_dbseo_enable_utf8'] AND !self::$config['dbtech_dbseo_filter_nonlatin_chars'])
			{
				if (!$userInfo = DBSEO::$db->generalQuery('
					SELECT userid
					FROM $user
					WHERE username = \'' . DBSEO::$db->escape_string(htmlspecialchars($username)) . '\'
					LIMIT 1
				'))
				{
					// utf-8 regexp?
					$userInfo = DBSEO::$db->generalQuery('
						SELECT userid
						FROM $user
						WHERE username = \'' . DBSEO::$db->escape_string(htmlspecialchars(urldecode($username))) . '\'
						LIMIT 1
					');
				}
			}
			else
			{
				// Test if we have a direct username match
				if (!$userInfo = DBSEO::$db->generalQuery('
					SELECT userid
					FROM $user
					WHERE username LIKE \'' . str_replace(self::$config['dbtech_dbseo_rewrite_separator'], ' ', DBSEO::$db->escape_string_like($username)) . '\'
					LIMIT 1
				'))
				{
					// nooooope, try regexp
					if (!$userInfo = DBSEO::$db->generalQuery('
						SELECT userid
						FROM $user
						WHERE username REGEXP \'' . str_replace("'", "\'", self::unFilterText(htmlspecialchars($username))) . '\'
						LIMIT 1
					'))
					{
						// utf-8 regexp?
						$userInfo = DBSEO::$db->generalQuery('
							SELECT userid
							FROM $user
							WHERE username REGEXP \'' . str_replace("'", "\'", self::unFilterText(htmlspecialchars(urldecode($username)))) . '\'
							LIMIT 1
						');
					}
				}
			}

			// Store this
			$userId = $userInfo['userid'];

			// Build the cache
			DBSEO::$datastore->build('username.' . hash('crc32b', $username), $userId);
		}

		return $userId;
	}

	/**
	 * Looks up forum ID based on forum info
	 *
	 * @param array $info
	 *
	 * @return integer
	 */
	public static function reverseForumTitle($info)
	{
		// Prepare fallback value
		$forumid = 0;

		// Grab our forum cache
		$forumcache = DBSEO::$db->fetchForumCache();

		if (isset($info['forum_path']))
		{
			foreach ($forumcache as $forum)
			{
				if ($forum['path'] == $info['forum_path'])
				{
					// We got our forum ID
					$forumid = $forum['forumid'];
					break;
				}
			}
		}
		else if (isset($info['forum_title']) AND is_array($forumcache))
		{
			$encodedTitle = urlencode($info['forum_title']);
			$decodedTitle = urldecode($info['forum_title']);

			foreach ($forumcache as $forum)
			{
				// Set the SEO'd forum URL in the global cache
				DBSEO_Rewrite_Forum::rewriteUrl($forum);

				if (
					DBSEO::$db->cache['forumcache'][$forum['forumid']]['seotitle'] == $encodedTitle OR
					DBSEO::$db->cache['forumcache'][$forum['forumid']]['seotitle'] == $decodedTitle OR
					DBSEO::$db->cache['forumcache'][$forum['forumid']]['seotitle'] == $info['forum_title'] OR
					DBSEO::$db->cache['forumcache'][$forum['forumid']]['seotitle_reversable'] == $encodedTitle OR
					DBSEO::$db->cache['forumcache'][$forum['forumid']]['seotitle_reversable'] == $decodedTitle OR
					DBSEO::$db->cache['forumcache'][$forum['forumid']]['seotitle_reversable'] == $info['forum_title']
				)
				{
					// We found our forum ID
					$forumid = $forum['forumid'];
					break;
				}
			}
		}

		return $forumid;
	}

	/**
	 * Reverses filtering to the text in question
	 *
	 * @param string $uri
	 * @param boolean $force404
	 *
	 * @return boolean
	 */
	public static function reverseObject($object, $title, $id = 0)
	{
		$whereCond = $idColumn = $tableName = $title2 = $title3 = '';
		$appendTitle = false;
		$unfilterTitle = true;

		switch ($object)
		{
			case 'blogcat':
				if ($title == self::$config['dbtech_dbseo_blog_category_undefined'])
				{
					return false;
				}

				$idColumn 		= 'blogcategoryid';
				$tableName 		= 'blog_category';
				$whereCond 		= 'userid IN(0,' . intval($id) . ') AND title';
				$appendTitle 	= true;
				break;

			case 'thread':
				$idColumn 		= 'threadid';
				$tableName 		= 'thread';
				$whereCond 		= ($id ? 'forumid = ' . intval($id) . ' AND ' : '') . 'title';
				$appendTitle 	= true;
				$unfilterTitle 	= false;
				break;

			case 'album':
				$idColumn 		= 'albumid';
				$tableName 		= 'album';
				$whereCond		= 'userid = "' . intval($id) . '"  AND title';
				$appendTitle 	= true;
				break;

			case 'cmsnode':
				$idColumn 		= 'nodeinfo.nodeid';
				$tableName 		= 'cms_nodeinfo AS nodeinfo LEFT JOIN $cms_node AS node ON(nodeinfo.nodeid = node.nodeid)';
				$whereCond 		= 'IF(url, url, title)';
				$appendTitle 	= true;
				break;

			case 'group':
				$idColumn 		= 'groupid';
				$tableName 		= 'socialgroup';
				$whereCond 		= 'name';
				$appendTitle 	= true;
				break;

			case 'groupcat':
				$idColumn 		= 'socialgroupcategoryid';
				$tableName 		= 'socialgroupcategory';
				$whereCond 		= 'title';
				$appendTitle 	= true;
				break;

			case 'tag':
				$idColumn 		= 'tagtext';
				$tableName 		= 'tag';
				$whereCond 		= 'tagtext';
				break;
		}

		if ($appendTitle)
		{
			// Prepare the title
			$title = preg_replace('#-a$#', '', $title);
		}

		// Pre-query info
		$preQuery = 'SELECT ' . $idColumn . ' AS id FROM $' . $tableName . ' WHERE ' . $whereCond . ' ';

		if ($info = DBSEO::$db->generalQuery($preQuery . ' LIKE "' . DBSEO::$db->escape_string_like(str_replace(self::$config['dbtech_dbseo_rewrite_separator'], ' ', $title)) . '" LIMIT 1'))
		{
			// Success on the first try!
			return $info['id'];
		}

		if ($unfilterTitle)
		{
			// We need to unfilter the text to try again
			$title2 = self::unFilterText(htmlspecialchars(self::$config['dbtech_dbseo_rewrite_separator'] . str_replace(' ', self::$config['dbtech_dbseo_rewrite_separator'], $title) . self::$config['dbtech_dbseo_rewrite_separator']));
			$title3 = self::unFilterText(htmlspecialchars(self::$config['dbtech_dbseo_rewrite_separator'] . str_replace(' ', self::$config['dbtech_dbseo_rewrite_separator'], $title) . self::$config['dbtech_dbseo_rewrite_separator']), true);

			if (
				!@preg_match('/' . $title2 . '/', $title, $match)
				OR !@preg_match('/' . $title3 . '/', $title, $match)
			)
			{
				return false;
			}
		}
		else
		{
			// Try another like comparison
			$title2 = '%' . str_replace(self::$config['dbtech_dbseo_rewrite_separator'], '%', DBSEO::$db->escape_string_like($title)) . '%';
		}

		if ($info = DBSEO::$db->generalQuery($preQuery . ($unfilterTitle ? 'REGEXP' : 'LIKE' ) . ' "' . $title2 . '" ORDER BY LENGTH(' . $whereCond . ') LIMIT 1'))
		{
			// Success on the second try!
			return $info['id'];
		}

		if (!$title3)
		{
			// We failed :(
			return false;
		}

		if ($info = DBSEO::$db->generalQuery($preQuery . ' REGEXP "' . $title3 . '" ORDER BY LENGTH(' . $whereCond . ') LIMIT 1'))
		{
			// Success on the third try!
			return $info['id'];
		}

		// Ultimate failure.
		return false;
	}
}
?>