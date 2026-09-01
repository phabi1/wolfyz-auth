<?php
namespace App\Core\UseCase;

interface UseCaseInterface
{
    public function execute(array $params = []);
}