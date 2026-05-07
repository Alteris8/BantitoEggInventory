<?php
class Inventory
{

	function __construct(
		private readonly ?int $id = null,
		private readonly string $productName,
		private readonly int $quantity,
		private readonly float $price,
		private readonly ?DateTime $dateUpdated = new DateTime(),
	) {}

	public function getId(): int
	{
		return $this->id;
	}

	public function getProductName(): string
	{
		return $this->productName;
	}

	public function getQuantity(): int
	{
		return $this->quantity;
	}

	public function getPrice(): float
	{
		return $this->price;
	}

	public function getStatus(): string
	{
		if ($this->quantity === 0) {
			return  "Out of Stock";
		}
		if ($this->quantity <= 5) {
			return "Low Stock";
		} else {
			return  "Available";
		}
	}

	public function getDateUpdated(): DateTime
	{
		return $this->dateUpdated;
	}
}
