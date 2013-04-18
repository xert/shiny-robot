<?php
namespace ShinyRobot\Log\Parser;

use ShinyRobot\Log\Message;

abstract class AbstractParser
{
    /**
     * Cela cesta k logu.
     */
    protected $filePath;

    /**
     * Definuje potomek, pro rozliseni jendotlivych druhu vysledku.
     */
    protected $type = 'unknown';


    /**
     * Nacte zpravy z logu do pole jako retezce.
     */
    abstract protected function getMessages();


    /**
     * Dostane pole jednotlivych zprav (jako retezce) a prevede jej na strukturu.
     *
     * @param array $messages Pole s indexy:
     *  - time
     *  - type (Notice, Exception, 404, ...)
     *  - text (u 404 je to chybejici adresa)
     *  - key (unikatni klic zpravy, zpravy se shodnym klicem jsou slouceny)
     *  - data (pole ostatnich udaju)
     *  - merge (pokud je TRUE, jsou pole "data" retezena za sebe)
     */
    abstract protected function parseMessages(array $messages);
    
    /**
     * @param string $filePath Cela cesta k logu
     */
    public function __construct($filePath)
    {
        if (! is_readable($filePath)) {
            throw new \InvalidArgumentException("Soubor '$filePath' nelze precist");
        }
        $this->filePath = $filePath;
    }

    /**
     * @param string $filePath
     * @return ErrorLog
     */
    public static function createErrorLogParser($filePath)
    {
        return new ErrorLog($filePath);
    }

    /**
     * @param string $filePath
     * @return GdiErrorLog
     */
    public static function createGdiErrorLogParser($filePath)
    {
        return new GdiErrorLog($filePath);
    }

    /**
     * @return Result
     */
    public function parse()
    {
        $this->checkXdebug();

        $this->turnLogging(false);
        $messages = $this->getMessages();
        $this->turnLogging(true);

        if ( empty($messages) ) {
            $sorted = array();
        } else {
            $parsed = $this->parseMessages($messages);
            $sorted = $this->sortMessages($parsed);
        }

        $result = new Result($this->filePath, $this->type, $sorted);

        return $result;
    }


    /**
     * Usporada zpravy tak, aby byly od nejnovejsi po nejstarsi. Pokud se nektera
     * zprava vyskytuje vicekrat (2 zpravy maji shodny klic), slouci je do jedne.
     *
     * @param array $messages
     * @return array
     */
    final private function sortMessages(array $messages)
    {
        // seradime podle klicu
        usort($messages, array($this, 'callbackCompareByKey'));
        
        // stejne chyby jsou ted sousede, sloucime stejne
        // TODO: extrahovat do metody
        $sorted = array(reset($messages));
        $lastSortedIndex = 0;
        $count = count($messages);
        for ( $i = 1; $i < $count; $i++ ) {

            if ( $sorted[$lastSortedIndex]->getKey() == $messages[$i]->getKey() ) {
                // stejny klic, sloucime dohromady
                $sorted[$lastSortedIndex]->addOccurence($messages[$i]->getTime());
                // pokud existuje klic "merge", data se zretezi
                if ( $messages[$i]->getMerge() ) {
                    $data = $sorted[$lastSortedIndex]->getData();
                    $data[] = $messages[$i]->getData();
                    $sorted[$lastSortedIndex]->setData($data);
                }
            } else {
                // nova chyba
                if ( $messages[$i]->getMerge() ) {
                    // data retezime, musime tedy prevest na pole
                    $messages[$i]->setData(array($messages[$i]->getData())); 
                }
                $sorted[] = $messages[$i];
                $lastSortedIndex++;
            }
        }


        // pod index "time" predame cas prvniho vyskytu a pocet vyskytu
        foreach ( $sorted as $key => $value ) {
            $occurences = $sorted[$key]->getOccurences();
            if ( ! empty($occurences) ) {
                $sorted[$key]->setTime(reset($occurences));
                $sorted[$key]->setCount(count($occurences));
            }
        }

        // seradime podle casu
        usort($sorted, array($this, 'callbackCompareByTimes'));

        return $sorted;
    }


    /**
     * Pokud je aktivni Xdebug, vyhod vyjimku. Logy maji totiz jine formaty.
     *
     * @throws \RuntimeException Pokud je aktivni Xdebug
     */
    final private function checkXdebug()
    {
        if ( function_exists('xdebug_enabled') ) {
            throw new \RuntimeException('Xdebug je aktivní, logy jsou v jiném formátu.');
        }
    }

    
    /**
     * Vypnuti/zapnuti logovani chyb.
     * Pri behu analyzatoru se nesmi logovat zadna chyba, protoze pri analyze 
     * ziveho logu se tak muze vytvorit nekonecna smycka, kdy jsou do souboru 
     * pridavany dalsi a dalsi chyby.
     *
     * @param bool $flag 
     */
    private function turnLogging($flag)
    {
        ini_set('log_errors', (bool) $flag);
    }


    /**
     * @see Message::compareByKey
     * @param Message $a
     * @param Message $b
     * @return int
     */
    public function callbackCompareByKey(Message $a, Message $b)
    {
        return $a->compareByKey($b);
    }


    /**
     * Seradi zpravy od nejnovejsi po nejstarsi.
     *
     * @see Message::compareByTime
     * @param array $a
     * @param array $b
     * @return int
     */
    public function callbackCompareByTimes(Message $a, Message $b)
    {
        return $a->compareByTime($b);
    }


    /**
     * @return resource
     * @throws \RuntimeException Pokud se soubor nepodari otevrit.
     */
    protected function getFilePointer()
    {
        if ( ! ($fp = fopen($this->filePath, 'r')) ) {
            throw new \RuntimeException("'{$this->filePath}' nelze otevrit.");
        }

        return $fp;
    }
}
