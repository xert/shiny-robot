<?php
namespace ShinyRobot;
use ShinyRobot\Log\Message;

/**
 * Kontrola, zda ma byt dana chyba poslana do Redminu nebo tise ignorovana.
 */
class Checker
{
    /**
     * @param Message $message
     * @return bool TRUE pokud ma byt zprava zaslana do Redmine, FALSE pokud se ma ignorovat
     */
    public function check(Message $message)
    {
        return ! $this->isSessionRemoteAddressException($message) &&
               ! $this->isSessionUserAgentException($message) &&
               ! $this->isUnsupportedBrowser($message);
    }

    /**
     * 'Zend_Session_Exception' with message 'This session is not valid according to j3_Session_Validator_RemoteAddress. Old ip: 127.0.0.1 - new ip: 168.0.0.1' in /data/www/production/gdi/lib/Zend/Session.php:786
     * @param Message $message
     * @return bool Zda se jedna o validacni vyjimku "Zend_Session_Exception".
     */
    private function isSessionRemoteAddressException(Message $message)
    {
        return ( $this->isException($message) &&
            $this->contains($message, 'Zend_Session_Exception') &&
            $this->contains($message, 'j3_Session_Validator_RemoteAddress') );
    }

    /**
     * 'Zend_Session_Exception' with message 'This session is not valid according to Zend_Session_Validator_HttpUserAgent.' in /data/www/production/gdi/lib/Zend/Session.php:786
     * @param Message $message
     * @return bool
     */
    private function isSessionUserAgentException(Message $message)
    {
        return ( $this->isException($message) &&
            $this->contains($message, 'Zend_Session_Exception') &&
            $this->contains($message, 'Zend_Session_Validator_HttpUserAgent') );
    }

    /**
     * Nepodporovany prohlizec: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; .NET CLR 1.1.4322; .NET CLR 2.0.50727) z adresy 194.145.185.66 (66.185.145.194.ip4.artcom.pl)
     * @param Message $message
     * @return bool
     */
    private function isUnsupportedBrowser(Message $message)
    {
        return ($this->contains($message, 'Nepodporovany prohlizec: '));
    }

    /**
     * @param Message $message
     * @return bool
     */
    private function isException(Message $message)
    {
        return strtolower(trim($message->getType())) == 'exception';
    }

    /**
     * @param Message $message
     * @param string $text
     * @return bool Zda text zpravy obsahuje $text
     */
    private function contains(Message $message, $text)
    {
        return strpos($message->getText(), $text) !== false;
    }
}
