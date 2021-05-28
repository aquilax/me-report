<?php

require __DIR__ . '/../vendor/autoload.php';

const CACHE_DIR = '#CACHEDIR';

$fileName = $argv[1];
$configFileName = isset($argv[2]) ? $argv[2] : '';

$config = array();
if ($configFileName) {
	$config = json_decode(file_get_contents($configFileName), true);
}

$config['#YESTERDAY'] = date('Y/m/d', strtotime('yesterday'));
$config['#TODAY'] = date('Y/m/d');

function getCommandCached($config, $rawCacheTTL = 0) {
    $cacheTTL = (!isset($config[CACHE_DIR]) || !is_readable($config[CACHE_DIR])) ? 0 : $rawCacheTTL;

    return function($text, Mustache_LambdaHelper $helper) use ($config, $cacheTTL) {
        $startTime = microtime(true);
        $text = trim($text);
        if ($text[0] === ';') {
            return $helper->render(' -- Commented -- ');
        }
        $output = array();
        $command = str_replace(array_keys($config), $config, $text);
        $key = md5($command);

        if ($cacheTTL) {
            $fileName = $config[CACHE_DIR] . '/me-report-' . $key;
            if (is_writable($fileName)) {
                $time = filemtime($fileName);
                if ($time && time() - $time <= $cacheTTL) {
                    $endTime = microtime(true);
                    $diff = $endTime - $startTime;
                    $output[] = file_get_contents($fileName);
                    $output[] = "<!-- loading from cache took $diff seconds -->";
                    $output[] = "";
                    return $helper->render(implode(PHP_EOL, $output));
                }
            }
        }

        exec($command, $output);

        $endTime = microtime(true);
        $diff = $endTime - $startTime;

        $output[] = "<!-- $command -->";
        $output[] = "<!-- took $diff seconds -->";

        if ($cacheTTL) {
            $fileName = $config[CACHE_DIR] . '/me-report-' . $key;
            $output[] = "<!-- cached to $fileName -->";
            file_put_contents($fileName, implode(PHP_EOL, $output));
        }

        $output[] = "";

        return $helper->render(implode(PHP_EOL, $output));
    };
}


$commands = array(
    'command' => getCommandCached($config),
    'command_cached_1h' => getCommandCached($config, 3600),
    'command_cached_3h' => getCommandCached($config, 10800),
    'command_cached_6h' => getCommandCached($config, 21600),
    'command_cached_12h' => getCommandCached($config, 43200),
    'command_cached_24h' => getCommandCached($config, 86400),
);

$m = new Mustache_Engine(array(
    'helpers' => $commands,
));
echo $m->render(file_get_contents($fileName), $config);
