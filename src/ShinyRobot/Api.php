<?php
namespace ShinyRobot;

use Redmine\Client;

class Api
{
    /**
     * @var \Redmine\Client
     */
    private $client;

    /**
     * @var int Id Redmine projektu, se kterym se pracuje.
     */
    private $projectId;

    /**
     * @var int Id Redmine trackeru, se kterym se pracuje.
     */
    private $trackerId;

    /**
     * Id custom poli v Redmine.
     */
    private $customFieldCount = 38;
    private $customFieldKey = 37;
    private $customFieldLastTimestamp = 41;

    /**
     * Id statusu v Redminu.
     */
    private $stateNew = 1;
    private $stateClosed = 5;

    /**
     * Id priorit v Redmine.
     */
    private $priorityNormal = 4;
    private $priorityHigh = 5;
    private $priorityAsap = 6;

    /**
     * @var bool Pokud je TRUE, neprovadi zadne operace zapisu, ale pouze zapisuje do konzole.
     */
    private $dryRun = false;

    /**
     * @var resource Pro zapis pri $dryRun == TRUE.
     */
    private $outputStream;

    function __construct(array $config, Client $client)
    {
        $this->client = $client;
        $this->setup($config);
    }

    private function setup(array $config)
    {
        $this->projectId = (int) $config['project'];
        $this->trackerId = (int) $config['tracker'];
        $this->stateNew = (int) $config['state_new'];
        $this->stateClosed = (int) $config['state_closed'];
        $this->customFieldCount = (int) $config['custom_field_count'];
        $this->customFieldKey = (int) $config['custom_field_key'];
        $this->customFieldLastTimestamp = (int) $config['custom_field_last_timestamp'];
        $this->priorityNormal = (int) $config['priority_normal'];
        $this->priorityHigh = (int) $config['priority_high'];
        $this->priorityAsap = (int) $config['priority_asap'];
    }

    /**
     * @param string $key
     * @return null|Issue
     */
    public function findByKey($key)
    {
        $cfKey = 'cf_' . $this->customFieldKey;

        $issues = $this->client->api('issue')->all(array(
            'project_id' => $this->projectId,
            'tracker_id' => $this->trackerId,
            $cfKey       => $key,
            'status_id'  => '*', // vsechny stavy
        ));

        if (empty($issues['issues'])) {
            return null;
        }

        $data = reset($issues['issues']);

        return new Issue($this, $data);
    }

    public function createIssue()
    {
        $data = array(
            'project_id' => $this->projectId,
            'tracker_id' => $this->trackerId,
            'state_id'   => $this->stateNew
        );

        return new Issue($this, $data);
    }

    public function getStateClosed()
    {
        return $this->stateClosed;
    }

    public function getStateNew()
    {
        return $this->stateNew;
    }

    public function getCustomFieldCount()
    {
        return $this->customFieldCount;
    }

    public function getCustomFieldKey()
    {
        return $this->customFieldKey;
    }

    public function getPriorityAsap()
    {
        return $this->priorityAsap;
    }

    public function getPriorityHigh()
    {
        return $this->priorityHigh;
    }

    public function getPriorityNormal()
    {
        return $this->priorityNormal;
    }

    public function getCustomFieldLastTimestamp()
    {
        return $this->customFieldLastTimestamp;
    }

    public function save(Issue $issue)
    {
        $api = $this->client->api('issue');

        if ($issue->hasId()) {
            if ($this->dryRun) {
                fwrite($this->outputStream, "Aktualizuji issue s id {$issue->getId()}\n");
            } else {
                $api->update($issue->getId(), $issue->toArray());
            }
        } else {
            if ($this->dryRun) {
                fwrite($this->outputStream, "Vytvarim novou issue\n");
            } else {
                $api->create($issue->toArray());
            }
        }
    }

    /**
     * @param resource $outputStream
     * @return Api
     */
    public function enableDryRun($outputStream)
    {
        $this->dryRun = true;
        $this->outputStream = $outputStream;
        return $this;
    }
}
