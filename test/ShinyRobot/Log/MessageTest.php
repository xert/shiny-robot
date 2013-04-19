<?php
namespace ShinyRobot\Log;

class MessageTest extends \PHPUnit_Framework_TestCase
{
    public function testMessage()
    {
        $data = array(
            'time' => '01-Feb-2010 09:29:06',
            'type' => '_type_',
            'text' => '_text_',
            'key'  => '_key_',
            'stackTrace' => '_trace_',
            'url' => '_url_',
            'referer' => '_referer_',
            'notAnAttrib' => '_notAnAttrib_',
        );
        $message = new Message($data);

        $this->assertEquals($data['time'], $message->getTime());
        $this->assertEquals($data['type'], $message->getType());
        $this->assertEquals($data['text'], $message->getText());
        $this->assertEquals($data['key'], $message->getKey());
        $this->assertEquals($data['stackTrace'], $message->getStackTrace());
        $this->assertEquals($data['url'], $message->getUrl());
        $this->assertEquals($data['referer'], $message->getReferer());
        $this->assertFalse(isset($message->notAnAttrib));
    }

    public function testSetKey()
    {
        $message = new Message(array('key' => 'cxxx'));
        $this->assertNotStartsWithSmallC($message->getKey());

        $message = new Message(array('key' => 'Cxxx'));
        $this->assertNotStartsWithSmallC($message->getKey());

        $message = new Message(array('key' => 'xcxxx'));
        $this->assertNotStartsWithSmallC($message->getKey());
    }

    /**
     * @param string $string
     */
    private function assertNotStartsWithSmallC($string)
    {
        $this->assertNotEquals(0, strncasecmp($string, 'c', 1));
    }

    public function testSetInvalidTimeSetsActualTimestamp()
    {
        $message = new Message(array('time' => false));
        $this->assertTrue($message->getTimestamp() > 0);
    }
}
