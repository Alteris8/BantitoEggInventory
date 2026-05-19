<?php
class Capital
{

	function __construct(

		private readonly float $balance,
		private readonly float $initialBalance,
		private readonly ?int $adminId = null,
		private readonly ?int $id = null,
	) {}

	public function getBalance(): float
	{
		return $this->balance;
	}
	public function getInitialBalance(): float
	{
		return $this->initialBalance;
	}
	public function getId(): ?int
	{
		return $this->id ?? null;
	}
	public function getAdminId(): ?int
	{
		return $this->adminId ?? null;
	}
}
