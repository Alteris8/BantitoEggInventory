<?php


class CapitalTransaction
{

	function __construct(
		private readonly string $type,
		private readonly float $amount,
		private readonly string $description,
		private readonly ?DateTime $createdAt = null,
		private readonly ?int $id = null,
		private readonly ?int $adminId = null,
		private readonly ?int $saleId = null,
		private readonly ?int $inventoryId = null,
		private readonly ?int $quantity = null,
	) {}

	public function getId(): ?int
	{
		return $this->id ?? null;
	}

	public function getAdminId(): ?int
	{
		return $this->adminId ?? null;
	}
	public function getSaleId(): ?int
	{
		return $this->saleId ?? null;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getAmount(): float
	{
		return $this->amount;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function getCurrentDate(): DateTime
	{
		return $this->createdAt;
	}

	public function getInventoryId(): ?int
	{
		return $this->inventoryId ?? null;
	}
	public function getQuantity(): ?int
	{
		return $this->quantity ?? null;
	}
}
