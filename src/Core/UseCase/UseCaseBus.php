<?php
namespace App\Core\UseCase;

use App\Core\Di\ContainerAwareInterface;
use App\Core\Di\ContainerAwareTrait;
use App\Core\Di\Locator;

class UseCaseBus implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private $locator;

    public function execute($useCaseClass, $request)
    {
        if (!$this->locator) {
            $this->locator = new Locator('use-case');
            $this->locator->setContainer($this->container);
        }
        
        $useCase = $this->locator->get($useCaseClass);
        if (!$useCase) {
            throw new \Exception("Use case $useCaseClass not found");
        }
        return $useCase->execute($request);
    }

}