<?php
include_once("database.php");
include_once("inventory.php");
include_once("baserepo.php");
include_once("producttyperepo.php");
class InventoryRepo extends BaseRepository
{
	private $allowedColumns = ['name', 'quantity', 'price', 'lastUpdated', 'productType'];
	private $allowedProductTypes = [];
	private $allowedStockStatuses = ['Available', 'Low Stock', 'Out of Stock'];
	public function __construct(PDO $pdo, int $adminId, ?ProductTypeRepo $productTypeRepo = null)
	{
		parent::__construct($pdo, $adminId);
		$this->allowedProductTypes = $productTypeRepo->findAllTypes();
	}

	protected function table(): string
	{
		return 'inventory_tb';
	}
	public function findById(int $id): ?Inventory
	{
		$stmt = $this->pdo->prepare("SELECT * FROM inventory_tb WHERE id=:id AND adminId = :adminId");
		$stmt->execute([':id' => $id, ':adminId' => $this->adminId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row) return null;

		return $this->mapToInventory($row);
	}
	public function findAll(string $sortColumn = 'lastUpdated', string $sortOrder = 'DESC'): array
	{
		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'lastUpdated';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';

		$stmt = $this->pdo->prepare("SELECT * FROM inventory_tb WHERE adminId = :adminId ORDER BY $sortColumn $sortOrder");
		$stmt->execute([':adminId' => $this->adminId]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$inventories = array_map(fn($row) => $this->mapToInventory($row), $rows);

		if ($sortColumn === 'status') {
			$priority = ['Out of Stock' => 0, 'Low Stock' => 1, 'Available' => 2];
			usort($inventories, function ($a, $b) use ($priority, $sortOrder) {
				$result = $priority[$a->getStatus()] <=> $priority[$b->getStatus()];
				return strtoupper($sortOrder) === 'ASC' ? $result : -$result;
			});
		}

		return $inventories;
	}

	public function findInventoriesByMonth(int $month, int $year, string $sortColumn = 'lastUpdated', string $sortOrder = 'DESC', ?string $productType = null): array
	{

		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'lastUpdated';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';
		$productTypeClause = ($productType && in_array($productType, $this->allowedProductTypes)) ? "AND productType = :productType" : "";

		$stmt = $this->pdo->prepare("
			SELECT * FROM inventory_tb
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
		return array_map(fn($row) => $this->mapToInventory($row), $rows);
	}
	public function findInventoriesByMonthWeek(int $month, int $week, int $year, string $sortColumn = 'lastUpdated', string $sortOrder = 'DESC', ?string $productType = null): array
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
		SELECT * FROM inventory_tb 
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

		return array_map(fn($row) => $this->mapToInventory($row), $rows);
	}
	public function findInventoriesByToday(string $sortColumn = 'lastUpdated', string $sortOrder = 'DESC', ?string $productType = null): array
	{
		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'lastUpdated';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';
		$productTypeClause = ($productType && in_array($productType, $this->allowedProductTypes)) ? "AND productType = :productType" : "";

		$stmt = $this->pdo->prepare("
			SELECT * FROM inventory_tb 
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
		return array_map(fn($row) => $this->mapToInventory($row), $rows);
	}
	public function findByProductType(string $productType, string $sortColumn = 'lastUpdated', string $sortOrder = 'DESC'): array
	{
		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'lastUpdated';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';
		if (!in_array($productType, $this->allowedProductTypes)) return [];

		$stmt = $this->pdo->prepare("
			SELECT * FROM inventory_tb 
			WHERE adminId = :adminId 
			AND productType = :productType
			ORDER BY $sortColumn $sortOrder
		    ");
		$stmt->execute([
			':adminId'     => $this->adminId,
			':productType' => $productType,
		]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => $this->mapToInventory($row), $rows);
	}
	public function findByStatus(string $status, string $sortColumn = 'lastUpdated', string $sortOrder = 'DESC'): array
	{
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';
		if (!in_array($status, $this->allowedStockStatuses)) return [];

		$stmt = $this->pdo->prepare("
			SELECT * FROM inventory_tb
			WHERE adminId = :adminId
			AND status = :status
			ORDER BY $sortColumn $sortOrder
		    ");
		$stmt->execute([':adminId' => $this->adminId, ':status' => $status]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => $this->mapToInventory($row), $rows);
	}

	public function searchInventory(string $productName): ?array
	{
		$stmt = $this->pdo->prepare("
			SELECT * FROM inventory_tb 
			WHERE name LIKE :name AND adminId = :adminId
		");

		$stmt->execute([
			':name' => '%' . $productName . '%',
			':adminId' => $this->adminId,
		]);

		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if (!$rows) return null;

		return array_map(fn($row) => $this->mapToInventory($row), $rows);
	}


	public function processSales(int $id, int $amount, float $price): void
	{
		$item = $this->findById($id);
		if (!$item) throw new Exception("Item not found");
		if ($item->getQuantity() < $amount) throw new Exception("Not enough stock");

		$pricePerUnit = $price;
		$salesAmount = $pricePerUnit * $amount;
		$currentDate = date('Y-m-d');



		$this->pdo->beginTransaction();
		try {
			$stmt = $this->pdo->prepare("
			SELECT id, itemsSold, sale FROM sales_tb
			WHERE inventoryId = :inventoryId
			AND DATE(dateSold) = :currentDate AND adminId = :adminId");
			$stmt->execute([
				':inventoryId' => $id,
				':currentDate' => $currentDate,
				':adminId' => $this->adminId
			]);
			$existingSale = $stmt->fetch(PDO::FETCH_ASSOC);
			$stmt = $this->pdo->prepare("
				UPDATE inventory_tb
				SET quantity = quantity - :amount
				WHERE id = :id
				AND adminId = :adminId
				");
			$stmt->execute([
				':amount' => $amount,
				':id' => $id,
				':adminId' => $this->adminId
			]);

			if ($existingSale) {
				$stmt = $this->pdo->prepare("
					UPDATE sales_tb
					SET itemsSold = itemsSold + :amount,
					sale = sale + :salesAmount
					WHERE id = :saleId
					AND adminId = :adminId
					");
				$stmt->execute([
					':amount' => $amount,
					':salesAmount' => $salesAmount,
					':saleId' => $existingSale['id'],
					':adminId' => $this->adminId
				]);

				$stmt = $this->pdo->prepare("
					UPDATE capital_transactions_tb
					SET amount = amount + :salesAmount
					WHERE saleId = :saleId
					AND adminId = :adminId
					");
				$stmt->execute([
					':salesAmount' => $salesAmount,
					':saleId' => $existingSale['id'],
					':adminId' => $this->adminId,
				]);
			} else {
				$stmt = $this->pdo->prepare("
					INSERT INTO sales_tb (inventoryId, price, name, productType, itemsSold, sale, dateSold, adminId)
					VALUES (:inventoryId, :price, :name, :productType, :amount, :sale, NOW(), :adminId)
					");
				$stmt->execute([
					':inventoryId' => $id,
					':price' => $item->getPrice(),
					':name' => $item->getProductName(),
					':productType' => $item->getProductType(),
					':amount' => $amount,
					':sale' => $salesAmount,
					':adminId' => $this->adminId

				]);
				$newSaleId = $this->pdo->lastInsertId();
				$stmt = $this->pdo->prepare("
			INSERT INTO capital_transactions_tb (adminId, type, amount, description, saleId)
			VALUES (:adminId, 'sale', :amount, :description, :saleId);
			");
				$stmt->execute([
					':adminId' => $this->adminId,
					':amount' => $salesAmount,
					':description' => $item->getProductName(),
					':saleId' => $newSaleId,
				]);
			}
			$this->pdo->commit();
		} catch (Exception $e) {
			$this->pdo->rollBack();
			throw $e;
		}
	}


	public function save(Inventory $inventory): void
	{
		$stmt = $this->pdo->prepare("
			INSERT INTO inventory_tb (name, productType, quantity, price, adminId) 
			VALUES (:name, :productType, :quantity, :price, :adminId)");

		$stmt->execute([
			':name' => $inventory->getProductName(),
			':productType' => $inventory->getProductType(),
			':quantity' => $inventory->getQuantity(),
			':price' => $inventory->getPrice(),
			':adminId' => $this->adminId

		]);
	}

	public function update(int $id, Inventory $inventory): void
	{
		$this->pdo->beginTransaction();
		try {
			$stmt = $this->pdo->prepare("
			UPDATE inventory_tb
			SET name = :name,
				productType = :productType,
				quantity = :quantity,
				price = :price
				WHERE id = :id AND
				adminId = :adminId
			");

			$stmt->execute([
				':id' => $id,
				':productType' => $inventory->getProductType(),
				':name' => $inventory->getProductName(),
				':quantity' => $inventory->getQuantity(),
				':price' => $inventory->getPrice(),
				':adminId' => $this->adminId
			]);
			$this->pdo->commit();
		} catch (Exception $e) {
			$this->pdo->rollBack();
			throw $e;
		}
	}

	public function restock(int $inventoryId, int $quantity): void
	{
		$stmt = $this->pdo->prepare("
				UPDATE inventory_tb
				SET quantity = quantity + :quantity
				WHERE id = :id AND adminId = :adminId
			    ");
		$stmt->execute([
			':quantity' => $quantity,
			':id'       => $inventoryId,
			':adminId'  => $this->adminId,
		]);
	}
	public function transferToArchive(int $id, Inventory $inventory): void
	{
		$stmt = $this->pdo->prepare("
			INSERT INTO archives_tb (name, productType, quantity, price, adminId) 
			VALUES (:name, :productType, :quantity, :price, :adminId)");
		$stmt->execute([
			'name' => $inventory->getProductName(),
			':productType' => $inventory->getProductType(),
			'quantity' => $inventory->getQuantity(),
			'price' => $inventory->getPrice(),
			'adminId' => $this->adminId
		]);
		$this->delete($id);
	}

	public function totalInventoryValue(string $filter = 'all', ?int $month = null, ?int $week = null, ?int $year = null, ?string $productType = null, string $search = '', ?string $status = null): float
	{
		$productTypeClause = ($productType && in_array($productType, $this->allowedProductTypes)) ? "AND productType = :productType" : "";
		$searchClause      = $search !== '' ? "AND name LIKE :search" : "";

		$dateClause = "";
		if ($filter === 'now') {
			$dateClause = "AND DATE(lastUpdated) = CURDATE()";
		} elseif ($filter === 'month' && $month && $year) {
			$dateClause = "AND MONTH(lastUpdated) = :month AND YEAR(lastUpdated) = :year";
		} elseif ($filter === 'week' && $month && $week && $year) {
			$ranges = [1 => [1, 7], 2 => [8, 14], 3 => [15, 21], 4 => [22, 31]];
			$dateClause = "AND MONTH(lastUpdated) = :month AND YEAR(lastUpdated) = :year AND DAY(lastUpdated) BETWEEN :start AND :end";
		}

		$sql = "SELECT * FROM inventory_tb WHERE adminId = :adminId $productTypeClause $searchClause $dateClause";

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
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$inventories = array_map(fn($row) => $this->mapToInventory($row), $rows);

		if ($status !== null && in_array($status, $this->allowedStockStatuses)) {
			$inventories = array_filter($inventories, fn($i) => $i->getStatus() === $status);
		}

		return array_reduce(
			$inventories,
			fn($carry, $i) => $carry + ($i->getQuantity() * $i->getPrice()),
			0.0
		);
	}

	public function paginate(int $page = 1, int $limit = 10, string $sortColumn = 'lastUpdated', string $sortOrder = 'DESC', string $search = '', string $productType = '', string $filter = 'all', ?int $month = null, ?int $week = null, ?int $year = null, ?string $status = null): array
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
			SELECT * FROM inventory_tb
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
		$inventories = array_map(fn($row) => $this->mapToInventory($row), $rows);

		if ($status !== null && in_array($status, $this->allowedStockStatuses)) {
			$inventories = array_values(array_filter($inventories, fn($i) => $i->getStatus() === $status));
		}

		return $inventories;
	}

	public function countFiltered(string $search = '', string $productType = '', string $filter = 'all', ?int $month = null, ?int $week = null, ?int $year = null, ?string $status = null): int
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
				SELECT * FROM inventory_tb
				WHERE adminId = :adminId
				$searchClause
				$typeClause
				$dateClause
			    ");
		$stmt->execute($params);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$inventories = array_map(fn($row) => $this->mapToInventory($row), $rows);

		if ($status !== null && in_array($status, $this->allowedStockStatuses)) {
			$inventories = array_filter($inventories, fn($i) => $i->getStatus() === $status);
		}

		return count($inventories);
	}

	private function mapToInventory(array $row): Inventory
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
