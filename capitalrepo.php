<?php
include_once("database.php");
include_once("capital.php");
include_once("baserepo.php");

class CapitalRepo extends BaseRepository
{
	protected function table(): string
	{
		return 'capital_tb';
	}

	public function findById(int $id): ?Capital
	{
		$stmt = $this->pdo->prepare("SELECT * FROM capital_tb WHERE id=:id ");
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row) return null;

		return new Capital(
			$row['balance'],
			$row['initialBalance'],
			(int)$row['id'],
			(int)$row['adminId'],
		);
	}


	public function save(Capital $capital): void
	{
		$stmt = $this->pdo->prepare("
			INSERT INTO capital_tb (balance, initialBalance, adminId) 
			VALUES (:balance, :initialBalance, :adminId)");

		$stmt->execute([
			':balance' => $capital->getBalance(),
			':initialBalance' => $capital->getInitialBalance(),
			':adminId' => $this->adminId,
		]);
	}

	public function update(Capital $capital): void
	{
		$stmt = $this->pdo->prepare("
			UPDATE capital_tb 
			SET balance = :balance,
			initialBalance = :initialBalance
		   	WHERE adminId = :adminId
			");

		$stmt->execute([
			':balance' => $capital->getBalance(),
			':initialBalance' => $capital->getInitialBalance(),
			':adminId' => $this->adminId,
		]);
	}
}
