<?php
require_once 'system/lib/dbpdo.php';

/** Global notes
 */
class GlobalNotes {
    static private $_instance;

    /** @return GlobalNotes */
    static function singleton(){
        if (!static::$_instance)
            static::$_instance = new static();
        return static::$_instance;
    }

    private function __construct(){
        $this->db = dbPDO::singleton();
    }

    /** Prepared queries
     * @var PDOStatement[]
     */
    protected $_prepared_queries = array();

    /** Prepare a query once and reuse
     * @param string $name Query name, unique
     * @param string $query The query
     * @param string $qdata Query data
     * @return PDOStatement
     * @throws PDOException
     */
    protected function _query($name, $query, $qdata){
        if (!isset($this->_prepared_queries[$name]))
            $this->_prepared_queries[$name] = $this->db->prepare($query);
        $this->_prepared_queries[$name]->execute($qdata);
        return $this->_prepared_queries[$name];
    }



    /** Idenfier: URL
     * @param string $url
     * @return mixed|string
     */
    static function idUrl($url){
        # Get the hostname
        if (strpos($url, '/') === FALSE)
            $url = "http://$url";
        $id = parse_url($url, PHP_URL_HOST);
        # Remove 'www'
        if (strncmp($id, 'www.', 4) === 0)
            $id = substr($id, 4);
        # Rip the 3+ domain levels off
        $id = implode('.', array_slice(explode('.', $id), -3));
        return $id;
    }



    /** Read a note by ($type,$id)
     * @param string $type
     * @param string $id
     * @return null|string
     * @throws PDOException
     */
    function get($type, $id){
        $qdata = array(':type' => $type, ':id' => $id);
        $note = $this->_query('get', 'SELECT `note` FROM `notes` WHERE `type`=:type AND `id`=:id;', $qdata)->fetchColumn(0);
        return ($note === FALSE)? null : $note;
    }

    /** Store a note $note into ($type,$id)
     * @param string $type
     * @param string $id
     * @return bool
     * @throws PDOException
     */
    function set($type, $id, $note){
        $qdata = array(':type' => $type, ':id' => $id, ':note' => $note);
        $this->_query('set', 'REPLACE INTO `notes` SET `type`=:type, `id`=:id, `note`=:note;', $qdata);
        return true;
    }

    /** Delete a note from ($type,$id)
     * @param string $type
     * @param string $id
     * @return bool
     * @throws PDOException
     */
    function del($type, $id){
        $qdata = array(':type' => $type, ':id' => $id);
        $this->_query('set', 'DELETE FROM `notes` WHERE `type`=:type AND `id`=:id;', $qdata);
        return true;
    }


    /** Attach the note widgets to an arbitrary HTML chunk
     * @param string $note
     */
    static function attachNote($type, $id, $html){
        # Try to load the note
        try {
            # The `notes` table might be missing when DB Connect is used
            $note = static::singleton()->get($type, $id);
        } catch (PDOException $e){
            return null; // Notes table not available
        }

        # Prepare the styles
        $display_none = 'style="display: none;"';
        $add_style = is_null($note)? '' : $display_none;
        $edit_style = is_null($note)? $display_none : '';

        # Prepare the data
        $idU = urlencode($id);

        # Append the widgets
        $html .= "<a class='globalnotes-add' href='' {$add_style}></a>";
        $html .= "<div class='globalnotes-edit' contenteditable='true' data-href='?m=ajax/note&type={$type}&id={$idU}' $edit_style>{$note}</div>";

        # Finish
        return $html;
    }
}
