<?php

declare(strict_types=1);

namespace Dranzd\StorebunkAccounting\Application\Query;

use DateTime;
use DateTimeInterface;
use Dranzd\Common\Cqrs\Domain\Message\AbstractQuery;

/**
 * Get Ledger Query
 *
 * Query to retrieve ledger postings for an account.
 *
 * @package Dranzd\StorebunkAccounting\Application\Query
 */
final class GetLedgerQuery extends AbstractQuery
{
    public const MESSAGE_NAME = 'storebunk.accounting.query.get_ledger';

    private string $tenantId;
    private string $accountId;
    private ?DateTimeInterface $fromDate;
    private ?DateTimeInterface $toDate;

    private function __construct(
        string $messageUuid,
        string $messageName,
        array $payload
    ) {
        parent::__construct($messageUuid, $messageName, $payload);
    }

    public static function create(
        string $tenantId,
        string $accountId,
        ?DateTimeInterface $fromDate = null,
        ?DateTimeInterface $toDate = null
    ): self {
        $query = new self(
            '',
            self::MESSAGE_NAME,
            [
                'tenant_id' => $tenantId,
                'account_id' => $accountId,
                'from_date' => $fromDate?->format('Y-m-d H:i:s'),
                'to_date' => $toDate?->format('Y-m-d H:i:s'),
            ]
        );

        $query->tenantId = $tenantId;
        $query->accountId = $accountId;
        $query->fromDate = $fromDate;
        $query->toDate = $toDate;

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

    public function getFromDate(): ?DateTimeInterface
    {
        return $this->fromDate;
    }

    public function getToDate(): ?DateTimeInterface
    {
        return $this->toDate;
    }

    public function getPayload(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'account_id' => $this->accountId,
            'from_date' => $this->fromDate?->format('Y-m-d H:i:s'),
            'to_date' => $this->toDate?->format('Y-m-d H:i:s'),
        ];
    }

    protected function setPayload(array $payload): void
    {
        $this->tenantId = $payload['tenant_id'];
        $this->accountId = $payload['account_id'];
        $this->fromDate = $payload['from_date'] ? new DateTime($payload['from_date']) : null;
        $this->toDate = $payload['to_date'] ? new DateTime($payload['to_date']) : null;
    }
}
