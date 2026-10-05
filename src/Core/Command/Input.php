<?php
namespace App\Core\Command;

use App\Core\Lib\Bag;

class Input
{
    public readonly Bag $arguments;
    public readonly Bag $options;

    public function __construct(array $arguments, array $options)
    {
        $this->arguments = new Bag();
        foreach ($arguments as $key => $value) {
            $this->arguments->set($key, $value);
        }
        $this->options = new Bag();
        foreach ($options as $key => $value) {
            $this->options->set($key, $value);
        }
    }
}