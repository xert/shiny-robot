<?php
namespace ShinyRobot;

use ShinyRobot\Log\Message;

class RobotTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Robot
     */
    private $robot;

    private $messages = array();

    /**
     * @var \Phake_IMock
     */
    private $checker;

    /**
     * @var \Phake_IMock
     */
    private $logResult;

    /**
     * @var \Phake_IMock
     */
    private $api;

    protected function setUp()
    {
        $this->logResult = \Phake::mock('ShinyRobot\Log\Parser\Result');
        $that = $this;
        \Phake::when($this->logResult)->getMessages()->thenGetReturnByLambda(function() use($that) { return $that->messages; });

        $this->checker = \Phake::mock('ShinyRobot\Checker');
        \Phake::when($this->checker)->check(\Phake::anyParameters())->thenReturn(true);

        $this->api = \Phake::mock('ShinyRobot\Api');

        $this->robot = new Robot($this->api, $this->checker, 1);
    }

    public function testNoErrorsSentIfCheckerRejectsThem()
    {
        \Phake::when($this->checker)->check(\Phake::anyParameters())->thenReturn(false);
        $this->messages[] = new Message(array());
        $this->robot->sendToRedmine($this->logResult);
        \Phake::verify($this->checker, \Phake::times(1))->check(\Phake::anyParameters());
        \Phake::verify($this->api, \Phake::times(0))->save(\Phake::anyParameters());
    }

    public function testUpdateExistingIssue()
    {
        $key = 'abckey';
        $this->messages[] = new Message(array('key' => $key, 'count' => 2));

        $issue = new Issue($this->api, array('id' => 1));
        \Phake::when($this->api)->findByKey($key)->thenReturn($issue);
        \Phake::when($this->api)->getStateNew()->thenReturn(1);
        \Phake::when($this->api)->getCustomFieldCount()->thenReturn(1);

        $this->robot->sendToRedmine($this->logResult);

        $data = $issue->toArray();
        $this->assertEquals(1, $data['status_id']); // issue je znovu otevrena
        $this->assertCustomFieldValue($issue, 1, 2);

        \Phake::verify($this->api)->save($issue);
    }

    public function testCreateNewIssue()
    {
        $key = 'abckey';
        $this->messages[] = new Message(array('key' => $key));

        $issue = new Issue($this->api);
        \Phake::when($this->api)->getCustomFieldKey()->thenReturn(1);
        \Phake::when($this->api)->createIssue()->thenReturn($issue);

        $this->robot->sendToRedmine($this->logResult);

        $this->assertCustomFieldValue($issue, 1, $key);
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
        $this->messages = array(
            new Message(array()),
            new Message(array()),
        );
        \Phake::when($this->api)->createIssue()->thenReturn(new Issue($this->api));
        $this->robot->sendToRedmine($this->logResult);

        \Phake::verify($this->api, \Phake::times(1))->save(\Phake::anyParameters());
    }
}
