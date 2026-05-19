<?php
class ProductType
{

	function __construct(
		private readonly string $type,
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
	public function getProductType(): string
	{
		return $this->type;
	}
}
