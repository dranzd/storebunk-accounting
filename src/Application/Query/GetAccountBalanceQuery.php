<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Query;

use Dranzd\Common\Cqrs\Domain\Message\AbstractQuery;

/**
 * Get Account Balance Query
 *
 * Query to retrieve the current balance of an account.
 *
 * @package Dranzd\StorebunkAccounting\Application\Query
 */
final class GetAccountBalanceQuery extends AbstractQuery
{
    public const MESSAGE_NAME = 'storebunk.accounting.query.get_account_balance';

    private string $tenantId;
    private string $accountId;

    private function __construct(
        string $messageUuid,
        string $messageName,
        array $payload
    ) {
        parent::__construct($messageUuid, $messageName, $payload);
    }

    public static function create(string $tenantId, string $accountId): self
    {
        $query = new self(
            '',
            self::MESSAGE_NAME,
            [
                'tenant_id' => $tenantId,
                'account_id' => $accountId,
            ]
        );

        $query->tenantId = $tenantId;
        $query->accountId = $accountId;

        return $query;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getPayload(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'account_id' => $this->accountId,
        ];
    }

    protected function setPayload(array $payload): void
    {
        $this->tenantId = $payload['tenant_id'];
        $this->accountId = $payload['account_id'];
    }
}
