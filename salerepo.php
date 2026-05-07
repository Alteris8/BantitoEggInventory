<?php
include_once("database.php");
include_once("sale.php");
class SalesRepo
{
	private PDO $pdo;
	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
	}
	public function findById(int $id): ?Sale
	{
		$stmt = $this->pdo->prepare("SELECT * FROM sales_tb WHERE id = :id");
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$row) return null;

		return new Sale(
			$row['name'],
			$row['itemsSold'],
			$row['sale'],
			new DateTime($row['dateSold']),
			$row['inventoryId'],
			$row['id'],
		);
	}

	public function findAll(string $sortColumn = 'dateSold', string $sortOrder = 'DESC'): array
	{
		$allowedColumns = ['name', 'itemsSold', 'sale', 'dateSold', 'inventoryId'];
		$allowedOrders = ['ASC', 'DESC'];

		if (!in_array($sortColumn, $allowedColumns)) $sortColumn = 'dateSold';
		if (!in_array(strtoupper($sortOrder), $allowedOrders)) $sortOrder = 'DESC';

		$stmt = $this->pdo->query("SELECT * FROM sales_tb ORDER BY $sortColumn $sortOrder");
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => new Sale(
			$row['name'],
			$row['itemsSold'],
			$row['sale'],
			new DateTime($row['dateSold']),
			$row['inventoryId'],
			$row['id'],
		), $rows);
	}

	public function exportAll(): array
	{
		$stmt = $this->pdo->query("SELECT * FROM sales_tb");
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => new Sale(
			$row['name'],
			$row['itemsSold'],
			$row['sale'],
			new DateTime($row['dateSold']),
			$row['inventoryId'],
			$row['id'],
		), $rows);
	}

	public function totalSales(string $filter, ?int $month = null, ?int $week = null, ?int $year = null): float
	{
		$sql = "SELECT SUM(sale) as total FROM sales_tb WHERE 1=1";
		$params = [];

		if ($filter === 'month') {
			$sql .= " AND MONTH(dateSold) = :month AND YEAR(dateSold) = :year";
			$params['month'] = $month;
			$params['year'] = $year;
		}

		if ($filter === 'week') {
			$ranges = [
				1 => [1, 7],
				2 => [8, 14],
				3 => [15, 21],
				4 => [22, 31],
			];

			$sql .= " AND MONTH(dateSold) = :month AND YEAR(dateSold) = :year AND DAY(dateSold) BETWEEN :start AND :end";

			$params['month'] = $month;
			$params['year'] = $year;
			$params['start'] = $ranges[$week][0];
			$params['end'] = $ranges[$week][1];
		}

		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($params);

		return (float)($stmt->fetchColumn() ?? 0);
	}

	public function findSalesByMonthWeek(
		int $month,
		int $week,
		int $year,
		string $sortColumn = 'dateSold',
		string $sortOrder = 'DESC'
	): array {
		$allowedColumns = ['name', 'itemsSold', 'sale', 'dateSold', 'inventoryId'];
		$allowedOrders = ['ASC', 'DESC'];
		if (!in_array($sortColumn, $allowedColumns)) $sortColumn = 'dateSold';
		if (!in_array(strtoupper($sortOrder), $allowedOrders)) $sortOrder = 'DESC';

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
		AND DAY(dateSold) BETWEEN :start AND :end
		ORDER BY $sortColumn $sortOrder
	");

		$stmt->execute([
			'month' => $month,
			'year'  => $year,
			'start' => $start,
			'end'   => $end,
		]);

		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => new Sale(
			$row['name'],
			$row['itemsSold'],
			$row['sale'],
			new DateTime($row['dateSold']),
			$row['inventoryId'],
			$row['id'],

		), $rows);
	}

	public function findSalesByMonth(int $month, int $year, string $sortColumn = 'dateSold', string $sortOrder = 'DESC'): array
	{
		$allowedColumns = ['name', 'itemsSold', 'sale', 'dateSold', 'inventoryId'];
		$allowedOrders = ['ASC', 'DESC'];

		if (!in_array($sortColumn, $allowedColumns)) $sortColumn = 'dateSold';
		if (!in_array(strtoupper($sortOrder), $allowedOrders)) $sortOrder = 'DESC';


		$stmt = $this->pdo->prepare("
        SELECT * FROM sales_tb
        WHERE MONTH(dateSold) = :month
        AND YEAR(dateSold) = :year
        ORDER BY $sortColumn $sortOrder
    ");
		$stmt->execute([
			':month' => $month,
			':year' => $year,
		]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => new Sale(
			$row['name'],
			$row['itemsSold'],
			$row['sale'],
			new DateTime($row['dateSold']),
			$row['inventoryId'],
			$row['id'],
		), $rows);
	}

	public function exportWeeklySales(int $month, int $week, int $year): array
	{

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
		AND DAY(dateSold) BETWEEN :start AND :end
		ORDER BY name ASC
	");

		$stmt->execute([
			'month' => $month,
			'year'  => $year,
			'start' => $start,
			'end'   => $end,
		]);

		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => new Sale(
			$row['name'],
			$row['itemsSold'],
			$row['sale'],
			new DateTime($row['dateSold']),
			$row['inventoryId'],
			$row['id'],
		), $rows);
	}

	public function exportMonthlySales(int $month, int $year): array
	{

		$stmt = $this->pdo->prepare("
        SELECT * FROM sales_tb
        WHERE MONTH(dateSold) = :month
        AND YEAR(dateSold) = :year
	ORDER BY name ASC
    ");
		$stmt->execute([
			':month' => $month,
			':year' => $year,
		]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => new Sale(
			$row['name'],
			$row['itemsSold'],
			$row['sale'],
			new DateTime($row['dateSold']),
			$row['inventoryId'],
			$row['id'],
		), $rows);
	}

	public function save(Sale $sale): void
	{
		$stmt = $this->pdo->prepare("INSERT INTO sales_tb (inventoryId, name, itemsSold, sale, dateSold)
			VALUES (:inventoryId, :name, :itemsSold, :sale, :dateSold)");
		$stmt->execute([
			':inventoryId' => $sale->getInventoryId(),
			':name' => $sale->getProductName(),
			':itemsSold' => $sale->getItemsSold(),
			':sale' => $sale->getSale(),
			':dateSold' => $sale->getDate()->format('Y-m-d'),

		]);
	}

	public function delete(int $id): void
	{
		$stmt = $this->pdo->prepare("
			DELETE FROM sales_tb WHERE id = :id
			");

		$stmt->execute([
			':id' => $id,
		]);
	}
}
