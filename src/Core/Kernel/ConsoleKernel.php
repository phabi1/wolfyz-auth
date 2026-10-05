<?php

namespace App\Core\Kernel;

use App\Core\Kernel\Console\ArgsParser;

class ConsoleKernel extends Base
{

    public function run()
    {
        global $argv;

        $this->bootstrap();

        $container = $this->getContainer();

        $argsParser = new ArgsParser();
        $input = $argsParser->parse($argv ?? []);

        $commandName = $input->arguments->get('command');
        $serviceIds = $this->getContainer()->findByTag('command');

        if ($input->options->has('help') || $input->options->has('h')) {
            $commandName = 'help';
        }

        $serviceId = null;
        foreach ($serviceIds as $id) {
            $def = $container->getDefinition($id);
            foreach ($def['tags'] as $tag) {
                if ($tag['name'] === 'command' && $tag['command'] === $commandName) {
                    $serviceId = $id;
                    break 2;
                }
            }
        }

        if ($serviceId === null) {
            throw new \RuntimeException("Command not found: " . $commandName);
        }

        $command = $container->get($serviceId);
        if ($command === null) {
            throw new \RuntimeException("Command not found: " . $commandName);
        }

        if ($command instanceof \App\Core\Command\CommandInterface === false) {
            throw new \RuntimeException("Command does not implement CommandInterface: " . $commandName);
        }
        $exitCode = $command->execute($input);
        exit($exitCode);
    }
}