<?php

require __DIR__ . '/../vendor/autoload.php';

$fileName = $argv[1];
$configFileName = isset($argv[2]) ? $argv[2] : '';

$config = array();
if ($configFileName) {
	$config = json_decode(file_get_contents($configFileName), true);
}

$config['#YESTERDAY'] = date('Y/m/d', strtotime('yesterday'));
$config['#TODAY'] = date('Y/m/d');

$commands = array(
    'command' => function($text, Mustache_LambdaHelper $helper) use ($config) {
        $text = trim($text);
        if ($text[0] === ';') {
            return $helper->render(' -- Commented -- ');
        }
        $command = str_replace(array_keys($config), $config, $text);
        $output = array();
        exec($command, $output);
        return $helper->render(implode(PHP_EOL, $output));
    }
);

$m = new Mustache_Engine(array(
    'helpers' => $commands,
));
echo $m->render(file_get_contents($fileName), $config);
