<?php
namespace ShinyRobot;
use Redmine\Client;
use ShinyRobot\Log\Message;
use ShinyRobot\Log\Parser\Result;

class Robot
{
    /**
     * @var int Kolik maximalne zprav bude zpracovano v jednom behu. Zabrani zasilani tisicu zprav se stejnou chybou.
     */
    private $limitMessages;

    /**
     * @var Checker
     */
    private $checker;

    /**
     * @var Api
     */
    private $api;

    /**
     * @param Api $api
     * @param Checker $checker
     * @param int $limitMessages
     */
    public function __construct(Api $api, Checker $checker, $limitMessages)
    {
        $this->api = $api;
        $this->checker = $checker;
        $this->limitMessages = $limitMessages;
    }

    public function sendToRedmine(Result $logResult)
    {
        $processedMessages = 0;
        foreach ( $logResult->getMessages() as $error ) {

            if ( $this->checker->check($error) ) {
                if ($this->processError($error)) {
                    if (++$processedMessages >= $this->limitMessages) {
                        return true;
                    }
                }
            }
        }
    }

    /**
     * @param Message $error
     * @return bool Zda byla issue ulozena (pridana) ci ne
     */
    private function processError(Message $error)
    {
        $save = false;

        $issue = $this->api->findByKey($error->getKey());

        if ( $issue ) {
            // chyba uz je v Redminu

            if ( $issue->isClosed() ) {
                $issue->open();
                $save = true;
            }

            if ( $error->getCount() > $issue->getCount() ) {
                // zvedneme pocet vyskytu
                $issue->setCount($error->getCount());
                $save = true;
            }
        } else {
            // chyba zatim neni v Redminu

            $issue = $this->api->createIssue()
                ->setSubject($error->getText())
                ->setCount($error->getCount())
                ->setKey($error->getKey())
                ->setPriorityByString($error->getType())
                ->setDescription($error);
            $save = true;
        }

        if ( $save ) {
            $this->api->save($issue);
        }

        return $save;
    }
}
