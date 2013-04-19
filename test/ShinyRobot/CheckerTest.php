<?php
namespace ShinyRobot;

use ShinyRobot\Log\Message;

class CheckerTest extends \PHPUnit_Framework_TestCase
{
    public function testCheckSessionRemoteAddress()
    {
        $text = "'Zend_Session_Exception' with message 'This session is not valid according to j3_Session_Validator_RemoteAddress. Old ip: 127.0.0.1 - new ip: 168.0.0.1' in /data/www/production/gdi/lib/Zend/Session.php:786";
        $this->assertNotChecked($text);
    }

    public function testCheckSessionUserAgent()
    {
        $text = "'Zend_Session_Exception' with message 'This session is not valid according to Zend_Session_Validator_HttpUserAgent.' in /data/www/production/gdi/lib/Zend/Session.php:786";
        $this->assertNotChecked($text);
    }

    public function testCheckUnsupportedBrowser()
    {
        $text = "Nepodporovany prohlizec: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; .NET CLR 1.1.4322; .NET CLR 2.0.50727) z adresy 194.145.185.66 (66.185.145.194.ip4.artcom.pl)";
        $this->assertNotChecked($text, 'warning');
    }

    /**
     * Overi, ze zprava s danym textem ma byt ignorovana.
     *
     * @param string $text
     * @param string $messageType
     */
    private function assertNotChecked($text, $messageType = 'exception')
    {
        $message = new Message(array(
            'type' => $messageType,
            'text' => $text
        ));
        $checker = new Checker();
        $this->assertFalse($checker->check($message));
    }
}
