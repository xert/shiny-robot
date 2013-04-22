<?php
namespace ShinyRobot\Log\Parser;
/**
 * Soubor s tetovacimi daty obsahuje:
 * - 1 x notice
 * - 2 x stejny  warning
 * - 1 x stejny  warning jako predchozi, jen ma jiny stack trace a URL
 * - 1 x unkatni warning (stejny jako predchozi 2, jen se vyskytl na jinem miste)
 * - 2 x stejnou exception
 * - 1 x unikatni exception s dalsi zanorenou v sobe
 * - 1 x parse error
 * - 1 x fatal error
 * - 1 x user error
 * - 1 x user warning
 * - 1 x user notice
 * - 1 x user deprecated error
 * - 1 x strict error
 * - 1 x recoverable error
 * - 1 x deprecated error
 */
class GdiErrorLogTest extends \PHPUnit_Framework_TestCase
{
/**
     * @var ErrorLog
     */
    private $parser;

    protected function setUp()
    {
        $filePath = realpath(dirname(__FILE__) . '/_files/gdierror.log');
        $this->parser = AbstractParser::createGdiErrorLogParser($filePath);
    }

    public function testCorrectNumberOfMessages()
    {
        $this->assertEquals(18, count($this->parser->getMessages()));
    }

    public function testEmptyLogfile()
    {
        $filePath = realpath(dirname(__FILE__) . '/_files/empty.log');
        $parser = AbstractParser::createGdiErrorLogParser($filePath);
        $this->assertEquals(0, count($parser->getMessages()));
    }
}
