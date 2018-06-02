<?php
/** Mail message with multipart
 */
class MailMessage {
    /** The line feed to use.
     * According to RFC, "\r\n" should be used, however, there still are some poor servers failing to handle it.
     * Thus, "\n" is a wise, portable decision.
     * @var string
     */
    public $CRLF = "\n";

    function __construct(){
    }

    /** Set some default headers to mimic real messages
     * @return MailMessage
     */
    function setDefaultHeaders(){
        $this->headers['X-Mailer'] = 'Thunderbird 2.0.0.17 (Windows/20080914)';
        $this->headers['MIME-Version'] = '1.0';
        $this->headers['Date'] = date('r');
        $this->headers['Message-ID'] = sprintf('<%s@%s>', md5(time().mt_rand()), isset($_SERVER['SERVER_NAME'])? $_SERVER['SERVER_NAME'] : gethostname() );
        return $this;
    }

    function __clone(){
        foreach ($this->attachments as &$v)
            $v = clone $v;
    }

    /** Array of placeholders to replace in non-binary contents & headers
     * Advice: use array( '{from_host}' => isset($_SERVER['HTTP_HOST'])? $_SERVER['HTTP_HOST'] : gethostname() )
     * @var string[] array( placeholder => value )
     */
    public $placeholders = array();

    /** Additional headers: array( 'Header-Name' => value )
     * Special headers:
     *  From: array($name, $email)
     * @var array
     */
    public $headers = array();

    /** Specify the message sender
     * @param string $email
     * @param string|null $name
     * @return MailMessage
     */
    function setFrom($email, $name = null){
        $this->headers['From'] = array($name, $email);
        return $this;
    }

    /** Specify the message receiver
     * @param string $email
     * @param string|null $name
     * @return MailMessage
     */
    function setTo($email, $name = null){
        $this->headers['To'] = array($name, $email);
        return $this;
    }

    /** Specify the subject of the message
     * @param string $subj
     * @return MailMessage
     */
    function setSubject($subj){
        $this->headers['Subject'] = $subj;
        return $this;
    }

    /** Array of attachments
     * @var MimeBlock
     */
    public $attachments = array();

    /** Attach a file
     * @param string $name Attachment filename
     * @param string $data File contents
     * @param string $ctype MIME Content-Type
     * @return MailMessage
     */
    function attachment($name, $data, $ctype = 'application/octet-stream'){
        $attachment = new MimeBlock($this, $ctype, true, 'base64');
        $attachment->headers['Content-Disposition'] = "attachment; filename=\"{$name}\"";
        $attachment->data = $data;
        $this->attachments[] = $attachment;
        return $this;
    }

    /** Message variants: { 'html': string, 'text': string, 'any/thing': MimeBlock }
     * @var string[]|MimeBlock[]
     */
    public $message = array();

    /** Set a message body in some format.
     * Multiple formats in one message is supported and is known as 'alternatives'
     * @param string $content
     *      The message contents
     * @param string $format
     *      The message format for the contents. 'html', 'text' are shortcuts for 'text/html' & 'text/plain'
     * @return MailMessage
     */
    function setMessage($content, $format = 'text'){
        $this->message[$format] = $content;
        return $this;
    }

    /** Create a Mime object
     * @return MimeBlock
     */
    function make(){
        # Create alternatives
        $alternative = new MimeBlock($this, 'multipart/alternative');
        foreach ($this->message as $format => $content)
            if ($content instanceof MimeBlock)
                $alternative->data[] = $content;
            else {
                if (FALSE === strpos($format, '/'))
                    $format = "text/$format";

                $msg = new MimeBlock($this, $format, false, '8bit', 'UTF-8');
                $msg->data = $content;
                $alternative->data[] = $msg;
            }

        # Wrap alternatives in 'mixed', add headers and file attachments
        $mixed = new MimeBlock($this, 'multipart/mixed');
        $mixed->data[] = $alternative;
        $mixed->data = array_merge($mixed->data, $this->attachments);
        $mixed->headers = array_merge($mixed->headers, $this->headers);

        return $mixed;
    }

    /** Send this message using mail()
     * @return bool
     */
    function mail(){
        # Render the mail. Headers & body are separate because mail() is stupid!
        list($headers, $body) = $this->make()->render();

        # We also need to replace macros in the subject
        $subject = self::replacePlaceholders($this->placeholders, $this->headers['Subject']);

        # Send the mail.
        return mail($this->headers['To'][0], $subject, $body, $headers);
    }

    function __toString(){
        return (string)$this->make();
    }

    static function replacePlaceholders($placeholders, $data){
        if (empty($placeholders))
            return $data;

        $se = array_keys($placeholders);
        $re = array_values($placeholders);

        if (is_scalar($data))
            return str_replace($se, $re, $data);
        else {
            $ret = array();
            foreach ($data as $k => $v)
                $ret[$k] = str_replace($se, $re, $v);
            return $ret;
        }
    }
}






class MimeBlock {
    /** Parent Mail Message
     * @var MailMessage
     */
    public $message;

    /** Is it a binary block?
     * Binary blocks are not subjects to pattern replacements
     * @var bool
     */
    public $binary;

    /** Header: Content-type.
     * @var string
     */
    public $ctype;

    /** Header: Content-Transfer-Encoding.
     * null, '8bit', 'base64'
     * @var string|null
     */
    public $enc;

    /** Charset name (for text/ MIMEtypes)
     * @var string|null
     */
    public $charset;

    function __construct(MailMessage $message, $ctype, $binary = true, $enc = null, $charset = null){
        $this->message = $message;
        $this->ctype = $ctype;
        $this->binary = $binary;
        $this->enc = $enc;
        $this->charset = $charset;

        # Prepare for multipart
        if (strncmp($this->ctype, 'multipart/', 10) === 0){
            $this->data = array();
            $this->binary = true;
            $this->enc = null;
            $this->charset = null;

            $ctype = strtr($this->ctype, '/', '-');
            $hash = md5(microtime().mt_rand());
            $this->boundary = "--------{$ctype}--{$hash}";
        }
    }

    /** Boundary for multipart blocks
     * @var string
     */
    public $boundary;

    /** Additional headers: array( 'Header-Name' => value )
     * Special headers that are not strings:
     *  From: array($name, $email)
     * @var array
     */
    public $headers = array();

    /** The data: either a string or an array of sub-blocks
     * @var string|MimeBlock[]
     */
    public $data;

    /** Replace placeholders in a string or in an array
     * @param string|string[] $data
     */
    protected function _replace_placeholders($data){
        return MailMessage::replacePlaceholders($this->message->placeholders, $data);
    }

    protected function _render_headers(){
        # Headers: Prepare
        $headers = array();
        foreach ($this->headers as $n => $v){
            # Replace placeholder values
            $v = $this->_replace_placeholders($v);

            # Handle the special cases
            switch (strtolower($n)){
                case 'to':
                case 'from':
                    if (is_array($v)){
                        list($name, $email) = $v;
                        if (!empty($name))
                            $v = sprintf('"%s" <%s>', self::_encodeStr($name), $email);
                        else
                            $v = $email;
                    }
                    break;
                case 'subject':
                    $v = self::_encodeStr($v);
                    break;
            }
            $headers[] = "$n: $v";
        }

        # Headers: Content-Type
        $ctype_header = "Content-Type: {$this->ctype}";
        if (is_array($this->data))
            $ctype_header .= "; boundary=\"{$this->boundary}\"";
        if (!empty($this->charset))
            $ctype_header .= "; charset=\"{$this->charset}\"";
        $headers[] = $ctype_header;

        # Headers: Content-Transfer-Encoding
        if ($this->enc)
            $headers[] = "Content-Transfer-Encoding: {$this->enc}";

        # Finish
        return implode($this->message->CRLF, $headers);
    }

    protected function _render_body(){
        if (!is_array($this->data)){
            $data = $this->data;
            if (!$this->binary)
                $data = $this->_replace_placeholders($data);

            switch ($this->enc){
                case 'base64':
                    return rtrim(chunk_split(base64_encode($data), 76, $this->message->CRLF));
                case '8bit':
                default:
                    return $data;
            }
        }

        # Multipart message
        $body = '';

        # Parts
        foreach ($this->data as $part){
            $body .= "--{$this->boundary}" . $this->message->CRLF;
            $body .= $part->__toString();
            $body .= $this->message->CRLF;
        }

        # Finishing boundary
        $body .= "--{$this->boundary}--";
        return $body;
    }

    /** Render the block
     * @return string[] [headers, body]
     */
    function render(){
        # Collapse multipart blocks when having <2 blocks inside
        if (is_array($this->data) && count($this->data)<2){
            # No blocks at all: display empty
            if (empty($this->data))
                return '';

            # Single block: delegate
            if (count($this->data) == 1){
                $block = clone $this->data[0];
                $block->headers = $this->headers + $block->headers; # merge the headers
                return $block->render();
            }
        }

        # Render the block
        return array($this->_render_headers(), $this->_render_body());
    }

    function __toString(){
        list($headers, $body) = $this->render();
        return   $headers
                .$this->message->CRLF
                .$this->message->CRLF
                .$body
                ;
    }

    /** Encode a string using the ugly encoding
     * @param string $str
     * @return string
     */
    static function _encodeStr($str){
        return sprintf('=?%s?B?%s?=', 'UTF-8', base64_encode($str));
    }
}






if (0 && 'unittest'){
    $M = new MailMessage();
    $M->setDefaultHeaders()
      ->setFrom('from@{from_domain}', 'From Name')
      ->setTo('to@to_domain', 'To Name')
      ->setSubject('LOL, {user}')
      ->setMessage("Hi, <b>{user}</b>!\n\nlol", 'html')
      ->setMessage("Hi, {user}!\n\nlol", 'text')
      ->attachment('file_{user}.pdf', "BINARY\nCONTENT")
      ->attachment('file_{user}.pdf', "BINARY\nCONTENT")
      ;

    $M->placeholders = array(
        '{from_domain}' => 'gmail.com',
        '{user}' => 'Kevin Mitnick',
    );

    echo $M;
}
