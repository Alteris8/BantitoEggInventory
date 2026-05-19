<?php
include_once("database.php");
include_once("producttype.php");
include_once("baserepo.php");
class ProductTypeRepo extends BaseRepository
{
	protected function table(): string
	{
		return 'product_types_tb';
	}
	public function findById(int $id): ?ProductType
	{
		$stmt = $this->pdo->prepare("SELECT * FROM product_types_tb WHERE id=:id AND adminId = :adminId");
		$stmt->execute([':id' => $id, ':adminId' => $this->adminId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row) return null;

		return $this->mapToProductTypes($row);
	}
	public function findAll(string $sortOrder = 'DESC'): array
	{
		$stmt = $this->pdo->query("SELECT * FROM product_types_tb  WHERE adminId = $this->adminId ORDER BY type $sortOrder");

		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => $this->mapToProductTypes($row), $rows);
	}
	public function findByType(string $type): ?ProductType
	{
		$stmt = $this->pdo->prepare("
			SELECT * FROM product_types_tb
			WHERE type = :type
			AND adminId = :adminId
			LIMIT 1
		");

		$stmt->execute([
			':type' => $type,
			':adminId' => $this->adminId
		]);

		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row) {
			return null;
		}

		return $this->mapToProductTypes($row);
	}
	public function findAllTypes(): array
	{
		$stmt = $this->pdo->prepare("SELECT type FROM product_types_tb WHERE adminId = :adminId ORDER BY type ASC");
		$stmt->execute([':adminId' => $this->adminId]);
		return $stmt->fetchAll(PDO::FETCH_COLUMN);
	}


	public function searchProductType(string $productType): ?array
	{
		$stmt = $this->pdo->prepare("
			SELECT * FROM product_types_tb 
			WHERE type LIKE :type AND adminId = :adminId
		");

		$stmt->execute([
			':type' => '%' . $productType . '%',
			':adminId' => $this->adminId,
		]);

		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if (!$rows) return null;

		return array_map(fn($row) => $this->mapToProductTypes($row), $rows);
	}



	public function save(ProductType $productType): void
	{
		$stmt = $this->pdo->prepare("
			INSERT INTO product_types_tb (type, adminId) 
			VALUES (:type, :adminId)");

		$stmt->execute([
			':type' => $productType->getProductType(),
			':adminId' => $this->adminId

		]);
	}


	public function paginate(int $page = 1, int $limit = 10, string $sortOrder = 'DESC', string $search = ''): array
	{
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';
		$offset = ($page - 1) * $limit;

		$searchClause = $search !== '' ? "AND type LIKE :search" : "";
		$params = [':adminId' => $this->adminId];
		if ($search !== '') $params[':search'] = '%' . $search . '%';

		$stmt = $this->pdo->prepare("
        SELECT * FROM product_types_tb
        WHERE adminId = :adminId
        $searchClause
        ORDER BY type $sortOrder
        LIMIT :limit OFFSET :offset
    ");
		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value);
		}
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => $this->mapToProductTypes($row), $rows);
	}

	public function countFiltered(string $search = ''): int
	{
		$searchClause = $search !== '' ? "AND type LIKE :search" : "";
		$params = [':adminId' => $this->adminId];
		if ($search !== '') $params[':search'] = '%' . $search . '%';

		$stmt = $this->pdo->prepare("
        SELECT COUNT(*) FROM product_types_tb
        WHERE adminId = :adminId
        $searchClause
    ");
		$stmt->execute($params);
		return (int) $stmt->fetchColumn();
	}

	private function mapToProductTypes(array $row): ProductType
	{
		return new ProductType(
			type: $row['type'],
			id: (int)$row['id'],
			adminId: (int)$row['adminId'],
		);
	}
}
