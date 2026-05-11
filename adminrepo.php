<?php

include_once("database.php");
include_once("admin.php");
include_once("baserepo.php");

class AdminRepo extends BaseRepository
{
	protected function table(): string
	{
		return 'admins_tb';
	}

	public function findByUsername(string $username): ?Admin
	{
		$stmt = $this->pdo->prepare("SELECT * FROM admins_tb WHERE username=:username ");
		$stmt->execute([':username' => $username]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row) return null;

		return new Admin(
			$row['fullName'],
			$row['username'],
			$row['password'],
			(int)$row['id'],
			new DateTime($row['createdAt']),
		);
	}
	public function findById(int $id): ?Admin
	{
		$stmt = $this->pdo->prepare("SELECT * FROM admins_tb WHERE id=:id");
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row) return null;

		return new Admin(
			$row['fullName'],
			$row['username'],
			$row['password'],
			(int)$row['id'],
			new DateTime($row['createdAt']),
		);
	}
	public function findAll(string $sortColumn = 'createdAt', string $sortOrder = 'DESC'): array
	{
		$allowedColumns = ['fullName', 'username', 'password', 'createdAt'];
		$allowedOrders = ['ASC', 'DESC'];
		if (!in_array($sortColumn, $allowedColumns)) $sortColumn = 'lastUpdated';
		if (!in_array(strtoupper($sortOrder), $allowedOrders)) $sortOrder = 'DESC';
		$stmt = $this->pdo->query("SELECT * FROM inventory_tb ORDER BY $sortColumn $sortOrder");
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => new Admin(
			$row['fullName'],
			$row['username'],
			$row['password'],
			(int)$row['id'],
			new DateTime($row['createdAt']),
		), $rows);
	}

	public function searchAdmin(string $username): ?array
	{
		$stmt = $this->pdo->prepare("
			SELECT * FROM admins_tb
			WHERE name LIKE :name
		");

		$stmt->execute([
			':name' => '%' . $username . '%'
		]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if (!$rows) return null;
		return array_map(fn($row) => new Admin(
			$row['fullName'],
			$row['username'],
			$row['password'],
			(int)$row['id'],
			new DateTime($row['createdAt']),
		), $rows);
	}

	public function delete(int $id): void
	{
		$stmt = $this->pdo->prepare("
			DELETE FROM admin_tb WHERE id = :id
			");

		$stmt->execute([
			':id' => $id,
		]);
	}

	public function save(Admin $admin): void
	{
		$hashedPassword = password_hash($admin->getPassword(), PASSWORD_BCRYPT);
		$stmt = $this->pdo->prepare("
			INSERT INTO admins_tb (fullName, username, password) 
			VALUES (:fullName, :username, :password)");

		$stmt->execute([
			':fullName' => $admin->getFullName(),
			':username' => $admin->getUsername(),
			':password' => $hashedPassword,
		]);
	}

	public function update(int $id, Admin $admin): void
	{
		$stmt = $this->pdo->prepare("
			UPDATE admins_tb
			SET fullName = :name,
				username = :username,
			WHERE id = :id
			");

		$stmt->execute([
			':id' => $id,
			':fullName' => $admin->getFullName(),
			':username' => $admin->getUsername(),
		]);
	}
}
