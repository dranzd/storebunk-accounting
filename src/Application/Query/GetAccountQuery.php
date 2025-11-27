<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Query;

use Dranzd\Common\Cqrs\Domain\Message\AbstractQuery;

/**
 * Get Account Query
 *
 * Query to retrieve an account from the chart of accounts.
 *
 * @package Dranzd\StorebunkAccounting\Application\Query
 */
final class GetAccountQuery extends AbstractQuery
{
    public const MESSAGE_NAME = 'storebunk.accounting.query.get_account';

    private string $accountId;

    private function __construct(
        string $messageUuid,
        string $messageName,
        array $payload
    ) {
        parent::__construct($messageUuid, $messageName, $payload);
    }

    public static function create(string $accountId): self
    {
        $query = new self(
            '',
            self::MESSAGE_NAME,
            ['account_id' => $accountId]
        );

        $query->accountId = $accountId;

        return $query;
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getPayload(): array
    {
        return ['account_id' => $this->accountId];
    }

    protected function setPayload(array $payload): void
    {
        $this->accountId = $payload['account_id'];
    }
}
