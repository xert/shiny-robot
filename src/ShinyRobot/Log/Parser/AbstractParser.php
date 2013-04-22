<?php
namespace ShinyRobot\Log\Parser;

use ShinyRobot\Log\Message;

abstract class AbstractParser implements \Iterator
{
    /**
     * Cela cesta k logu.
     */
    protected $filePath;

    /**
     * Definuje potomek, pro rozliseni jendotlivych druhu vysledku.
     */
    protected $type = 'unknown';

    private $currentMessage = '';

    /**
     * @var resource File pointer na otevreny log soubor.
     */
    private $fp;

    /**
     * Dostane pole jednotlivych zprav (jako retezce) a prevede jej na strukturu.
     *
     * @param string $messageText Text chyby nacteny z log souboru.
     * @return Message
     * @xparam array $messages Pole s indexy:
     *  - time
     *  - type (Notice, Exception, 404, ...)
     *  - text (u 404 je to chybejici adresa)
     *  - key (unikatni klic zpravy, zpravy se shodnym klicem jsou slouceny)
     *  - data (pole ostatnich udaju)
     *  - merge (pokud je TRUE, jsou pole "data" retezena za sebe)
     */
    abstract protected function parseMessage($messageText);

    /**
     * @param string $line Radek z logu.
     * @return bool Zda se jedna o radek, kterym zacina chyba v logu.
     */
    abstract protected function isStartOfMessage($line);

    /**
     * @param string $filePath Cela cesta k logu
     * @throws \InvalidArgumentException
     */
    public function __construct($filePath)
    {
        if (! is_readable($filePath)) {
            throw new \InvalidArgumentException("Soubor '$filePath' nelze precist");
        }
        $this->checkXdebug();
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
     * Vrati vsechny zpravy z logu v poli.
     */
    public function getMessages()
    {
        $messages = array();
        foreach ($this as $message) {
            $messages[] = $message;
        }

        return $messages;
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
     * @return resource
     */
    protected function getFilePointer()
    {
        if (! $this->fp) {
            $this->fp = $this->openFile();
        }

        return $this->fp;
    }

    /**
     * @return resource
     * @throws \RuntimeException Pokud se soubor nepodari otevrit.
     */
    private function openFile()
    {
        if ( ! ($fp = fopen($this->filePath, 'r')) ) {
            throw new \RuntimeException("'{$this->filePath}' nelze otevrit.");
        }

        return $fp;
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return Message
     */
    public function current()
    {
        $this->currentMessage = '';
        $messageText = '';
        $fp = $this->getFilePointer();

        while (! feof($fp)) {
            $line = fgets($fp, 4096);
            if ( $this->isStartOfMessage($line) && ! empty($messageText) ) {
                fseek($fp, -1 * strlen($line), SEEK_CUR);
                return $this->parseMessage($messageText);
            }
            $messageText .= $line;
        }

        return $this->parseMessage($messageText);
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {

    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        ftell($this->getFilePointer());
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        $fp = $this->getFilePointer();
        if (feof($fp)) {
            return false;
        }

        $char = fgetc($fp);
        fseek($fp, -1, SEEK_CUR);

        return $char !== false;
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        rewind($this->getFilePointer());
    }
}
