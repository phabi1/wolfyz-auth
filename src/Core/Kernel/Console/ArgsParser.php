<?php

namespace App\Core\Kernel\Console;

use App\Core\Command\Input;
class ArgsParser
{
    public function parse(array $args): Input
    {
        $options = [];
        $arguments = [];

        array_shift($args); // Remove the script name from the arguments
        $command = array_shift($args);

        $arguments['command'] = $command;

        $count = count($args);
        for ($i = 0; $i < $count; $i++) {
            $arg = $args[$i];
            // Options longue (e.g., --env=prod ou --verbose)
            if (str_starts_with($arg, '--')) {
                $option = substr($arg, 2);
                if (str_contains($option, '=')) {
                    [$key, $value] = explode('=', $option, 2);
                    $options[$key] = $value;
                } else if (isset($args[$i + 1]) && !str_starts_with($args[$i + 1], '-')) {
                    $options[$option] = $args[$i + 1];
                    $i++; // Skip the next argument as it is the value for the current option
                } else {
                    $options[$option] = true;
                }
                continue;
            }

            // Option courte (e.g., -u value or -v)
            if (str_starts_with($arg, '-')) {
                $option = substr($arg, 1);
                if (isset($args[$i + 1]) && !str_starts_with($args[$i + 1], '-')) {
                    $options[$option] = $args[$i + 1];
                    $i++; // Skip the next argument as it is the value for the current option
                } else {
                    $options[$option] = true;
                }
                continue;
            }

            // Argument (non-option)
            $arguments[] = $arg;
        }
        return new Input($arguments, $options);
    }
}