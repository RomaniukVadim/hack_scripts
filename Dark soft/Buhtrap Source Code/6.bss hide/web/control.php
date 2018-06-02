<?php

$login = 'admin';
$pass_md5 = '21232f297a57a5a743894a0e4a801fc3';
$db_file = './data.txt';

function is_good_account($account) {
  return preg_match("/^[0-9]*$/", $account);
}

function is_id($id) {
  return preg_match("/^[a-fA-F0-9]{16,16}$/", $id);
}

function is_float_str_2X($val) {
  return preg_match("/^[0-9]*\.[0-9]{2,2}$/", $val);
}

function read_db() {
  global $db_file;
  $a = @file_get_contents($db_file);
  if (!$a) {
    return NULL;
  }
  $result = Array();
  $b = explode("\n", $a);
  foreach($b as $c) {
    @list($id, $amount, $account) = explode('|', $c);
    if ($id != '' && $amount != '') {
      $result[$id]['account'] = $account;
      $result[$id]['amount'] = $amount;
    }
  }
  return $result;
}

function write_db($db) {
  global $db_file;
  $text = "";
  foreach ($db as $id => $v) {
    $account = $v['account'];
    $amount = $v['amount'];
    $text .= "$id|$amount|$account\n";
  }
  return @file_put_contents($db_file, $text);
}

///////////////////

if (isset($_GET['i'])) {
  $id = strtoupper($_GET['i']);
  $db = read_db();
  if (isset($db[$id])) {
    die($db[$id]['amount'].'|'.$db[$id]['account']);
  } else {
    die('000');
  }
}

///////////////////

if (isset($_POST['login']) && isset($_POST['pass']))
{
  if ($_POST['login'] == $login && md5($_POST['pass']) == $pass_md5) {
    setcookie('login', $_POST['login']);
    setcookie('pass_md5', md5($_POST['pass']));
    header("Location: ?");
    die();
  } else {
    print 'Wrong credentials';
    die();
  }
}

$auth_ok = false;

if (isset($_COOKIE['login']) && $_COOKIE['login'] == $login) {
  if (isset($_COOKIE['pass_md5']) && $_COOKIE['pass_md5'] == $pass_md5) {
    $auth_ok = true;
  }
}

if ($auth_ok) {
  if (isset($_GET['act']) && $_GET['act'] == 'logout') {
    setcookie('login', '');
    setcookie('pass_md5', '');
    header("Location: ?");
    die();
  }

  if (isset($_GET['act']) && $_GET['act'] == 'del') {
    if (isset($_GET['id'])) {
      $id = $_GET['id'];
      $db = read_db();
      unset($db[$id]);
      write_db($db);
      header("Location: ?");
      die();
    }
  }

  if (isset($_POST['act']) && $_POST['act'] == 'add') {
    if (isset($_POST['id']) && isset($_POST['amount']) && isset($_POST['account'])) {
      $id = strtoupper($_POST['id']);
      $account = $_POST['account'];
      $amount = $_POST['amount'];
      if (!is_id($id)) {
        die("Bad ID; need XXXXXXXXXXXXXXXX (16 hex digits)");
      }
      if (!is_float_str_2X($amount)) {
        die("Bad amount; need float xxx.XX like 1000.50 or 10235.23");
      }
      if (!is_good_account($account)) {
        die("Bad account");
      }
      $db = read_db();
      $db[$id] = Array('account' => $account, 'amount' => $amount);
      write_db($db);
      header("Location: ?");
      die();
    }
  }
}

?>

<html>
<head><title>Admin</title></head>
<body>

<?php

if (!$auth_ok) {
?>
  <form method='post' action='?'>
    Login: <input type='text' name='login' value='' /><br />
    Password: <input type='password' name='pass' value='' /><br />
    <input type='submit' value='Login' />
  </form>
<?php
} else {
  ?>
  <a href='?act=logout'>Logout</a><br /><br />
  <table width='500px' border='1'>
    <tr>
      <th width='30%'>ID</th>
      <th width='30%'>Amount</th>
      <th width='30%'>Account</th>
      <th width='10%'>&nbsp;</th>
    </tr>
  <?php

  $db = read_db();
  if (count($db) == 0) {
    print "<tr><td align='center' colspan='4'><b>EMPTY</b></td></tr>\n";
  }
  else {
    foreach($db as $id => $v) {
      $account = $v['account'];
      $amount = $v['amount'];
      print "  <tr>\n";
      print "    <td>$id</td>";
      print "    <td>$amount</td>";
      print "    <td>$account</td>";
      print "    <td><a href=\"?act=del&id=$id\">delete</a></td>";
      print "  </tr>";
    }
  }

  ?>
  </table><br />
  <form action='?' method='post'>
    ID: <input type='text' name="id" value='' />
    Amount: <input type='text' name="amount" value='' size='10' />
    Account: <input type='text' name="account" value='' size='10' />
    <input type="hidden" name="act" value="add" />
    <input type='submit' value='Add/replace' />
  </form>
  <?php
}

?>
</body>
</html>
