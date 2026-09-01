<?php

namespace App\Core\Entity;

use App\Core\Cache;

class EntityDefinitionFactory
{
    public static function create()
    {
        if (!file_exists(CACHE_DIR . '/entities.php')) {

            $definitions = include_once CONFIG_DIR . '/entities.php';
            if(file_put_contents(CACHE_DIR . '/entities.php', '<?php return ' . var_export($definitions, true) . ';') === false) {
                throw new \RuntimeException('Failed to write entity definitions to cache.');
            }
        }

        $definitions = include CACHE_DIR . '/entities.php';
        $entityDefinition = new EntityDefinition();
        foreach ($definitions as $name => $definition) {
            $entityDefinition->register($name, $definition);
        }
        return $entityDefinition;
    }
}