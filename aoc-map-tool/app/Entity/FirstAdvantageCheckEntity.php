<?php

namespace App\Entity;
class FirstAdvantageCheckEntity
{
    private array $order;

    public function __construct(array $order)
    {
        $this->order = $order;
    }

    public function getFIOrderId(): ?string
    {
        return $this->order['order_id'];
    }

    public function getStatus(): ?string
    {
        return $this->order['order_status'];
    }

    public function getResultStatus(): ?string
    {
        return $this->order['result_status'];
    }

    public function getApplicationStatus(): ?string
    {
        return $this->order['application_details']['application_status'] ?? null;
    }

    public function getQuoteBacks(): array
    {
        return $this->order['meta_data']['quote_backs'];
    }

    public function extractFICandidateId(): ?string
    {
        $candidateId = null;
        foreach ($this->getQuoteBacks() as $quoteBackRecord) {
            if ($quoteBackRecord['name'] === 'candidate_id') {
                return $quoteBackRecord['value'];
            }
        }

        return $candidateId;
    }

    public function getMappedData(): array
    {
        return array_filter([
            'fa_order_id' => $this->getFIOrderId(),
            'status' => $this->getStatus(),
            'application_status' => $this->getApplicationStatus(),
            'result_status' => $this->getResultStatus()
        ]);
    }
}
