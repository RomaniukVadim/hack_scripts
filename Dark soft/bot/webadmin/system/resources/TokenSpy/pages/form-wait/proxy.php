<?php
/** A proxy script is executed within the context of ProxyController::actionPage()
 *
 * Available variables:
 * @var \lib\fun\TokenSpy\gate\ProxyController $this
 *      Proxy controller
 * @var \Amiss\Manager $man
 *      Amiss entity manager
 * @var \Citadel\Models\TokenSpy\BotState $bs
 *      Bot state
 * @var \lib\fun\TokenSpy\gate\BotInfo $botInfo
 *      Bot info
 *
 * @var bool $disable_page_transitions
 * @var int|null $__updateRequest
 */

// Default state
if (!isset($_SESSION['form_wait-state']))
    $_SESSION['form_wait-state'] = 'form';

// When in 'form' state, don't use timeouts
if ($_SESSION['form_wait-state'] == 'form'){
    $disable_page_transitions = true;
}

if (!is_null($__updateRequest))
    return; // Update requests use POST: they should not trigger the below

// When POST data was sent, switch to 'wait'
if (!empty($_POST)){
    $_SESSION['form_wait-state'] = 'wait';

    // Also reset mtime so timeouts start from this moment
    $bs->mtime = new \DateTime();
    $man->save($bs);
}

// When 'wait' times out, reset the session
if (!$disable_page_transitions && $bs->needPageTransition()){
    unset($_SESSION['form_wait-state']);
}
