<?php
require __DIR__ . '/../src/autoload.php';

$opts = getopt('c:');

if (! isset($opts['c'])) {
    $msg = "Zadejte cestu ke konfiguracnimu ini souboru pres parametr -c\n";
    $msg .= "Napr.: php shiny-robot.phar -c=./config.ini\n";
    die($msg);
}

$config = parse_ini_file($opts['c']);

switch ($config['log_type']) {
    case 'gdi':
        $parser = new ShinyRobot\Log\Parser\GdiErrorLog($config['log_file']);
        break;

    case 'error':
        $parser = new ShinyRobot\Log\Parser\ErrorLog($config['log_file']);
        break;

    default:
        throw new \InvalidArgumentException("Neplatny typ logu '{$config['log_file']}''");
}

$url = rtrim($config['url'], '/');
$client = new \Redmine\Client($url, $config['api_key']);
$api = new \ShinyRobot\Api($config, $client);
$robot = new \ShinyRobot\Robot($api, new \ShinyRobot\Checker(), $config['limit_processed_messages']);
$robot->sendToRedmine($parser->parse());