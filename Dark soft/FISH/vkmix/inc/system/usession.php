<?php
if(!$time) {
 header('Location: /');
 exit;
}

// создаём пользовательскую сессию
$usession = $session->get('usession');
if(!$usession) {
 $usession = $session->add('usession', rand() + time());
}
?>