<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Command;

use Dranzd\Common\Cqrs\Domain\Message\AbstractCommand;

/**
 * Post Journal Entry Command
 *
 * Command to post a journal entry to the ledger.
 *
 * @package Dranzd\StorebunkAccounting\Application\Command
 */
final class PostJournalEntryCommand extends AbstractCommand
{
    public const MESSAGE_NAME = 'storebunk.accounting.command.post_journal_entry';

    private string $entryId;

    private function __construct(
        string $messageUuid,
        string $messageName,
        array $payload
    ) {
        parent::__construct($messageUuid, $messageName, $payload);
    }

    public static function create(string $entryId): self
    {
        $command = new self(
            '',
            self::MESSAGE_NAME,
            ['entry_id' => $entryId]
        );

        $command->entryId = $entryId;

        return $command;
    }

    public function getEntryId(): string
    {
        return $this->entryId;
    }

    public function getPayload(): array
    {
        return ['entry_id' => $this->entryId];
    }

    protected function setPayload(array $payload): void
    {
        $this->entryId = $payload['entry_id'];
    }
}
