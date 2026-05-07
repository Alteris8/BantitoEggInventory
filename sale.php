<?php
class Sale
{

	function __construct(
		private readonly string $productName,
		private readonly int $itemsSold,
		private readonly float $sale,
		private readonly DateTime $date = new DateTime(),
		private readonly ?int $inventoryId = null,
		private readonly ?int $id = null,
	) {}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getInventoryId(): ?int
	{
		return $this->inventoryId;
	}

	public function getProductName(): string
	{
		return $this->productName;
	}

	public function getItemsSold(): int
	{
		return $this->itemsSold;
	}

	public function getSale(): float
	{
		return $this->sale;
	}

	public function getDate(): DateTime
	{
		return $this->date;
	}
}
