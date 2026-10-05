<?php

namespace App\Core\Mvc\Controller\Helper;

use App\Core\UseCase\UseCaseBus as UseCaseBusService;

class UseCaseBus
{

    private UseCaseBusService $useCaseBus;

    public function __construct(UseCaseBusService $useCaseBus)
    {
        $this->useCaseBus = $useCaseBus;
    }

    public function __invoke($useCase, array $data = [])
    {
        return $this->execute($useCase, $data);
    }

    public function execute($useCase, array $data = [])
    {
        return $this->useCaseBus->execute($useCase, $data);
    }
}