<?php
namespace ShinyRobot\Log;
/**
 * Obalka pro zpravu ziskanou z logu.
 */
class Message
{
    /**
     * @var string Format casu dle PHP funkce date().
     */
    private $timeFormat = 'd-M-Y H:i:s';

    private $time;
    private $type;
    private $text;
    private $key;
    private $url;
    private $ip;
    private $referer;
    private $stackTrace;
    private $timestamp;

    /**
     * @var array Casy vyskytu stejne zpravy.
     */
    private $times = array();

    /**
     * @var bool Pokud je TRUE, jsou pole "data" retezena za sebe.
     */
    private $merge = false;

    /**
     * @var array Dalsi data, ktera mohou byt ke zprave pripojena.
     */
    private $data = array();
    
    /**
     * @var int Pocet vyskytu chyby.
     */
    private $count = 0;


    /**
     * @param array $message
     */
    public function __construct(array $message)
    {
        if (isset($message['timeFormat'])) {
            $this->setTimeFormat($message['timeFormat']);
            unset($message['timeFormat']);
        }

        foreach ( $message as $key => $value ) {
            $methodName = 'set' . ucfirst(strtolower($key));
            if (method_exists($this, $methodName) ) {
                // setter
                $this->$methodName($value);
            } else if ( property_exists($this, $key) ) {
                // atribut
                $this->{$key} = $value;
            }
        }
    }


    /**
     * @param Message $message
     * @return int
     *   0 pokud jsou klice stejne
     *  -1 pokud je klic $message vetsi, nez vlastni klic
     *   1 pokud je klic $message mensi, nez vlastni klic
     */
    public function compareByKey(Message $message)
    {
        return strncasecmp($this->getKey(), $message->getKey(), 32);
    }


    /**
     * Zpravy jsou razeny od nejmladsi po nejstarsi.
     *
     * @param Message $message
     * @return int
     *   0 pokud jsou casy stejne
     *  -1 pokud je cas $message vetsi, nez mistni
     *   1 pokud je cas $message mensi, nez mistni
     */
    public function compareByTime(Message $message)
    {
        if ( $this->getTimestamp() == $message->getTimestamp() ) {
            return 0;
        }

        return ($this->getTimestamp() < $message->getTimestamp()) ? 1 : -1;
    }


    /**
     * @param string $time
     */
    public function setTime($time)
    {
        $this->time = $time;
        $datetime = \DateTime::createFromFormat($this->timeFormat, $time);
        $this->timestamp = $datetime ? $datetime->getTimestamp() : time();
        if ( empty($this->times) ) {
            $this->addOccurence($time);
        }
    }


    /**
     * Redmine pri pokusu o ziskani issue pres REST API podle custom field,
     * jehoz hodnota zacina pismenem "c" vyhodi chybu. Proto se teto situaci
     * vyhneme radeji uz zde.
     * 
     * @param string $key
     */
    public function setKey($key)
    {
        if ( strncasecmp($key, 'c', 1) === 0 ) {
            $key = 'x' . substr($key, 1);
        }

        $this->key = $key;
    }

    /**
     * @param int $count
     */
    public function setCount($count) {
        $this->count = $count;
    }

    /**
     * @return string
     */
    public function getTime() {
        return $this->time;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getText() {
        return $this->text;
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getIp() {
        return $this->ip;
    }

    /**
     * @return string
     */
    public function getReferer() {
        return $this->referer;
    }

    /**
     * @return array
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @return bool
     */
    public function getMerge() {
        return $this->merge;
    }

    /**
     * @return string
     */
    public function getStackTrace() {
        return $this->stackTrace;
    }

    /**
     * @return int
     */
    public function getCount() {
        return $this->count;
    }

    /**
     * @param array $data
     */
    public function setData($data) {
        $this->data = $data;
    }

    /**
     * Prida cas vyskytu zpravy (chyby).
     * 
     * @param string $time
     */
    public function addOccurence($time)
    {
        $this->times[] = $time;
        sort($this->times);
    }


    /**
     * @return array
     */
    public function getTimes()
    {
        return $this->times;
    }


    /**
     * @return array
     */
    public function getOccurences()
    {
        return $this->getTimes();
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param string $timeFormat
     * @return Message
     */
    public function setTimeFormat($timeFormat)
    {
        $this->timeFormat = $timeFormat;

        return $this;
    }
}
