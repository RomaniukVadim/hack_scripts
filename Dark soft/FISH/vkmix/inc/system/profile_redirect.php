<?php
if($uban_type) {
 header('Location: /blocked');
 exit;
} elseif($udel) {
 header('Location: /deleted');
 exit;
} elseif(!$user_logged) {
 // если пользователь неавторизован, перенаправляем на главную
 header('Location: /');
 exit;
}
?>