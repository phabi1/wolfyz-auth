<?php
namespace App\Oidc\Command;

use App\Core\Command\CommandInterface;
use App\Core\Command\Input;
use App\Core\UseCase\UseCaseBus;

class CreateClientCommand implements CommandInterface
{
    private UseCaseBus $useCaseBus;

    public function __construct(UseCaseBus $useCaseBus)
    {
        $this->useCaseBus = $useCaseBus;
    }

    public function execute(Input $input): int
    {
        $client_id = $input->arguments->get(0);

        $this->useCaseBus->execute('oidc.create-client', [
            'client_id' => $client_id,
        ]);
        // Implement the logic to create an OIDC client here
        return 0;
    }

    public static function getName(): string
    {
        return 'oidc:create-client';
    }

    public static function getDescription(): string
    {
        return 'Creates a new OIDC client';
    }

    public static function getArguments(): array
    {
        return [
            'use-case-bus' => 'The use case bus instance',
        ];
    }

    public static function getOptions(): array
    {
        return [
            ['name' => 'command', 'command' => 'oidc:create-client'],
        ];
    }
}