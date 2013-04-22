<?php
namespace ShinyRobot\Log\Parser;
use ShinyRobot\Log\Message;

/**
 * Parser noveho zpusobu logovani pomoci j3_Error_Logger.
 */
class GdiErrorLog extends AbstractParser
{
    /**
     * Oddelovac jednotlivych zaznamu v logu.
     */
    const RECORDS_SEPARATOR = "----------";

    protected $type = 'error';

    protected function isStartOfMessage($line)
    {
        return strncasecmp($line, '[', 1) === 0;
    }

    protected function parseMessage($messageText)
    {
        // hvezdickou jsou oznaceny zpracovavane useky

        $lines = explode(PHP_EOL, $messageText);

        // [*17-Aug-2010 13:57:24*] PHP Notice: Undefined variable...
        $time = substr($lines[0], 1, 20);
        $data = array();

        // [17-Aug-2010 13:57:24] PHP Notice*:* Undefined variable...
        $typeEndPos = strpos($lines[0], ':', 27);
        // [17-Aug-2010 13:57:24] PHP *Notice*: Undefined variable...
        $type = substr($lines[0], 27, $typeEndPos - 27);
        // [17-Aug-2010 13:57:24] PHP Notice: *Undefined variable...*
        $text = substr($lines[0], $typeEndPos + 2);

        // $lines[1] == 'Stack trace:'
        $stackTrace = '';
        $i = 2;
        do {
            $stackTrace .= $lines[$i] . PHP_EOL;
            $i++;
        } while ( strncasecmp('Url:', $lines[$i], 4) !== 0 );

        // Url: http://localhost/tlumoceni-preklady.cz/portal/tlumoceni-preklady.cz/htdocs/	Referer: unknown
        list($url, $referer, $ip) = explode("\t", $lines[$i]);
        $url = substr($url, 5);
        $referer = substr($referer, 9);
        $ip = substr($ip, 4);
        $key = md5($type . $text);

        return new Message(array(
            'time' => $time,
            'type' => $type,
            'text' => $text,
            'key'  => $key,
            'stackTrace' => $stackTrace,
            'url' => $url,
            'referer' => $referer,
            'ip' => $ip
        ));
    }
}
