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
     * @param string $time
     * @return $this
     */
    public function setTime($time)
    {
        $this->time = $time;
        $datetime = \DateTime::createFromFormat($this->timeFormat, $time);
        $this->timestamp = $datetime ? $datetime->getTimestamp() : time();

        return $this;
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
     * @return string
     */
    public function getStackTrace() {
        return $this->stackTrace;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }
}
