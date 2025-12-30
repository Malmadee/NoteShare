<?php
/**
 * Simple SMTP Mailer for Gmail
 * Direct SMTP connection without external dependencies
 */

class SimpleSmtpMailer {
    private $host = 'smtp.gmail.com';
    private $port = 587;
    private $username = 'webnoteshare@gmail.com';
    private $password = 'uhpvkihmsgqzauwh';
    private $socket = null;
    private $timeout = 30;
    
    public function __construct($username, $password) {
        $this->username = $username;
        $this->password = $password;
    }
    
    /**
     * Connect to SMTP server and authenticate
     */
    public function connect() {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
        
        if (!$this->socket) {
            throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
        }
        
        // Get initial response
        $response = $this->readResponse();
        if (!$this->isSuccess($response)) {
            throw new Exception("Server rejected connection: $response");
        }
        
        // Send EHLO
        $this->sendCommand("EHLO NoteShare");
        $this->readResponse();
        
        // Start TLS
        $this->sendCommand("STARTTLS");
        $response = $this->readResponse();
        if (!$this->isSuccess($response)) {
            throw new Exception("STARTTLS failed: $response");
        }
        
        // Enable TLS
        if (!stream_context_set_option($this->socket, 'ssl', 'allow_self_signed', true)) {
            throw new Exception("Failed to set SSL context");
        }
        
        if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new Exception("Failed to enable TLS encryption");
        }
        
        // After TLS, send EHLO again
        $this->sendCommand("EHLO NoteShare");
        $this->readResponse();
        
        // Authenticate
        $this->authenticate();
    }
    
    /**
     * Authenticate with Gmail
     */
    private function authenticate() {
        // Send AUTH LOGIN
        $this->sendCommand("AUTH LOGIN");
        $response = $this->readResponse();
        
        if (!$this->isSuccess($response)) {
            throw new Exception("AUTH LOGIN failed: $response");
        }
        
        // Send base64 encoded username
        $this->sendCommand(base64_encode($this->username));
        $response = $this->readResponse();
        
        if (!$this->isSuccess($response)) {
            throw new Exception("Username rejected: $response");
        }
        
        // Send base64 encoded password
        $this->sendCommand(base64_encode($this->password));
        $response = $this->readResponse();
        
        if (!$this->isSuccess($response)) {
            throw new Exception("Password rejected: $response");
        }
    }
    
    /**
     * Send email
     *
     * @param string $from
     * @param string $to
     * @param string $subject
     * @param string $htmlBody
     * @param string $textBody
     * @param string $replyTo Optional Reply-To address
     */
    public function send($from, $to, $subject, $htmlBody, $textBody = '', $replyTo = '') {
        // SET FROM
        $this->sendCommand("MAIL FROM:<$from>");
        $response = $this->readResponse();
        if (!$this->isSuccess($response)) {
            throw new Exception("MAIL FROM failed: $response");
        }
        
        // SET TO
        $this->sendCommand("RCPT TO:<$to>");
        $response = $this->readResponse();
        if (!$this->isSuccess($response)) {
            throw new Exception("RCPT TO failed: $response");
        }
        
        // DATA
        $this->sendCommand("DATA");
        $response = $this->readResponse();
        if (!$this->isSuccess($response)) {
            throw new Exception("DATA failed: $response");
        }
        
        // Build email message
        $message = "From: $from\r\n";
        $message .= "To: $to\r\n";
        if (!empty($replyTo)) {
            $message .= "Reply-To: $replyTo\r\n";
        }
        // Add a Date header
        $message .= "Date: " . date('r') . "\r\n";
        $message .= "Subject: $subject\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: multipart/alternative; boundary=\"boundary\"\r\n";
        $message .= "\r\n";
        
        // Text part
        if (!empty($textBody)) {
            $message .= "--boundary\r\n";
            $message .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
            $message .= "Content-Transfer-Encoding: quoted-printable\r\n";
            $message .= "\r\n";
            $message .= $textBody;
            $message .= "\r\n";
        }
        
        // HTML part
        $message .= "--boundary\r\n";
        $message .= "Content-Type: text/html; charset=\"UTF-8\"\r\n";
        $message .= "Content-Transfer-Encoding: quoted-printable\r\n";
        $message .= "\r\n";
        $message .= $htmlBody;
        $message .= "\r\n";
        $message .= "--boundary--\r\n";
        
        // Send message
        fwrite($this->socket, $message . "\r\n.\r\n");
        $response = $this->readResponse();
        
        if (!$this->isSuccess($response)) {
            throw new Exception("Message rejected: $response");
        }
    }
    
    /**
     * Close connection
     */
    public function close() {
        if ($this->socket) {
            $this->sendCommand("QUIT");
            @fclose($this->socket);
            $this->socket = null;
        }
    }
    
    /**
     * Send a command to SMTP server
     */
    private function sendCommand($command) {
        fwrite($this->socket, $command . "\r\n");
        fflush($this->socket);
    }
    
    /**
     * Read response from SMTP server
     */
    private function readResponse() {
        $response = '';
        while (!feof($this->socket)) {
            $line = fgets($this->socket, 512);
            $response .= $line;
            
            // Check if this is the last line of response
            if (strlen($line) >= 4 && ctype_digit(substr($line, 0, 3))) {
                if ($line[3] === ' ') {
                    break; // Last line
                }
            }
        }
        return $response;
    }
    
    /**
     * Check if response is successful (2xx or 3xx)
     */
    private function isSuccess($response) {
        if (empty($response)) {
            return false;
        }
        $code = intval(substr($response, 0, 3));
        return ($code >= 200 && $code < 400);
    }
    
    public function __destruct() {
        $this->close();
    }
}
?>
