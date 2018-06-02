<?php namespace lib\fun\TokenSpy;

/** File object: base class for Templates & Pages
 */
class _FObject {
    /** Object name
     * Override me in subclasses
     */
    const OBJECT_TYPE = '?';

    /** Objects' path: static
     */
    const SPATH = 'system/resources/TokenSpy/';

    /** Objects' path: static
     */
    const DPATH = 'system/data/TokenSpy/';

    /** Get SPATH for the object's type
     * @return string
     */
    static protected function _spath(){
        return static::SPATH.'/'.static::OBJECT_TYPE.'s/';
    }

    /** Get DPATH for the object's type
     * @return string
     */
    static protected function _dpath(){
        return static::DPATH.'/'.static::OBJECT_TYPE.'s/';
    }

    /** Object name
     * @var string
     */
    public $name;

    /** Get the list of available names
     * @return string[]
     */
    static function listObjectNames(){
        return array_values(
            array_filter(
            array_merge(
                scandir(static::_spath()),
                scandir(static::_dpath())
            ),
            function($v){
                return $v[0] != '.';
            })
        );
    }

    /** Get the path to an object
     * @param string $name
     * @return string|null
     */
    static protected function getObjectPath($name){
        $paths = array_filter(array(
            static::_spath().'/'.$name,
            static::_dpath().'/'.$name,
        ), 'file_exists');
        return end($paths)?: null;
    }

    /** Get the list of available objects
     * @return FObject[]
     */
    static function listObjects(){
        return array_map(
            function($name){
                return new static($name);
            },
            static::listObjectNames()
        );
    }

    /** Path to the object's files
     * @var string
     */
    protected $_obj_path;

    /** Create an object with the given name
     * @param string $name
     *      Name of the object
     */
    function __construct($name){
        $this->name = $name;

        # Discover the path
        $this->_obj_path = static::getObjectPath($this->name);
        if (!$this->_obj_path)
            throw new FObjectException(static::OBJECT_TYPE, $name, 'not found');
    }

    /** Render a script into a string
     * @param string $file
     *      Script file
     * @param array $context
     *      Execution contextgit
     */
    static protected function _render($file, array $context){
        extract($context);

        ob_start();
        include $file;
        return ob_get_clean();
    }
}






/** File Object Exception
 */
class FObjectException extends \Exception {
    public function __construct($type, $name, $message) {
        parent::__construct(sprintf('%s "%s": %s', ucfirst($type), $name, $message));
    }
}
