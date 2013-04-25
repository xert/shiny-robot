<?php
namespace ShinyRobot;
use Redmine\Client;
use ShinyRobot\Log\Message;
use ShinyRobot\Log\Parser\AbstractParser;

class Robot
{
    /**
     * @var int Kolik maximalne zprav bude zaslano do Redmine v jednom behu. Zabrani zasilani tisicu zprav se stejnou chybou.
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

    public function sendToRedmine(AbstractParser $parser)
    {
        $processedMessages = 0;
        foreach ($parser as $error) {

            if ($this->checker->check($error)) {
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
            // chyba uz je v Redmine

            if ($error->getTimestamp() > $issue->getLastTimestamp()) {
                // chyba se vyskytla znovu a novy vyskyt jeste nebyl zalogovan v Redmine

                $issue->setLastTimestamp($error->getTimestamp());
                $issue->setCount($issue->getCount() + 1);
                if ( $issue->isClosed() ) {
                    $issue->open();
                }

                $save = true;
            }

        } else {
            // chyba zatim neni v Redminu

            $issue = $this->api->createIssue()
                ->setSubject($error->getText())
                ->setCount(1)
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
