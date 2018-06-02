<?php
/**
 * XML Utilities
 */

/** Magic-based XML generator :)
 *
 * Make attributes as dynamic object properties
 * Make subtags as array keys
 */
class XMLtag extends ArrayObject {
	/** Indentation character
	 * @var string|null
	 */
	protected $__indentation = "\t";

	/** Tag level (for indentation)
	 * @var int
	 */
	protected $__level = -1;

	/** Name of the tag
	 * @var string|null
	 */
	protected $__tagName;

	/** Tag attributes: array( name => value )
	 * @var string[]
	 */
	protected $__attributes = array();

	/** CDATA storage (if any)
	 * In CDATA tags no contents is available
	 * @var string
	 */
	protected $__cdata = null;

	/** Comment string (useless :)
	 * @var string|null
	 */
	protected $__comment = null;

	/**
	 * @param string|null $tagName
	 *      Name of this tag. Use null for the root tag
	 */
	function __construct($tagName = null, $level = 0){
		$this->__tagName = $tagName;
		$this->__level = $level;
	}

	/** Get/Set CDATA on the tag
	 * @param null|string $value
	 * @return null|string
	 */
	function CData($value = null){
		if (count($this))
			trigger_error('CData() is not availble on tags with contents', E_USER_ERROR);
		if (!is_null($value))
			$this->__cdata = $value;
		return $this->__cdata;
	}

	/** Get/Set the tag comment.
	 * Is printed just before the tag
	 * @param $value
	 */
	function comment($value){
		if (!is_null($value))
			$this->__comment = $value;
		return $this->__comment;
	}

	#region Magic Properties

	function __get($name) {
		if (!isset($this->__attributes[$name]))
			return null;
		return $this->__attributes[$name];
	}

	function __set($name, $value) {
		if (is_null($this->__tagName))
			trigger_error('Root tag has no attributes', E_USER_ERROR);
		$this->__attributes[$name] = $value;
	}

	#endregion

	#region ArrayAccess

	/**
	 * @param mixed|null $index
	 * @param XMLtag $value
	 */
	function offsetSet($k, $value) {
		# Control
		if (!is_null($this->__cdata))
			trigger_error('CData tag has no contents', E_USER_ERROR);
		if (! $value instanceof self)
			trigger_error('Can only store "'.get_class($this).'" objects, got "'.(  is_object($value)? get_class($value) : gettype($value)  ).'"', E_USER_ERROR);

		# If we have an index and there's no tagName on the value — set it
		if (!is_null($k) && is_null($value->__tagName))
			$value->__tagName = $k;

		# Set level
		$value->__indentation = $this->__indentation;
		$value->__level = $this->__level+1;

		# Set
		return parent::offsetSet($k, $value);
	}

	#endregion

	function __toString(){
		$t = str_repeat($this->__indentation, max(0,$this->__level));
		$xml = '';

		# Render the contents
		if (!empty($this->__cdata)){
			if (FALSE === strpos($this->__cdata, ']]>'))
				$xml = "<![CDATA[{$this->__cdata}]]>";
			else
				$xml = htmlentities($this->__cdata);
		}
		else {
			foreach ($this as $key => $tag)
				$xml .= (string)$tag;
			if (strlen($xml) !== 0)
				$xml = "\n{$xml}$t\t";
		}

		# Wrap into the tag
		if (!is_null($this->__tagName)){
			# tagName
			$tag = "$t<{$this->__tagName}";

			# Attributes
			foreach ($this->__attributes as $name => $value)
				$tag .= ' '.$name.'="'.htmlentities($value).'"';

			# Close
			if (0 == strlen($xml)) # Empty tag
				$xml = "$tag />\n";
			else
				$xml = "$tag>{$xml}</{$this->__tagName}>\n";
		}

		# Comment?
		if (!is_null($this->__comment))
			$xml = "$t<!-- {$this->__comment} -->\n".$xml;

		return $xml;
	}
}



if (0 && 'unittest XMLtag'){
	$xml = new XMLtag();
	$xml['HatKeeper'] = new XMLtag; # Example: keyName defines tagName when the latter is not set
	$xml['HatKeeper'][0] = new XMLtag('presets'); # Example: key name defines nothing
	$xml['HatKeeper'][0][] = $preset = new XMLtag('preset');

	$preset->name = 'C-BOT'; # Example: attributes
	$preset->proxy = '127.0.0.1:8080';
	$preset->gmttime = "+03:00";
	$urls = $preset['urls'] = new XMLtag;

	# Example: subtag with no contents
	$url = $urls[] = new XMLtag('url');
	$url->mask = '^.*';
	$headers = $url[] = new XMLtag('headers');

	$header = $headers[] = new XMLtag('header');
	$header->name = 'User-Agent';
	$header->value = 'Mozilla/5.0 (Windows NT 5.2; rv:9.0.2) Gecko/20100101 Firefox/9.0.2)';

	$header = $headers[] = new XMLtag('header');
	$header->name = 'Accept';
	$header->value = '*/*';

	# Example: subtag with CData
	$urls['cdata'] = new XMLtag;
	$urls['cdata']->danger = '<LOL></LOL>';
	$urls['cdata']->CData(<<<CDATA
A
B
C
LOL
CDATA
);

	echo $xml;
}