<?php
include_once("database.php");
include_once("inventory.php");
include_once("baserepo.php");
include_once("producttyperepo.php");
class ArchiveItemRepo extends BaseRepository
{

	private $allowedColumns = ['name', 'quantity', 'price', 'lastUpdated', 'productType'];
	private $allowedProductTypes = [];
	public function __construct(PDO $pdo, int $adminId, ProductTypeRepo $productTypeRepo)
	{
		parent::__construct($pdo, $adminId);
		$this->allowedProductTypes = $productTypeRepo->findAllTypes();
	}
	protected function table(): string
	{
		return 'archives_tb';
	}
	public function findById(int $id): ?Inventory
	{
		$stmt = $this->pdo->prepare("SELECT * FROM archives_tb WHERE id=:id AND adminId = :adminId");
		$stmt->execute([':id' => $id, ':adminId' => $this->adminId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row) return null;
		return $this->mapToArchiveItem($row);
	}
	public function findAll(string $sortColumn = 'lastUpdated', string $sortOrder = 'DESC'): array
	{

		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'lastUpdated';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';

		$stmt = $this->pdo->prepare("SELECT * FROM archives_tb  WHERE adminId = :adminId ORDER BY $sortColumn $sortOrder");
		$stmt->execute([':adminId' => $this->adminId]);

		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$archives = array_map(fn($row) => $this->mapToArchiveItem($row), $rows);


		return $archives;
	}
	public function findArchiveItemsByMonth(int $month, int $year, string $sortColumn = 'lastUpdated', string $sortOrder = 'DESC', ?string $productType = null): array
	{

		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'lastUpdated';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';
		$productTypeClause = ($productType && in_array($productType, $this->allowedProductTypes)) ? "AND productType = :productType" : "";

		$stmt = $this->pdo->prepare("
			SELECT * FROM archives_tb
			WHERE MONTH(lastUpdated) = :month
			AND YEAR(lastUpdated) = :year 
			AND adminId = :adminId
			$productTypeClause
			ORDER BY $sortColumn $sortOrder
		    ");

		$params = [':month' => $month, ':year' => $year, ':adminId' => $this->adminId];
		if ($productType && in_array($productType, $this->allowedProductTypes)) $params[':productType'] = $productType;

		$stmt->execute($params);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => $this->mapToArchiveItem($row), $rows);
	}
	public function findArchiveItemsByMonthWeek(int $month, int $week, int $year, string $sortColumn = 'lastUpdated', string $sortOrder = 'DESC', ?string $productType = null): array
	{

		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'lastUpdated';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';
		$productTypeClause = ($productType && in_array($productType, $this->allowedProductTypes)) ? "AND productType = :productType" : "";

		$ranges = [
			1 => [1, 7],
			2 => [8, 14],
			3 => [15, 21],
			4 => [22, 31],
		];

		$start = $ranges[$week][0];
		$end   = $ranges[$week][1];

		$stmt = $this->pdo->prepare("
				SELECT * FROM archives_tb
				WHERE MONTH(lastUpdated) = :month
			AND YEAR(lastUpdated) = :year
			AND DAY(lastUpdated) BETWEEN :start AND :end AND adminId = :adminId $productTypeClause
			ORDER BY $sortColumn $sortOrder
		");

		$params = [
			':month' => $month,
			':year'  => $year,
			':start' => $start,
			':end'   => $end,
			':adminId'   => $this->adminId,
		];
		if ($productType && in_array($productType, $this->allowedProductTypes)) $params[':productType'] = $productType;

		$stmt->execute($params);

		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => $this->mapToArchiveItem($row), $rows);
	}
	public function findArchiveItemsByToday(string $sortColumn = 'lastUpdated', string $sortOrder = 'DESC', ?string $productType = null): array
	{
		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'lastUpdated';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';
		$productTypeClause = ($productType && in_array($productType, $this->allowedProductTypes)) ? "AND productType = :productType" : "";

		$stmt = $this->pdo->prepare("
					SELECT * FROM archives_tb
					WHERE adminId = :adminId 
			AND DATE(lastUpdated) = CURDATE() $productTypeClause
			ORDER BY $sortColumn $sortOrder
		    ");
		$params = [
			':adminId' => $this->adminId
		];

		if ($productType && in_array($productType, $this->allowedProductTypes)) {
			$params[':productType'] = $productType;
		}

		$stmt->execute($params);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => $this->mapToArchiveItem($row), $rows);
	}
	public function findByProductType(string $productType, string $sortColumn = 'lastUpdated', string $sortOrder = 'DESC'): array
	{
		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'lastUpdated';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';
		if (!in_array($productType, $this->allowedProductTypes)) return [];

		$stmt = $this->pdo->prepare("
			SELECT * FROM archives_tb 
			WHERE adminId = :adminId 
			AND productType = :productType
			ORDER BY $sortColumn $sortOrder
		    ");
		$stmt->execute([
			':adminId'     => $this->adminId,
			':productType' => $productType,
		]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => $this->mapToArchiveItem($row), $rows);
	}

	public function searchArchiveItem(string $productName): ?array
	{
		$stmt = $this->pdo->prepare("
			SELECT * FROM archives_tb 
			WHERE name LIKE :name AND adminId = :adminId
		");

		$stmt->execute([
			':name' => '%' . $productName . '%',
			':adminId' => $this->adminId,
		]);

		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if (!$rows) return null;

		return array_map(fn($row) => $this->mapToArchiveItem($row), $rows);
	}


	public function transferToInventory(int $id, Inventory $archiveItem): void
	{
		$stmt = $this->pdo->prepare("
			INSERT INTO inventory_tb (name, productType, quantity, price, adminId) 
			VALUES (:name, :productType, :quantity, :price, :adminId)");
		$stmt->execute([
			'name' => $archiveItem->getProductName(),
			':productType' => $archiveItem->getProductType(),
			'quantity' => $archiveItem->getQuantity(),
			'price' => $archiveItem->getPrice(),
			'adminId' => $this->adminId
		]);
		$this->delete($id);
	}

	public function paginate(int $page = 1, int $limit = 10, string $sortColumn = 'lastUpdated', string $sortOrder = 'DESC', string $search = '', string $productType = '', string $filter = 'all', ?int $month = null, ?int $week = null, ?int $year = null): array
	{
		$offset = ($page - 1) * $limit;
		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'lastUpdated';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';

		$searchClause = $search !== '' ? "AND name LIKE :search" : "";
		$typeClause   = ($productType !== '' && in_array($productType, $this->allowedProductTypes)) ? "AND productType = :productType" : "";

		$dateClause = "";
		if ($filter === 'now') {
			$dateClause = "AND DATE(lastUpdated) = CURDATE()";
		} elseif ($filter === 'month' && $month && $year) {
			$dateClause = "AND MONTH(lastUpdated) = :month AND YEAR(lastUpdated) = :year";
		} elseif ($filter === 'week' && $month && $week && $year) {
			$ranges = [1 => [1, 7], 2 => [8, 14], 3 => [15, 21], 4 => [22, 31]];
			$start  = $ranges[$week][0];
			$end    = $ranges[$week][1];
			$dateClause = "AND MONTH(lastUpdated) = :month AND YEAR(lastUpdated) = :year AND DAY(lastUpdated) BETWEEN :weekStart AND :weekEnd";
		}

		$params = [':adminId' => $this->adminId];
		if ($search !== '')     $params[':search']      = '%' . $search . '%';
		if ($typeClause !== '') $params[':productType'] = $productType;
		if ($filter === 'month' && $month && $year) {
			$params[':month'] = $month;
			$params[':year']  = $year;
		} elseif ($filter === 'week' && $month && $week && $year) {
			$params[':month']    = $month;
			$params[':year']     = $year;
			$params[':weekStart'] = $start;
			$params[':weekEnd']   = $end;
		}

		$stmt = $this->pdo->prepare("
			SELECT * FROM archives_tb
			WHERE adminId = :adminId
			$searchClause
			$typeClause
			$dateClause
			ORDER BY $sortColumn $sortOrder
			LIMIT :limit OFFSET :offset
		    ");
		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value);
		}
		$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => $this->mapToArchiveItem($row), $rows);
	}

	public function countFiltered(string $search = '', string $productType = '', string $filter = 'all', ?int $month = null, ?int $week = null, ?int $year = null): int
	{
		$searchClause = $search !== '' ? "AND name LIKE :search" : "";
		$typeClause   = ($productType !== '' && in_array($productType, $this->allowedProductTypes)) ? "AND productType = :productType" : "";

		$dateClause = "";
		if ($filter === 'now') {
			$dateClause = "AND DATE(lastUpdated) = CURDATE()";
		} elseif ($filter === 'month' && $month && $year) {
			$dateClause = "AND MONTH(lastUpdated) = :month AND YEAR(lastUpdated) = :year";
		} elseif ($filter === 'week' && $month && $week && $year) {
			$ranges = [1 => [1, 7], 2 => [8, 14], 3 => [15, 21], 4 => [22, 31]];
			$start  = $ranges[$week][0];
			$end    = $ranges[$week][1];
			$dateClause = "AND MONTH(lastUpdated) = :month AND YEAR(lastUpdated) = :year AND DAY(lastUpdated) BETWEEN :weekStart AND :weekEnd";
		}

		$params = [':adminId' => $this->adminId];
		if ($search !== '')     $params[':search']      = '%' . $search . '%';
		if ($typeClause !== '') $params[':productType'] = $productType;
		if ($filter === 'month' && $month && $year) {
			$params[':month'] = $month;
			$params[':year']  = $year;
		} elseif ($filter === 'week' && $month && $week && $year) {
			$params[':month']     = $month;
			$params[':year']      = $year;
			$params[':weekStart'] = $start;
			$params[':weekEnd']   = $end;
		}

		$stmt = $this->pdo->prepare("
			SELECT COUNT(*) FROM archives_tb
			WHERE adminId = :adminId
			$searchClause
			$typeClause
			$dateClause
		    ");
		$stmt->execute($params);
		return (int) $stmt->fetchColumn();
	}

	private function mapToArchiveItem(array $row): Inventory
	{
		return new Inventory(
			productName: $row['name'],
			quantity: (int)$row['quantity'],
			price: (float)$row['price'],
			dateUpdated: new DateTime($row['lastUpdated']),
			productType: $row['productType'],
			id: (int)$row['id'],
			adminId: (int)$row['adminId'],
		);
	}
}
