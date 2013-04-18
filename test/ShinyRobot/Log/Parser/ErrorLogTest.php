<?php
namespace ShinyRobot\Log\Parser;
use ShinyRobot\Log\Message;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;

/**
 * Soubor s tetovacimi daty obsahuje:
 * - 1 x notice
 * - 2 x stejny  warning
 * - 1 x unkatni warning (stejny jako predchozi 2, jen se vyskytl na jinem miste)
 * - 1 x unikatni exception
 * - 2 x stejnou exception
 * - 1 x unikatni exception (stejna, jako predchozi 2, jen jiny backtrace)
 * - 1 x unikatni excepton s pribalenou puvodni vyjimkou
 * - 1 x unikatni vyjimku s CRLF znakem
 */
class ErrorLogTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Result
     */
    protected $result;

    /**
     * @var string Soubor s logem (v pameti).
     */
    private static $logFilePath = 'var://log_file';

    public static function setUpBeforeClass()
    {
        $fixturePath = __DIR__ . '/_files/error.log';

        vfsStream::setup('dir');
        self::$logFilePath = vfsStream::url('dir/log.txt');

        $content = file_get_contents($fixturePath);
        // natvrdo pridame Windows konec radku
        $content = str_replace('%%CRLF%%', "\r\n", $content);
        file_put_contents(self::$logFilePath, $content);
    }

    protected function setUp()
    {
        $parser = AbstractParser::createErrorLogParser(self::$logFilePath);
        $this->result = $parser->parse();
    }

    public function testCorrectNumberOfMessages()
    {
        $this->assertEquals(8, count($this->result->getMessages()));
    }

    public function testResultContainsErrorObjects()
    {
        $this->assertGreaterThan(1, count($this->result->getMessages()));
        foreach ( $this->result->getMessages() as $error ) {
            $this->assertInstanceOf('ShinyRobot\Log\Message', $error);
        }
    }

    public function testCorrectSorting()
    {
        $data = $this->result->getMessages();

        $count = count($data);
        for ( $i = 1; $i < $count; $i++ ) {
            $this->assertGreaterThanOrEqual($data[$i]->getTimestamp(), $data[$i-1]->getTimestamp());
        }
    }

    /**
     * Chovani, kdyz je log prazdny.
     */
    public function testEmptyLog()
    {
        $filePath = realpath(dirname(__FILE__) . '/_files/empty.log');
        $parser = AbstractParser::createErrorLogParser($filePath);
        $result = $parser->parse();
        $this->assertEquals(0, $result->count());
    }

    public function testWrongFilenameThrowsException()
    {
        $this->setExpectedException('InvalidArgumentException');
        $filePath = '_nonexistingfilepath_';
        $parser = AbstractParser::createErrorLogParser($filePath);
        $parser->parse();
    }

    public function testCorrectParsingOfJolError()
    {
        $messages = $this->result->getMessages();
        $jolMessage = reset($messages);

        $this->assertEquals(
            'http://portal.staging.tlumoceni-preklady.cz/servis/clear-titulka-text/token/xxx/',
            $jolMessage->getUrl()
        );
        $this->assertEquals('unknown', $jolMessage->getReferer());
        $this->assertEquals(
            "'Zend_View_Exception' with message 'script 'servis/clear-titulka-text.phtml' not found in path (/www/portal/tlumoceni-preklady.cz/modules/default/views/scripts/)' in /www/lib/Zend/View/Abstract.php:924",
            $jolMessage->getText()
        );
        $stackTrace = <<<ST
#0 /www/lib/Zend/Http/Client.php(261): Zend_Uri::factory('servis/clear-ti...')
#1 /www/lib/Zend/Http/Client.php(244): Zend_Http_Client->setUri('servis/clear-ti...')
#2 /www/webkurzor/modules/firma/controllers/ObrazekController.php(868): Zend_Http_Client->__construct('servis/clear-ti...')
#3 /www/webkurzor/modules/firma/controllers/ObrazekController.php(246): Firma_ObrazekController->clearCachedTitulkaText()
#4 /www/lib/Zend/Controller/Action.php(513): Firma_ObrazekController->titulkaTextAction()
#5 /www/lib/Zend/Controller/Dispatcher/Standard.php(289): Zend_Controller_Action->dispatch('titulkaTextActi...')
#6 /www/lib/Zend/Controller/Front.php(946): Zend_Controller_Dispatcher_Standard->dispatch(Object(Zend_Controller_Request_Http), Object(Zend_Controller_Response_Http))
#7 /www/lib/j3/App.php(157): Zend_Controller_Front->dispatch()
#8 /www/webkurzor/htdocs/index.php(5): j3_App->run()
#9 {main}
ST;
        $stackTrace = $this->normalizeLineSeparators($stackTrace);
        $this->assertEquals($stackTrace, $jolMessage->getStackTrace());
    }

    public function testCorrectParsingOfTpError()
    {
        $messages = $this->result->getMessages();
        /** @var $tpMessage Message */
        $tpMessage = $messages[6];

        $this->assertEquals(
            'http://www.francouzstina-on-line.cz/jazykove-zkousky/',
            $tpMessage->getUrl()
        );
        $this->assertEquals('unknown', $tpMessage->getReferer());
        $this->assertEquals(
            "'PDOException' with message 'SQLSTATE[HY000] [2013] Lost connection to MySQL server at 'reading initial communication packet', system error: 61' in /data/www/production/gdi/lib/Zend/Db/Adapter/Pdo/Abstract.php:129",
            $tpMessage->getText()
        );

        $stackTrace = <<<ST
#0 /data/www/production/gdi/lib/Zend/Db/Adapter/Pdo/Abstract.php(129): PDO->__construct('mysql:host=10.0...', 'j2-read', '3QAKzPRS8j3MKv4...', Array)
#1 /data/www/production/gdi/lib/Zend/Db/Adapter/Pdo/Mysql.php(109): Zend_Db_Adapter_Pdo_Abstract->_connect()
#2 /data/www/production/gdi/lib/Zend/Db/Adapter/Abstract.php(459): Zend_Db_Adapter_Pdo_Mysql->_connect()
#3 /data/www/production/gdi/lib/Zend/Db/Adapter/Pdo/Abstract.php(238): Zend_Db_Adapter_Abstract->query('SELECT z.zkratk...', Array)
#4 /data/www/production/gdi/lib/Zend/Db/Adapter/Abstract.php(734): Zend_Db_Adapter_Pdo_Abstract->query('SELECT z.zkratk...', Array)
#5 /data/www/production/gdi/apps/jol/controllers/ZkouskyController.php(157): Zend_Db_Adapter_Abstract->fetchAll('SELECT z.zkratk...')
#6 /data/www/production/gdi/apps/jol/controllers/ZkouskyController.php(41): ZkouskyController->doSearch('vsechny', false)
#7 /data/www/production/gdi/lib/Zend/Controller/Action.php(513): ZkouskyController->indexAction()
#8 /data/www/production/gdi/lib/Zend/Controller/Dispatcher/Standard.php(295): Zend_Controller_Action->dispatch('indexAction')
#9 /data/www/production/gdi/lib/Zend/Controller/Front.php(954): Zend_Controller_Dispatcher_Standard->dispatch(Object(Zend_Controller_Request_Http), Object(Zend_Controller_Response_Http))
#10 /data/www/production/gdi/lib/jol/App.php(90): Zend_Controller_Front->dispatch()
#11 /data/www/production/gdi/public/jol/francouzstina-on-line.cz/index.php(15): jol_App->run()
#12 {main}

Next exception 'Zend_Db_Adapter_Exception' with message 'SQLSTATE[HY000] [2013] Lost connection to MySQL server at 'reading initial communication packet', system error: 61' in /data/www/production/gdi/lib/Zend/Db/Adapter/Pdo/Abstract.php:144
Stack trace:
#0 /data/www/production/gdi/lib/Zend/Db/Adapter/Pdo/Mysql.php(109): Zend_Db_Adapter_Pdo_Abstract->_connect()
#1 /data/www/production/gdi/lib/Zend/Db/Adapter/Abstract.php(459): Zend_Db_Adapter_Pdo_Mysql->_connect()
#2 /data/www/production/gdi/lib/Zend/Db/Adapter/Pdo/Abstract.php(238): Zend_Db_Adapter_Abstract->query('SELECT z.zkratk...', Array)
#3 /data/www/production/gdi/lib/Zend/Db/Adapter/Abstract.php(734): Zend_Db_Adapter_Pdo_Abstract->query('SELECT z.zkratk...', Array)
#4 /data/www/production/gdi/apps/jol/controllers/ZkouskyController.php(157): Zend_Db_Adapter_Abstract->fetchAll('SELECT z.zkratk...')
#5 /data/www/production/gdi/apps/jol/controllers/ZkouskyController.php(41): ZkouskyController->doSearch('vsechny', false)
#6 /data/www/production/gdi/lib/Zend/Controller/Action.php(513): ZkouskyController->indexAction()
#7 /data/www/production/gdi/lib/Zend/Controller/Dispatcher/Standard.php(295): Zend_Controller_Action->dispatch('indexAction')
#8 /data/www/production/gdi/lib/Zend/Controller/Front.php(954): Zend_Controller_Dispatcher_Standard->dispatch(Object(Zend_Controller_Request_Http), Object(Zend_Controller_Response_Http))
#9 /data/www/production/gdi/lib/jol/App.php(90): Zend_Controller_Front->dispatch()
#10 /data/www/production/gdi/public/jol/francouzstina-on-line.cz/index.php(15): jol_App->run()
#11 {main}
ST;

        $stackTrace = $this->normalizeLineSeparators($stackTrace);
        $this->assertEquals($stackTrace, $tpMessage->getStackTrace());
    }
    
    public function testWindowsLineEnd()
    {
        $loggedText = file_get_contents(self::$logFilePath);
        $this->assertContains("\r\n", $loggedText);

        //drive tento test spadnul, kvuli nejednoznacnosti konce radku (pokud byl soubor ukoncovan UNIX like, a obsahoval
        //byt jediny WIN line ending, vyhodnotilo se to tak, ze cely soubor ma WIN line ending
        $messages = $this->result->getMessages();
        $message  = $messages[7]; //zprava je rozdelena znakem CRLF
        $text = "'Zend_Mail_Protocol_Exception' with message '4.1.8 <rgxucr@noqbtz.com>: Sender address rejected: Domain not found  ' in /data/www/production/gdi/lib/Zend/Mail/Protocol/Abstract.php:431";
        $this->assertEquals($text, $message->getText());

        $stackTrace = <<<ST
#0 /data/www/production/gdi/lib/Zend/Mail/Protocol/Smtp.php(289): Zend_Mail_Protocol_Abstract->_expect(Array, 300)
#1 /data/www/production/gdi/lib/Zend/Mail/Transport/Smtp.php(211): Zend_Mail_Protocol_Smtp->rcpt('info@informatio...')
#2 /data/www/production/gdi/lib/Zend/Mail/Transport/Abstract.php(348): Zend_Mail_Transport_Smtp->_sendMail()
#3 /data/www/production/gdi/lib/Zend/Mail.php(1194): Zend_Mail_Transport_Abstract->send(Object(Zend_Mail))
#4 /data/www/production/gdi/apps/nazkusenou/agentura.php(544): Zend_Mail->send()
#5 /data/www/production/gdi/apps/nazkusenou/agentura.php(198): AgenturaModel->emailForm(Object(AgenturaForm), 'http://www.nazk...')
#6 {main}
ST;
        $stackTrace = $this->normalizeLineSeparators($stackTrace);
        $this->assertEquals($stackTrace, $message->getStackTrace());
    }

    /**
     * @param string $text
     * @return string
     */
    private function normalizeLineSeparators($text)
    {
        return str_replace("\r", "", $text);
    }
}
