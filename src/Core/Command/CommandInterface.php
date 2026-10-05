<?php

namespace App\Core\Command;

use App\Core\Command\Output;
use App\Core\Command\Input;

interface CommandInterface
{
    public function execute(Input $input): int;

    public static function getName(): string;

    public static function getDescription(): string;

    public static function getArguments(): array;

    public static function getOptions(): array;
}