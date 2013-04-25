<?php
namespace ShinyRobot;

use ShinyRobot\Log\Message;
use ShinyRobot\Log\Parser\AbstractParser;

class RobotTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Robot
     */
    private $robot;

    /**
     * @var \Phake_IMock
     */
    private $checker;

    /**
     * @var TestParser
     */
    private $parser;

    /**
     * @var \Phake_IMock
     */
    private $api;

    protected function setUp()
    {
        $this->checker = \Phake::mock('ShinyRobot\Checker');
        \Phake::when($this->checker)->check(\Phake::anyParameters())->thenReturn(true);

        $this->api = \Phake::mock('ShinyRobot\Api');

        $this->robot = new Robot($this->api, $this->checker, 1);

        $this->parser = new TestParser('file_path');
    }

    public function testNoErrorsSentIfCheckerRejectsThem()
    {
        \Phake::when($this->checker)->check(\Phake::anyParameters())->thenReturn(false);
        $this->parser->messages[] = new Message(array());
        $this->robot->sendToRedmine($this->parser);
        \Phake::verify($this->checker, \Phake::times(1))->check(\Phake::anyParameters());
        \Phake::verify($this->api, \Phake::times(0))->save(\Phake::anyParameters());
    }

    public function testUpdateExistingIssue()
    {
        $key = 'abckey';
        $this->parser->messages[] = new Message(array('key' => $key, 'timestamp' => 200));

        $issue = new Issue($this->api, array(
            'id'            => 1,
            'custom_fields' => array(
                array(
                    'id'    => 1, // count
                    'value' => 3
                ),
                array(
                    'id'    => 2, // last timestamp
                    'value' => 100
                )
            )
        ));
        \Phake::when($this->api)->findByKey($key)->thenReturn($issue);
        \Phake::when($this->api)->getStateNew()->thenReturn(1);
        \Phake::when($this->api)->getCustomFieldCount()->thenReturn(1);
        \Phake::when($this->api)->getCustomFieldLastTimestamp()->thenReturn(2);

        $this->robot->sendToRedmine($this->parser);

        $data = $issue->toArray();
        $this->assertEquals(1, $data['status_id']); // issue je znovu otevrena
        $this->assertCustomFieldValue($issue, 1, 4); // count

        \Phake::verify($this->api)->save($issue);
    }

    public function testCreateNewIssue()
    {
        $key = 'abckey';
        $this->parser->messages[] = new Message(array('key' => $key));

        $issue = new Issue($this->api);
        \Phake::when($this->api)->getCustomFieldKey()->thenReturn(1);
        \Phake::when($this->api)->getCustomFieldCount()->thenReturn(2);
        \Phake::when($this->api)->createIssue()->thenReturn($issue);

        $this->robot->sendToRedmine($this->parser);

        $this->assertCustomFieldValue($issue, 1, $key);
        $this->assertCustomFieldValue($issue, 2, 1); // count
        \Phake::verify($this->api)->save($issue);
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

    public function testProcessOnlyLimitedAmountOfMessages()
    {
        $this->parser->messages = array(
            new Message(array()),
            new Message(array()),
        );
        \Phake::when($this->api)->createIssue()->thenReturn(new Issue($this->api));
        $this->robot->sendToRedmine($this->parser);

        \Phake::verify($this->api, \Phake::times(1))->save(\Phake::anyParameters());
        // overime, ze se nenacetly vsechny zpravy, ale iteruje se postupne
        $this->assertEquals(0, $this->parser->position);
    }
}

class TestParser extends AbstractParser
{
    public $messages = array();

    public $position = 0;

    public function __construct($filePath)
    {
    }

    protected function parseMessage($messageText)
    {
    }

    function rewind() {
        $this->position = 0;
    }

    function current()
    {
        return $this->messages[$this->position];
    }

    function key()
    {
        return $this->position;
    }

    function next()
    {
        ++$this->position;
    }

    function valid()
    {
        return isset($this->messages[$this->position]);
    }

    /**
     * @param string $line Radek z logu.
     * @return bool Zda se jedna o radek, kterym zacina chyba v logu.
     */
    protected function isStartOfMessage($line)
    {
        return true;
    }
}