<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Command;

use Dranzd\Common\Cqrs\Domain\Message\AbstractCommand;
use Dranzd\StorebunkAccounting\Domain\Accounting\Account\Type;

/**
 * Create Account Command
 *
 * Command to create a new account in the chart of accounts.
 *
 * @package Dranzd\StorebunkAccounting\Application\Command
 */
final class CreateAccountCommand extends AbstractCommand
{
    public const MESSAGE_NAME = 'storebunk.accounting.command.create_account';

    private string $accountId;
    private string $name;
    private Type $type;

    private function __construct(
        string $messageUuid,
        string $messageName,
        array $payload
    ) {
        parent::__construct($messageUuid, $messageName, $payload);
    }

    public static function create(
        string $accountId,
        string $name,
        Type $type
    ): self {
        $command = new self(
            '',
            self::MESSAGE_NAME,
            [
                'account_id' => $accountId,
                'name' => $name,
                'type' => $type->value,
            ]
        );

        $command->accountId = $accountId;
        $command->name = $name;
        $command->type = $type;

        return $command;
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getPayload(): array
    {
        return [
            'account_id' => $this->accountId,
            'name' => $this->name,
            'type' => $this->type->value,
        ];
    }

    protected function setPayload(array $payload): void
    {
        $this->accountId = $payload['account_id'];
        $this->name = $payload['name'];
        $this->type = Type::from($payload['type']);
    }
}
