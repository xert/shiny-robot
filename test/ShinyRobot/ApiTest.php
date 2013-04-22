<?php
namespace ShinyRobot;


class ApiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Api
     */
    private $api;

    /**
     * @var \Phake_IMock
     */
    private $client;

    private $config = array(
        'project' => 1,
        'tracker' => 2,
        'state_new' => 3,
        'state_closed' => 4,
        'custom_field_count' => 5,
        'custom_field_key' => 6,
        'custom_field_last_timestamp' => 10,
        'priority_normal' => 7,
        'priority_high' => 8,
        'priority_asap' => 9,
    );

    protected function setUp()
    {
        $this->client = \Phake::mock('Redmine\Client');
        $this->api = new Api($this->config, $this->client);
    }

    public function testCreateIssue()
    {
        $issue = $this->api->createIssue();
        $this->assertInstanceOf('ShinyRobot\Issue', $issue);
        $this->assertFalse($issue->isClosed());

        $data = $issue->toArray();
        $this->assertEquals($this->config['project'], $data['project_id']);
        $this->assertEquals($this->config['tracker'], $data['tracker_id']);
        $this->assertEquals($this->config['state_new'], $data['state_id']);
    }

    public function testFindByKey()
    {
        $issues = \Phake::mock('Redmine\Api\Issue');
        \Phake::when($this->client)->api('issue')->thenReturn($issues);
        \Phake::when($issues)->all(\Phake::capture($data))->thenReturn(array('issues' => array(array())));

        $key = 'abc';
        $issue = $this->api->findByKey($key);

        $this->assertInstanceOf('ShinyRobot\Issue', $issue);
        $cfKey = 'cf_' . $this->config['custom_field_key'];
        $this->assertEquals($key, $data[$cfKey]);
        $this->assertEquals($this->config['tracker'], $data['tracker_id']);
        $this->assertEquals($this->config['project'], $data['project_id']);
        $this->assertEquals('*', $data['status_id']);
    }

    public function testSaveCreate()
    {
        $issue = new Issue($this->api);
        $issues = \Phake::mock('Redmine\Api\Issue');
        \Phake::when($this->client)->api('issue')->thenReturn($issues);
        $this->api->save($issue);
        \Phake::verify($issues)->create($issue->toArray());
    }

    public function testSaveUpdate()
    {
        $id = 42;
        $issue = new Issue($this->api, array('id' => $id));
        $issues = \Phake::mock('Redmine\Api\Issue');
        \Phake::when($this->client)->api('issue')->thenReturn($issues);
        $this->api->save($issue);
        \Phake::verify($issues)->update($id, $issue->toArray());
    }

    public function testDryRun()
    {
        $stream = fopen('php://memory', 'w');
        $this->api->enableDryRun($stream);

        rewind($stream);
        $content = stream_get_contents($stream);
        $this->assertContains("Dry run", $content);
    }
}
