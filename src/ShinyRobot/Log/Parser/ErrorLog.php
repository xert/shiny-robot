<?php
namespace ShinyRobot\Log\Parser;
use ShinyRobot\Log\Message;

/**
 * Puvodni error log, do ktereho zapisuje PHP a vyjimky loguje error controller.
 */
class ErrorLog extends AbstractParser
{
    protected $type = 'error';

    /**
     * @see j3_Log_Parser_Abstract::getMessages()
     * @return array
     */
    protected function getMessages()
    {
        $messages = array();
        $message = '';

        $fp = $this->getFilePointer();
        while ( ! feof($fp) ) {
            $line = fgets($fp, 4096);
            if ( strncasecmp($line, '[', 1) === 0 && ! empty($message) ) {
                $messages[] = $message;
                $message = '';
            }
            $message .= $line;
        }
        if ( ! empty($message) ) {
            $messages[] = $message;
        }

        return $messages;
    }

    
    /**
     * @see j3_Log_Parser_Abstract::getMessages()
     * @param array $messages
     * @return array
     */
    protected function parseMessages(array $messages)
    {
        //v kazde zprave odstranim "\r", takze i ve windows budou konce radku "\n"
        $lineSeparator = "\n";

        $parsed = array();
        foreach ($messages as $message ) {
            $message = str_replace("\r", "", $message);
            $time = substr($message, 1, 20);
            if ( substr(strtolower($message), 27, 9) == 'exception' ) {
                $type = 'Exception';
                $lines = explode($lineSeparator, $message);

                // 1. radek
                if ( strpos($lines[0], "Referer") !== false ) {
                    // [14-Apr-2010 13:15:51 UTC] Exception: http://xxx.cz	Referer: http://xxx2.cz ...
                    $fstLineParts = explode("Referer", $lines[0]);
                    $url = trim(substr($fstLineParts[0], 38), ",\t ");
                    $referer = trim($fstLineParts[1], ":\t ");
                    array_shift($lines);
                } else {
                    // [14-Apr-2010 12:42:34 UTC] exception 'RuntimeException' with message 'Token ...
                    $url = $referer = null;
                    $lines[0] = substr($lines[0], 27);
                }

                //text vyjimky je do te doby, dokud se nevyskytne text "stack trace"
                $text = array();
                do {
                    $line = array_shift($lines);
                    $text[] = $line;
                } while ($line !== null && $line != "Stack trace:");
                array_pop($text); //odstranime slova "Stack trace:"
                $text = implode(' ', $text);

                //odstranime slovo "exception "
                $text = substr($text, 10);

                // STACK TRACE

                // posledni prazdny radek
                $lastLine = end($lines);
                if (empty($lastLine)) {
                    array_pop($lines);
                }

                // posledni radek "-------"
                $lastLine = end($lines);
                if (strncasecmp($lastLine, '---', 3) === 0) {
                    array_pop($lines);
                }
                $trace = implode($lineSeparator, $lines);

                $key = md5($type . $text . $trace);

            } else {
                // obycejna chyba
                if ( substr($message, 23, 4) == 'PHP ' ) {
                    // [13-Apr-2010 12:25:41] PHP Notice:  Undefined offset:  2 in /www/ ...
                    $typeLength = strpos($message, ':', 23) - 23;
                    $type = substr($message, 23 + 4, $typeLength - 4); // odstrihneme "PHP"
                    $text = substr($message, 23 + $typeLength + 3);
                } else {
                    // bez typu, napr. [13-Apr-2010 12:26:36] Nepodporovany prohlizec: Mozilla/4.0 ...
                    $type = 'Custom';
                    $text = substr($message, 23);
                }
                $key  = md5($type . $text);
                $data = null;
                $trace = null;
                $url = null;
                $referer = null;
            }

            $message = new Message(array(
                'time' => $time,
                'type' => $type,
                'text' => $text,
                'key'  => $key,
                'stackTrace' => $trace,
                'url' => $url,
                'referer' => $referer,
            ));
            $parsed[] = $message;
        }
        
        return $parsed;
    }
}
