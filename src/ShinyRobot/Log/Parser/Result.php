<?php
namespace ShinyRobot\Log\Parser;
use ShinyRobot\Log\Message;

/**
 * Vysledek zpracovani souboru s logem.
 */
class Result implements \Countable
{
    /**
     * @var string Cela cesta k souboru.
     */
    private $fileName;

    /**
     * @var string Typ logu, napr. "404" nebo "error".
     */
    private $type;

    /**
     * @see j3_Log_Parser_Abstract::parseMessages()
     * @var array
     */
    private $messages = array();


    /**
     * @param string $fileName Cela cesta k souboru.
     * @param string $type Typ logu, napr. "404" nebo "error".
     * @param array $messages {@link j3_Log_Parser_Abstract::parseMessages()}
     */
    public function  __construct($fileName, $type, array $messages)
    {
        $this->checkMessages($messages);
        $this->fileName = $fileName;
        $this->type = $type;
        $this->messages = $messages;
    }


    /**
     * @param array $errors
     * @throws \InvalidArgumentException Pokud nektera z polozek pole neni typu
     *  Message.
     */
    private function checkMessages(array $errors)
    {
        foreach ( $errors as $error ) {
            if ( ! ($error instanceof Message) ) {
                throw new \InvalidArgumentException('Zprava není typu Message.');
            }
        }
    }


    /**
     * Seradi vysledky od nejcasteji se vyskytujicich.
     */
    public function sortByCount()
    {
        usort($this->messages, array($this, 'callbackSortByCount'));

        return $this;
    }


    /**
     * @return int
     */
    public function count()
    {
        return count($this->messages);
    }


    public function callbackSortByCount(Message $a, Message $b)
    {
        if ( $a->getCount() == $b->getCount() ) {
            return 0;
        }

        return ( $a->getCount() < $b->getCount() ) ? 1 : -1;
    }


    public function getMessages()
    {
        return $this->messages;
    }


    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
