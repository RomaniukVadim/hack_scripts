<?php
/** Serialized file handler
 */
class DatFileLoader {
    /** Storage filename
     * @var string
     */
    protected $_filename;

    /**
     * @param string $filename
     *      The filename to use as a storage
     */
    function __construct($filename){
        $this->_filename = $filename;

        $wrcheck = file_exists($this->_filename)? $this->_filename : dirname($this->_filename);
        if (!is_writable($wrcheck))
            trigger_error("File '{$this->_filename}' must be writable!", E_USER_WARNING);
    }

    /** Load the data from the specified file.
     * @return mixed|null The data from the file, or `null` when the file is missing or cannot be read
     */
    function load(){
        # Try to load
        if (file_exists($this->_filename)){
            $s = file_get_contents($this->_filename);
            if ($s !== false){
                $d = unserialize($s);
                if ($d !== false)
                    return $d;
            }
        }

        # Defaults?
        return null;
    }

    /** Save the data back
     * @return bool Whether the file was written to successfully
     */
    function save($data){
        $serialized = serialize($data);
        return file_put_contents($this->_filename, $serialized);
    }
}
