<?php
abstract class BaseRepository
{
	protected PDO $pdo;
	protected ?int $adminId;

	public function __construct(PDO $pdo, ?int $adminId = null)
	{
		$this->pdo = $pdo;
		$this->adminId = $adminId;
	}
	abstract protected function table(): string;
	public function delete(int $id): void
	{
		$stmt = $this->pdo->prepare("
			DELETE FROM {$this->table()} WHERE id = :id AND adminId = :adminId
			");

		$stmt->execute([
			':id' => $id,
			':adminId' => $this->adminId
		]);
	}
	public function countAll(): int
	{
		$stmt = $this->pdo->prepare("
			SELECT COUNT(*) FROM {$this->table()} WHERE adminId = :adminId
			");

		$stmt->execute([
			':adminId' => $this->adminId
		]);
		return (int) $stmt->fetchColumn();
	}
}
