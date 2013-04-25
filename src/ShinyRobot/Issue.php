<?php
namespace ShinyRobot;

use ShinyRobot\Log\Message;

class Issue
{
    private $data = array();

    /**
     * @var array Puvodni data nactena z Redmine.
     */
    private $origData = array();

    /**
     * @var Api
     */
    private $api;

    function __construct(Api $api, array $data = array())
    {
        $this->api = $api;
        $this->origData = $data;
        $this->setupDataFromOriginalData();
    }

    private function setupDataFromOriginalData()
    {
        $keysToCopy = array('id', 'project_id', 'tracker_id', 'state_id');
        foreach ($keysToCopy as $key) {
            if (isset($this->origData[$key])) {
                $this->data[$key] = $this->origData[$key];
            }
        }
    }

    /**
     * @param string $name
     * @return mixed
     */
    private function getOrigValue($name)
    {
        return isset($this->origData[$name]) ? $this->origData[$name] : null;
    }

    public function isClosed()
    {
        return $this->getOrigValue('status_id') == $this->api->getStateClosed();
    }

    /**
     * Nastavi issue stav "novy".
     */
    public function open()
    {
        $this->data['status_id'] = $this->api->getStateNew();
    }

    public function getCount()
    {
        $id = $this->api->getCustomFieldCount();

        return $this->getCustomFieldValue($id);
    }

    public function getLastTimestamp()
    {
        $id = $this->api->getCustomFieldLastTimestamp();

        return $this->getCustomFieldValue($id);
    }

    /**
     * @param int $fieldId
     * @return int
     */
    private function getCustomFieldValue($fieldId)
    {
        if (isset($this->origData['custom_fields'])) {
            foreach ($this->origData['custom_fields'] as $field) {
                if ($field['id'] == $fieldId) {
                    return isset($field['value']) ? $field['value'] : 0;
                }
            }
        }

        return 0;
    }

    public function setCount($count)
    {
        return $this->setCustomField($this->api->getCustomFieldCount(), (int) $count);
    }

    /**
     * @param int $id
     * @param mixed $value
     * @return $this
     */
    private function setCustomField($id, $value)
    {
        if (! isset($this->data['custom_fields'])) {
            $this->data['custom_fields'] = array();
        }

        foreach ($this->data['custom_fields'] as $key => $field) {
            if ($field['id'] == $id) {
                unset($this->data['custom_fields'][$key]);
                break;
            }
        }

        $this->data['custom_fields'][] = array(
            'id'    => $id,
            'value' => $value
        );

        return $this;
    }

    /**
     * Subject muze mit max. 255 znaku.
     * @param string $subject
     * @return Issue
     */
    public function setSubject($subject)
    {
        $subject = htmlentities($subject, ENT_QUOTES);
        $this->data['subject'] = substr($subject, 0, 255);
        return $this;
    }

    public function setKey($key)
    {
        return $this->setCustomField($this->api->getCustomFieldKey(), $key);
    }

    public function setLastTimestamp($lastTimestamp)
    {
        return $this->setCustomField($this->api->getCustomFieldLastTimestamp(), $lastTimestamp);
    }

    private function setPriority($priorityId)
    {
        $this->data['priority_id'] = $priorityId;

        return $this;
    }

    public function setDescription(Message $error)
    {
        $description = "* Time: {$error->getTime()}\n"
            . "* Text: {$error->getText()}\n"
            . "* Url: {$error->getUrl()}\n"
            . "* Referer: {$error->getReferer()}\n"
            . "* IP: {$error->getIp()}\n"
            . "* Stack trace:\n"
            . $error->getStackTrace();

        $this->data['description'] = htmlentities($description, ENT_QUOTES);

        return $this;
    }

    /**
     * Podle priority chyby nastavi interni prioritu Redminu.
     *
     * @param string $priority Nazev chyby, napr. "Notice".
     * @return $this
     */
    public function setPriorityByString($priority)
    {
        $priority = strtolower($priority);

        switch ($priority) {
            case 'fatal error':
            case 'parse error':
            case 'core error':
            case 'compile error':
            case strpos($priority, 'exception') !== false:
                return $this->setPriority($this->api->getPriorityAsap());

            case 'recoverable error':
            case 'core warning':
            case 'compile warning':
            case 'warning':
                return $this->setPriority($this->api->getPriorityHigh());

            default:
                return $this->setPriority($this->api->getPriorityNormal());
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    public function hasId()
    {
        return $this->getId() > 0;
    }

    public function getId()
    {
        return isset($this->origData['id']) ? $this->origData['id'] : null;
    }
}
