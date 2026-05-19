<?php
include_once("database.php");
include_once("sale.php");
include_once("baserepo.php");
include_once("producttyperepo.php");
class SalesRepo extends BaseRepository
{

	private $allowedColumns = ['name', 'productType', 'price', 'dateSold', 'sale', 'productType'];
	private $allowedProductTypes = [];
	protected function table(): string
	{
		return 'sales_tb';
	}
	public function __construct(PDO $pdo, int $adminId, ?ProductTypeRepo $productTypeRepo = null)
	{
		parent::__construct($pdo, $adminId);
		$this->allowedProductTypes = $productTypeRepo->findAllTypes();
	}

	public function findById(int $id): ?Sale
	{
		$stmt = $this->pdo->prepare("SELECT * FROM sales_tb WHERE id = :id AND adminId = :adminId");
		$stmt->execute([':id' => $id, ':adminId' => $this->adminId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$row) return null;

		return $this->mapToSale($row);
	}

	public function findAll(string $sortColumn = 'dateSold', string $sortOrder = 'DESC'): array
	{
		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'dateSold';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';

		$stmt = $this->pdo->prepare("SELECT * FROM sales_tb  WHERE adminId = :adminId ORDER BY $sortColumn $sortOrder ");
		$stmt->execute([':adminId' => $this->adminId]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => $this->mapToSale($row), $rows);
	}
	public function findSalesByToday(string $sortColumn = 'dateSold', string $sortOrder = 'DESC', ?string $productType = null, ?string $search = null): array
	{
		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'dateSold';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';
		$productTypeClause = ($productType && in_array($productType, $this->allowedProductTypes)) ? "AND productType = :productType" : "";
		$searchClause = $search !== '' ? "AND name LIKE :search" : "";

		$stmt = $this->pdo->prepare("
			SELECT * FROM sales_tb 
			WHERE adminId = :adminId 
		AND DATE(dateSold) = CURDATE() $productTypeClause
			$searchClause 
			ORDER BY $sortColumn $sortOrder
		    ");
		$params = [':adminId' => $this->adminId];
		if ($productType && in_array($productType, $this->allowedProductTypes)) $params[':productType'] = $productType;
		if ($search !== '') $params[':search'] = '%' . $search . '%';
		$stmt->execute($params);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => $this->mapToSale($row), $rows);
	}



	public function totalSales(string $filter = 'all', ?int $month = null, ?int $week = null, ?int $year = null, ?string $productType = null, ?string $search = null): float
	{
		$productTypeClause = ($productType && in_array($productType, $this->allowedProductTypes)) ? "AND productType = :productType" : "";
		$searchClause      = $search !== '' ? "AND name LIKE :search" : "";

		$dateClause = "";
		if ($filter === 'now') {
			$dateClause = "AND DATE(dateSold) = CURDATE()";
		} elseif ($filter === 'month' && $month && $year) {
			$dateClause = "AND MONTH(dateSold) = :month AND YEAR(dateSold) = :year";
		} elseif ($filter === 'week' && $month && $week && $year) {
			$ranges = [1 => [1, 7], 2 => [8, 14], 3 => [15, 21], 4 => [22, 31]];
			$dateClause = "AND MONTH(dateSold) = :month AND YEAR(dateSold) = :year AND DAY(dateSold) BETWEEN :start AND :end";
		}

		$sql = "SELECT COALESCE(SUM(sale), 0) FROM sales_tb WHERE adminId = :adminId $productTypeClause $searchClause $dateClause AND (status = 'active' OR status = 'corrected')";

		$params = [':adminId' => $this->adminId];
		if ($productType && in_array($productType, $this->allowedProductTypes)) $params[':productType'] = $productType;
		if ($search !== '') $params[':search'] = '%' . $search . '%';
		if ($filter === 'month' && $month && $year) {
			$params[':month'] = $month;
			$params[':year']  = $year;
		} elseif ($filter === 'week' && $month && $week && $year) {
			$ranges = [1 => [1, 7], 2 => [8, 14], 3 => [15, 21], 4 => [22, 31]];
			$params[':month'] = $month;
			$params[':year']  = $year;
			$params[':start'] = $ranges[$week][0];
			$params[':end']   = $ranges[$week][1];
		}

		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($params);
		return (float)($stmt->fetchColumn() ?? 0);
	}

	public function findSalesByMonthWeek(int $month, int $week, int $year, string $sortColumn = 'dateSold', string $sortOrder = 'DESC', ?string $productType = null, ?string $search = null): array
	{
		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'dateSold';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';
		$productTypeClause = ($productType && in_array($productType, $this->allowedProductTypes)) ? "AND productType = :productType" : "";
		$searchClause = $search !== '' ? "AND name LIKE :search" : "";

		$ranges = [
			1 => [1, 7],
			2 => [8, 14],
			3 => [15, 21],
			4 => [22, 31],
		];

		$start = $ranges[$week][0];
		$end   = $ranges[$week][1];

		$stmt = $this->pdo->prepare("
		SELECT * FROM sales_tb 
		WHERE MONTH(dateSold) = :month
		AND YEAR(dateSold) = :year
			AND DAY(dateSold) BETWEEN :start AND :end AND adminId = :adminId $productTypeClause
			$searchClause
		ORDER BY $sortColumn $sortOrder
			");

		$params = [':month' => $month, ':year' => $year, ':start' => $start, ':end' => $end, ':adminId' => $this->adminId];
		if ($productType && in_array($productType, $this->allowedProductTypes)) $params[':productType'] = $productType;
		if ($search !== '') $params[':search'] = '%' . $search . '%';
		$stmt->execute($params);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => $this->mapToSale($row), $rows);
	}

	public function findSalesByMonth(int $month, int $year, string $sortColumn = 'dateSold', string $sortOrder = 'DESC', ?string $productType = null, ?string $search = null): array
	{
		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'dateSold';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';
		$productTypeClause = ($productType && in_array($productType, $this->allowedProductTypes)) ? "AND productType = :productType" : "";
		$searchClause = $search !== '' ? "AND description LIKE :search" : "";


		$stmt = $this->pdo->prepare("
			SELECT * FROM sales_tb
			WHERE MONTH(dateSold) = :month
			AND YEAR(dateSold) = :year AND adminId = :adminId $productTypeClause
			$searchClause
			ORDER BY $sortColumn $sortOrder
		    ");
		$params = [':month' => $month, ':year' => $year, ':adminId' => $this->adminId];
		if ($productType && in_array($productType, $this->allowedProductTypes)) $params[':productType'] = $productType;
		if ($search !== '') $params[':search'] = '%' . $search . '%';
		$stmt->execute($params);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => $this->mapToSale($row), $rows);
	}

	public function findByProductType(string $productType, string $sortColumn = 'dateSold', string $sortOrder = 'DESC'): array
	{
		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'dateSold';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';
		if (!in_array($productType, $this->allowedProductTypes)) return [];

		$stmt = $this->pdo->prepare("
				SELECT * FROM sales_tb 
				WHERE adminId = :adminId 
				AND productType = :productType
				ORDER BY $sortColumn $sortOrder
			    ");
		$stmt->execute([
			':adminId'     => $this->adminId,
			':productType' => $productType,
		]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => $this->mapToSale($row), $rows);
	}

	public function save(Sale $sale): void
	{
		$stmt = $this->pdo->prepare("INSERT INTO sales_tb (inventoryId, price, name, productType, itemsSold, sale, dateSold, adminId)
			VALUES (:inventoryId, :price, :name, :productType, :itemsSold, :sale, :dateSold, :adminId)");
		$stmt->execute([
			':inventoryId' => $sale->getInventoryId(),
			':price' => $sale->getPrice(),
			':name' => $sale->getProductName(),
			':productType' => $sale->getProductType(),
			':itemsSold' => $sale->getItemsSold(),
			':sale' => $sale->getSale(),
			':dateSold' => $sale->getDate()->format('Y-m-d'),
			':adminId' => $this->adminId,

		]);
	}

	public function voidSale(int $id): void
	{
		$sale = $this->findById($id);
		$this->pdo->beginTransaction();
		try {
			$stmt = $this->pdo->prepare("
			UPDATE sales_tb
			SET status = 'voided',
				originalSaleId = :id
				WHERE id = :id AND
				adminId = :adminId
		");

			$stmt->execute([
				':id' => $id,
				':adminId' => $this->adminId
			]);
			$stmt = $this->pdo->prepare("
			UPDATE capital_transactions_tb
			SET status = 'voided'
				WHERE saleId = :saleId AND
				adminId = :adminId
		");

			$stmt->execute([
				':saleId' => $id,
				':adminId' => $this->adminId
			]);

			$stmt = $this->pdo->prepare("
			UPDATE inventory_tb
			SET quantity = quantity + :quantitySold
			WHERE id = :inventoryId AND 
			adminId = :adminId
			");
			$stmt->execute([
				':quantitySold' => $sale->getItemsSold(),
				':inventoryId' => $id,
				':adminId' => $this->adminId
			]);
		} catch (\Throwable $th) {
			//throw $th;
		}
	}

	public function makeSaleActive(int $id): void
	{
		$sale = $this->findById($id);
		$stmt = $this->pdo->prepare("
			UPDATE sales_tb
			SET status = 'active'
				WHERE id = :id AND
				adminId = :adminId
		");

		$stmt->execute([
			':id' => $id,
			':adminId' => $this->adminId
		]);
		$stmt = $this->pdo->prepare("
			UPDATE capital_transactions_tb
			SET status = 'active'
				WHERE saleId = :saleId AND
				adminId = :adminId
		");

		$stmt->execute([
			':saleId' => $id,
			':adminId' => $this->adminId
		]);

		$stmt = $this->pdo->prepare("
			UPDATE inventory_tb
			SET quantity = quantity - :quantitySold
			WHERE inventoryId = :inventoryId AND 
			adminId = :adminId
			");
		$stmt->execute([
			':quantitySold' => $sale->getItemsSold(),
			':inventoryId' => $id,
			':adminId' => $this->adminId
		]);
	}

	public function correctSale(int $id): void
	{
		$sale = $this->findById($id);
		$stmt = $this->pdo->prepare("
			UPDATE sales_tb
			SET status = 'corrected'
				WHERE id = :id AND
				adminId = :adminId
		");

		$stmt->execute([
			':id' => $id,
			':adminId' => $this->adminId
		]);
		$stmt = $this->pdo->prepare("
			UPDATE capital_transactions_tb
			SET status = 'corrected'
				WHERE saleId = :saleId AND
				adminId = :adminId
		");

		$stmt->execute([
			':saleId' => $id,
			':adminId' => $this->adminId
		]);

		$stmt = $this->pdo->prepare("
			UPDATE inventory_tb
			SET quantity = quantity + :quantitySold
			WHERE inventoryId = :inventoryId AND 
			adminId = :adminId
			");
		$stmt->execute([
			':quantitySold' => $sale->getItemsSold(),
			':inventoryId' => $id,
			':adminId' => $this->adminId
		]);
	}
	public function searchSale(string $productName): ?array
	{
		$stmt = $this->pdo->prepare("
			SELECT * FROM sales_tb 
			WHERE name LIKE :name AND adminId = :adminId
		");

		$stmt->execute([
			':name' => '%' . $productName . '%',
			':adminId' => $this->adminId,
		]);

		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if (!$rows) return null;
		return array_map(fn($row) => $this->mapToSale($row), $rows);
	}
	public function delete(int $id): void
	{
		$this->pdo->beginTransaction();
		try {
			$stmt = $this->pdo->prepare("
            DELETE FROM sales_tb 
            WHERE originalSaleId = :id AND adminId = :adminId
        ");
			$stmt->execute([':id' => $id, ':adminId' => $this->adminId]);

			$stmt = $this->pdo->prepare("
            DELETE FROM sales_tb WHERE id = :id AND adminId = :adminId
        ");
			$stmt->execute([':id' => $id, ':adminId' => $this->adminId]);

			$stmt = $this->pdo->prepare("
            DELETE FROM capital_transactions_tb 
            WHERE saleId = :saleId AND adminId = :adminId AND type = 'sale'
        ");
			$stmt->execute([':saleId' => $id, ':adminId' => $this->adminId]);

			$this->pdo->commit();
		} catch (Exception $e) {
			$this->pdo->rollBack();
			throw $e;
		}
	}

	public function paginate(int $page = 1, int $limit = 10, string $sortColumn = 'dateSold', string $sortOrder = 'DESC', string $search = '', string $productType = '', string $filter = 'all', ?int $month = null, ?int $week = null, ?int $year = null): array
	{
		$offset = ($page - 1) * $limit;
		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'dateSold';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';

		$searchClause = $search !== '' ? "AND name LIKE :search" : "";
		$typeClause   = ($productType !== '' && in_array($productType, $this->allowedProductTypes)) ? "AND productType = :productType" : "";

		$dateClause = "";
		if ($filter === 'now') {
			$dateClause = "AND DATE(dateSold) = CURDATE()";
		} elseif ($filter === 'month' && $month && $year) {
			$dateClause = "AND MONTH(dateSold) = :month AND YEAR(dateSold) = :year";
		} elseif ($filter === 'week' && $month && $week && $year) {
			$ranges = [1 => [1, 7], 2 => [8, 14], 3 => [15, 21], 4 => [22, 31]];
			$start  = $ranges[$week][0];
			$end    = $ranges[$week][1];
			$dateClause = "AND MONTH(dateSold) = :month AND YEAR(dateSold) = :year AND DAY(dateSold) BETWEEN :weekStart AND :weekEnd";
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
				SELECT * FROM sales_tb
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
		return array_map(fn($row) => $this->mapToSale($row), $rows);
	}

	public function countFiltered(string $search = '', string $productType = '', string $filter = 'all', ?int $month = null, ?int $week = null, ?int $year = null): int
	{
		$searchClause = $search !== '' ? "AND name LIKE :search" : "";
		$typeClause   = ($productType !== '' && in_array($productType, $this->allowedProductTypes)) ? "AND productType = :productType" : "";

		$dateClause = "";
		if ($filter === 'now') {
			$dateClause = "AND DATE(dateSold) = CURDATE()";
		} elseif ($filter === 'month' && $month && $year) {
			$dateClause = "AND MONTH(dateSold) = :month AND YEAR(dateSold) = :year";
		} elseif ($filter === 'week' && $month && $week && $year) {
			$ranges = [1 => [1, 7], 2 => [8, 14], 3 => [15, 21], 4 => [22, 31]];
			$start  = $ranges[$week][0];
			$end    = $ranges[$week][1];
			$dateClause = "AND MONTH(dateSold) = :month AND YEAR(dateSold) = :year AND DAY(dateSold) BETWEEN :weekStart AND :weekEnd";
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
			SELECT COUNT(*) FROM sales_tb
			WHERE adminId = :adminId
			$searchClause
			$typeClause
			$dateClause
		    ");
		$stmt->execute($params);
		return (int)$stmt->fetchColumn();
	}
	private function mapToSale(array $row): Sale
	{
		return new Sale(
			productName: $row['name'],
			itemsSold: (int)$row['itemsSold'],
			sale: (float)$row['sale'],
			status: $row['status'],
			price: (float)$row['price'],
			date: new DateTime($row['dateSold']),
			productType: $row['productType'],
			inventoryId: (int)$row['inventoryId'],
			adminId: (int)$row['adminId'],
			id: (int)$row['id'],
		);
	}
}
