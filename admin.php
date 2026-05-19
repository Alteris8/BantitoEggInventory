<?php
class Admin
{

	function __construct(

		public readonly string $fullName,
		public readonly string $username,
		public readonly string $password,
		public readonly ?int $id = null,
		public readonly ?DateTime $createdAt = null,
	) {}

	public function getId(): ?int
	{
		return $this->id ?? null;
	}

	public function getUsername(): string
	{
		return $this->username;
	}

	public function getFullName(): string

	{
		return $this->fullName;
	}
	public function getPassword(): string

	{
		return $this->password;
	}

	public function getCreatedAt(): ?DateTime
	{
		return $this->createdAt ?? null;
	}
}
