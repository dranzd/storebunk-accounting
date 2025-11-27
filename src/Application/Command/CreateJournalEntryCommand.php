<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Command;

use DateTimeInterface;
use DateTime;
use Dranzd\Common\Cqrs\Domain\Message\AbstractCommand;

/**
 * Create Journal Entry Command
 *
 * Command to create a new journal entry in draft state.
 *
 * @package Dranzd\StorebunkAccounting\Application\Command
 */
final class CreateJournalEntryCommand extends AbstractCommand
{
    public const MESSAGE_NAME = 'storebunk.accounting.command.create_journal_entry';

    private string $entryId;
    private DateTimeInterface $date;
    private string $description;
    /** @var array<int, array{accountId: string, amount: float, side: string}> */
    private array $lines;

    private function __construct(
        string $messageUuid,
        string $messageName,
        array $payload
    ) {
        parent::__construct($messageUuid, $messageName, $payload);
    }

    public static function create(
        string $entryId,
        DateTimeInterface $date,
        string $description,
        array $lines
    ): self {
        $command = new self(
            '',
            self::MESSAGE_NAME,
            [
                'entry_id' => $entryId,
                'date' => $date->format('Y-m-d H:i:s'),
                'description' => $description,
                'lines' => $lines,
            ]
        );

        $command->entryId = $entryId;
        $command->date = $date;
        $command->description = $description;
        $command->lines = $lines;

        return $command;
    }

    public function getEntryId(): string
    {
        return $this->entryId;
    }

    public function getDate(): DateTimeInterface
    {
        return $this->date;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return array<int, array{accountId: string, amount: float, side: string}>
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    public function getPayload(): array
    {
        return [
            'entry_id' => $this->entryId,
            'date' => $this->date->format('Y-m-d H:i:s'),
            'description' => $this->description,
            'lines' => $this->lines,
        ];
    }

    protected function setPayload(array $payload): void
    {
        $this->entryId = $payload['entry_id'];
        $this->date = new DateTime($payload['date']);
        $this->description = $payload['description'];
        $this->lines = $payload['lines'];
    }
}
