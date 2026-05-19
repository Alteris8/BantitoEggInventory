<?php
class Inventory
{

	function __construct(
		private readonly string $productName,
		private readonly int $quantity,
		private readonly float $price,
		private readonly ?DateTime $dateUpdated = null,
		private readonly ?string $productType = null,
		private readonly ?int $id = null,
		public readonly ?int $adminId = null,
	) {}

	public function getId(): ?int
	{
		return $this->id ?? null;
	}

	public function getAdminId(): ?int
	{
		return $this->adminId ?? null;
	}
	public function getProductType(): ?string
	{
		return $this->productType ?? null;
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
