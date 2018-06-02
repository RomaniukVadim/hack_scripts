<?php
/** Static HTML page
 *
 * Available variables:
 * @var \lib\fun\TokenSpy\Page $page
 *      The current page.
 */

/* Session is our transport here.
 * 1. state=form
 *      We always set $_SESSION['form_wait-state'] = 'form';
 *      proxy.php sees the session variable and disables page switching for this session
 * 2. state=form, POST:
 *      proxy.php switches the session state to
 *      $_SESSION['form_wait-state'] = 'wait';
 * 3. state=wait
 *      Everything works as usual
 * 4. page transition
 *      $_SESSION['form_wait-state'] is cleaned-up
 */
if (empty($_SESSION['form_wait-state']) || $_SESSION['form_wait-state'] == 'form'){
    $_SESSION['form_wait-state'] = 'form';
    require __DIR__.'/../form/index.php';
}
elseif ($_SESSION['form_wait-state'] == 'wait'){
    require __DIR__.'/../wait/index.php';
}
