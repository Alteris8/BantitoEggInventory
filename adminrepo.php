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
		return $this->mapToAdmin($row);
	}
	public function findById(int $id): ?Admin
	{
		$stmt = $this->pdo->prepare("SELECT * FROM admins_tb WHERE id=:id");
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row) return null;

		return $this->mapToAdmin($row);
	}
	public function findAll(string $sortColumn = 'createdAt', string $sortOrder = 'DESC'): array
	{
		$allowedColumns = ['fullName', 'username', 'password', 'createdAt'];
		$allowedOrders = ['ASC', 'DESC'];
		if (!in_array($sortColumn, $allowedColumns)) $sortColumn = 'createdAt';
		if (!in_array(strtoupper($sortOrder), $allowedOrders)) $sortOrder = 'DESC';
		$stmt = $this->pdo->query("SELECT * FROM admins_tb ORDER BY $sortColumn $sortOrder");
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => $this->mapToAdmin($row), $rows);
	}

	public function searchAdmin(string $username): ?array
	{
		$stmt = $this->pdo->prepare("
			SELECT * FROM admins_tb
			WHERE username LIKE :username
		");

		$stmt->execute([
			':username' => '%' . $username . '%'
		]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if (!$rows) return null;

		return array_map(fn($row) => $this->mapToAdmin($row), $rows);
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
		SET fullName = :fullName,
			username = :username
		WHERE id = :id
	");

		$stmt->execute([
			':id' => $id,
			':fullName' => $admin->getFullName(),
			':username' => $admin->getUsername(),
		]);
	}
	private function mapToAdmin(array $row): Admin
	{
		return new Admin(
			fullName: $row['fullName'],
			username: $row['username'],
			password: $row['password'],
			id: (int)$row['id'],
			createdAt: new DateTime($row['createdAt']),
		);
	}
}
