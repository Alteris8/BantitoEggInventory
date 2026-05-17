<?php
class ProductType
{

	function __construct(
		private readonly string $type,
		private readonly ?int $id = null,
		public readonly ?int $adminId = null,
	) {}

	public function getId(): int
	{
		return $this->id;
	}

	public function getAdminId(): ?int
	{
		return $this->adminId;
	}
	public function getProductType(): string
	{
		return $this->type;
	}
}
