<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Query;

use Dranzd\Common\Cqrs\Domain\Message\AbstractQuery;

/**
 * Get All Accounts Query
 *
 * Query to retrieve all accounts from the chart of accounts.
 *
 * @package Dranzd\StorebunkAccounting\Application\Query
 */
final class GetAllAccountsQuery extends AbstractQuery
{
    public const MESSAGE_NAME = 'storebunk.accounting.query.get_all_accounts';

    private function __construct(
        string $messageUuid,
        string $messageName,
        array $payload
    ) {
        parent::__construct($messageUuid, $messageName, $payload);
    }

    public static function create(): self
    {
        return new self('', self::MESSAGE_NAME, []);
    }

    public function getPayload(): array
    {
        return [];
    }

    protected function setPayload(array $payload): void
    {
        // No payload for this query
    }
}
