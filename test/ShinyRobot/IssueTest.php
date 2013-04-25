<?php
namespace ShinyRobot;


use ShinyRobot\Log\Message;

class IssueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Phake_IMock
     */
    private $api;

    private $priorityNormal = 4;
    private $priorityHigh = 5;
    private $priorityAsap = 6;

    protected function setUp()
    {
        $this->api = \Phake::mock('ShinyRobot\Api');
        \Phake::when($this->api)->getPriorityNormal()->thenReturn($this->priorityNormal);
        \Phake::when($this->api)->getPriorityHigh()->thenReturn($this->priorityHigh);
        \Phake::when($this->api)->getPriorityAsap()->thenReturn($this->priorityAsap);
    }

    public function testSetSubject()
    {
        $issue = new Issue($this->api);
        $subject = 's & s' . str_repeat('s', 300);
        $issue->setSubject($subject);
        $data = $issue->toArray();
        $this->assertEquals(255, strlen($data['subject']));
        $this->assertContains('&amp;', $data['subject']);
    }

    public function testIncCount()
    {
        $issue = new Issue($this->api);
        $id = 42;
        $count = 100;
        \Phake::when($this->api)->getCustomFieldCount()->thenReturn($id);
        $issue->setCount($count);
        $this->assertCustomFieldValue($issue, $id, $count);

        $issue->setCount($count + 1);
        $this->assertCustomFieldValue($issue, $id, $count + 1);
    }

    public function testSetKey()
    {
        $issue = new Issue($this->api);
        $id = 42;
        $key = 'abcde';
        \Phake::when($this->api)->getCustomFieldKey()->thenReturn($id);
        $issue->setKey($key);
        $this->assertCustomFieldValue($issue, $id, $key);
    }

    /**
     * @param Issue $issue
     * @param int $fieldId
     * @param mixed $value
     */
    private function assertCustomFieldValue(Issue $issue, $fieldId, $value)
    {
        $data = $issue->toArray();
        foreach ($data['custom_fields'] as $field) {
            if ($field['id'] == $fieldId) {
                return $this->assertEquals($value, $field['value']);
            }
        }

        $this->fail("custom field $fieldId doesn't have value $value");
    }

    public function prioritiesByErrorProvider()
    {
        return array(
            array('Fatal error', $this->priorityAsap),
            array('parse ERROR', $this->priorityAsap),
            array('core error', $this->priorityAsap),
            array('compile error', $this->priorityAsap),
            array('exception', $this->priorityAsap),
            array('some EXCEPTION with code', $this->priorityAsap),
            array('Zend_Cache_Exception', $this->priorityAsap),

            array('recoverable error', $this->priorityHigh),
            array('core warning', $this->priorityHigh),
            array('compile warning', $this->priorityHigh),
            array('warning', $this->priorityHigh),

            array('Notice', $this->priorityNormal),
            array(' ', $this->priorityNormal),
        );
    }

    /**
     * @dataProvider prioritiesByErrorProvider
     */
    public function testSetPriorityByString($priorityString, $expectedPriorityId)
    {
        $issue = new Issue($this->api);
        $issue->setPriorityByString($priorityString);
        $data = $issue->toArray();
        $this->assertEquals($expectedPriorityId, $data['priority_id']);
    }

    public function testSetDescription()
    {
        $issue = new Issue($this->api);
        $message = new Message(array(
            'time' => '12:57:01',
            'text' => 'text & text',
            'url' => 'http://www.google.com',
            'referer' => 'referer',
            'ip' => '127.0.0.1',
            'stackTrace' => 'stack trace'
        ));

        $expected = <<<EXP
* Time: 12:57:01
* Text: text &amp; text
* Url: http://www.google.com
* Referer: referer
* IP: 127.0.0.1
* Stack trace:
stack trace
EXP;



        $issue->setDescription($message);
        $data = $issue->toArray();
        $this->assertEquals($expected, $data['description']);
    }

    public function testHasId()
    {
        $issue = new Issue($this->api);
        $this->assertFalse($issue->hasId());

        $issue = new Issue($this->api, array('id' => 0));
        $this->assertFalse($issue->hasId());

        $issue = new Issue($this->api, array('id' => 42));
        $this->assertTrue($issue->hasId());
    }

    public function testOpen()
    {
        $issue = new Issue($this->api);
        $this->assertTrue($issue->isClosed());

        \Phake::when($this->api)->getStateNew()->thenReturn(1);
        $issue->open();
        $data = $issue->toArray();
        $this->assertEquals(1, $data['status_id']);
    }

    public function testGetCustomField()
    {
        $countId = 42;
        $count = 100;
        $lastTimestampId = 20;
        \Phake::when($this->api)->getCustomFieldCount()->thenReturn($countId);
        \Phake::when($this->api)->getCustomFieldLastTimestamp()->thenReturn($lastTimestampId);
        $data = array(
            'custom_fields' => array(
                array(
                    'id'    => $countId,
                    'value' => $count
                ),
                array(
                    'id'    => $lastTimestampId,
                )
            )
        );
        $issue = new Issue($this->api, $data);
        $this->assertEquals($count, $issue->getCount());
        $this->assertEquals(0, $issue->getLastTimestamp());
    }
}
