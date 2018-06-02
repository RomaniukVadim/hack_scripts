<?php
@include '../lib/remscript-server.php';
@include '../lib/mailmessage.php';

class MailerScript extends RemScriptServer {
    /** Self-test
     * @return bool[]
     */
    function methodSelftest($server_version){
        return array(
            'php version' => phpversion(),
            'mail() enabled' => function_exists('mail'),
            'safe mode off' => !ini_get('safe_mode'),
            'script updated' => '1.0.2' == $server_version,
        );
    }



    /** Send a message to multiple receivers
     *
     * Supported placeholders:
     *      {hostname}      Current hostname
     *      {email}         The receiver's email address
     *      {name}          The receiver's name, or if not provided, his e-mail account
     *      {random}        Random ASCII bytes, length=8
     *
     * Every uploaded file is attached to the message, preserving the provided filename & MIME type.
     *
     * @param array $message
     *      Body, optionally, in multiple formats: { 'plain': string, 'html': string }
     * @param string $subject
     *      Subject
     * @param string|array $sender
     *      Sender: [ email, name ] or [ email ] or just `email`
     * @param array $receivers
     *      Array of Receivers: { tid: [email, name] } or { tid: [email] }
     * @throws Exception
     * @returns array { tid: boolean }
     */
    function methodSend(array $message, $subject, $sender, $receivers, $send_delay = 0.0){
        # Prepare the message with contents
        $msg = new MailMessage();
        foreach ($message as $format => $content)
            $msg->setMessage($content, $format);

        # From & Subject
        $sender = (array)$sender;
        $msg->setSubject($subject);
        $msg->setFrom($sender[0], isset($sender[1])? $sender[1] : null);

        # Attach files
        foreach ($_FILES as $field_name => $f)
            if ($f['error'] == UPLOAD_ERR_OK)
                $msg->attachment(basename($f['name']), file_get_contents($f['tmp_name']), $f['type']);
            else
                throw new Exception('Upload of "'.$f['name'].'" failed: #'.$f['error']);

        # mail() exposes private info in the 'X-PHP-Script' header. Don't let it fool us!
        $server_backup = array($_SERVER['PHP_SELF'], $_SERVER['REMOTE_ADDR']);
        $_SERVER['PHP_SELF'] = "/";
        $_SERVER['REMOTE_ADDR'] = $_SERVER['SERVER_ADDR'];

        # Timeout
        @set_time_limit(60*60*5);

        # Send the messages
        $sent = array();
        foreach ($receivers as $id => $to){
            $msg->setDefaultHeaders();

            # Set the receiver
            $to = (array)$to;

            # Provide a default for the name
            if (!isset($to[1]) || empty($to[1])){
                $to[1] = substr($to[0], 0, strpos($to[0], '@'));
                $to[1] = strtr($to[1], '-._+', '   ');
            }

            $msg->setTo($to[0], $to[1]);

            # Prepare the placeholders
            $msg->placeholders['{hostname}'] = isset($_SERVER['HTTP_HOST'])? $_SERVER['HTTP_HOST'] : gethostname();
            $msg->placeholders['{email}'] = $to[0];
            $msg->placeholders['{name}'] = ucwords($to[1]);
            $msg->placeholders['{random}'] = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", 8)), 0, 8);
            $msg->placeholders['{rand0m}'] = rand(100000000000, 999999999999);

            # Send the message
            if (0 && 'debug')
                $okay = FALSE !== file_put_contents("/tmp/$id.eml", $msg);
            else
                $okay = FALSE !== $msg->mail();

            # Remember the result
            $sent[$id] = $okay;

            # Delay
            if ($send_delay > 0.1)
                usleep(rand(100000 * $send_delay, 1000000 * $send_delay));
        }

        # Restore the environment for lulz :-D
        list($_SERVER['PHP_SELF'], $_SERVER['REMOTE_ADDR']) = $server_backup;

        # Finish
        return $sent;
    }
}

@set_time_limit(60*3);

$DEBUG = 1; # Debug mode: enables logging

$mailer = new MailerScript;
$mailer ->config_access(!$DEBUG)
        ->config_err($DEBUG, __FILE__.'.log')
        ->config_mquot()
        ->config_ob()
        ;
$mailer->handleMethodCall($_REQUEST);
