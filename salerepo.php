<?php
include_once("database.php");
include_once("sale.php");
include_once("baserepo.php");
class SalesRepo extends BaseRepository
{
	protected function table(): string
	{
		return 'sales_tb';
	}

	public function findById(int $id): ?Sale
	{
		$stmt = $this->pdo->prepare("SELECT * FROM sales_tb WHERE id = :id AND adminId = :adminId");
		$stmt->execute([':id' => $id, ':adminId' => $this->adminId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$row) return null;

		return new Sale(
			$row['name'],
			$row['itemsSold'],
			$row['sale'],
			new DateTime($row['dateSold']),
			$row['inventoryId'],
			$row['adminId'],
			$row['id'],
		);
	}

	public function findAll(string $sortColumn = 'dateSold', string $sortOrder = 'DESC'): array
	{
		$allowedColumns = ['name', 'itemsSold', 'sale', 'dateSold', 'inventoryId'];
		$allowedOrders = ['ASC', 'DESC'];

		if (!in_array($sortColumn, $allowedColumns)) $sortColumn = 'dateSold';
		if (!in_array(strtoupper($sortOrder), $allowedOrders)) $sortOrder = 'DESC';

		$stmt = $this->pdo->query("SELECT * FROM sales_tb  WHERE adminId = $this->adminId ORDER BY $sortColumn $sortOrder ");
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => new Sale(
			$row['name'],
			$row['itemsSold'],
			$row['sale'],
			new DateTime($row['dateSold']),
			$row['inventoryId'],
			$row['adminId'],
			$row['id'],
		), $rows);
	}
	public function findToday(string $sortColumn = 'dateSold', string $sortOrder = 'DESC'): array
	{
		$allowedColumns = ['name', 'itemsSold', 'sale', 'dateSold', 'inventoryId'];
		$allowedOrders = ['ASC', 'DESC'];
		if (!in_array($sortColumn, $allowedColumns)) $sortColumn = 'dateSold';
		if (!in_array(strtoupper($sortOrder), $allowedOrders)) $sortOrder = 'DESC';

		$stmt = $this->pdo->prepare("
        SELECT * FROM sales_tb 
        WHERE adminId = :adminId 
        AND DATE(dateSold) = CURDATE()
        ORDER BY $sortColumn $sortOrder
    ");
		$stmt->execute([':adminId' => $this->adminId]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => new Sale(
			$row['name'],
			$row['itemsSold'],
			$row['sale'],
			new DateTime($row['dateSold']),
			$row['inventoryId'],
			$row['adminId'],
			$row['id'],
		), $rows);
	}



	public function totalSales(string $filter, ?int $month = null, ?int $week = null, ?int $year = null): float
	{
		$sql = "SELECT SUM(sale) as total FROM sales_tb WHERE adminId = :adminId";
		$params = ['adminId' => $this->adminId];

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

	public function findSalesByMonthWeek(int $month, int $week, int $year, string $sortColumn = 'dateSold', string $sortOrder = 'DESC'): array
	{
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
		AND DAY(dateSold) BETWEEN :start AND :end AND adminId = :adminId
		ORDER BY $sortColumn $sortOrder
	");

		$stmt->execute([
			'month' => $month,
			'year'  => $year,
			'start' => $start,
			'end'   => $end,
			':adminId'   => $this->adminId,
		]);

		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => new Sale(
			$row['name'],
			$row['itemsSold'],
			$row['sale'],
			new DateTime($row['dateSold']),
			$row['inventoryId'],
			$row['adminId'],
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
        AND YEAR(dateSold) = :year AND adminId = :adminId
        ORDER BY $sortColumn $sortOrder
    ");
		$stmt->execute([
			':month' => $month,
			':year' => $year,
			':adminId' => $this->adminId,
		]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => new Sale(
			$row['name'],
			$row['itemsSold'],
			$row['sale'],
			new DateTime($row['dateSold']),
			$row['inventoryId'],
			$row['adminId'],
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
		AND DAY(dateSold) BETWEEN :start AND :end AND adminId = :adminId
		ORDER BY name ASC
	");

		$stmt->execute([
			'month' => $month,
			'year'  => $year,
			'start' => $start,
			'end'   => $end,
			':adminId'   => $this->adminId,
		]);

		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => new Sale(
			$row['name'],
			$row['itemsSold'],
			$row['sale'],
			new DateTime($row['dateSold']),
			$row['inventoryId'],
			$row['adminId'],
			$row['id'],
		), $rows);
	}

	public function exportSalesToday(): array
	{

		$stmt = $this->pdo->prepare("
        SELECT * FROM sales_tb
			WHERE DATE(dateSold) = CURDATE()
			AND
		       	adminId = :adminId
	ORDER BY name ASC
    ");
		$stmt->execute([
			':adminId' => $this->adminId,
		]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => new Sale(
			$row['name'],
			$row['itemsSold'],
			$row['sale'],
			new DateTime($row['dateSold']),
			$row['inventoryId'],
			$row['adminId'],
			$row['id'],
		), $rows);
	}

	public function save(Sale $sale): void
	{
		$stmt = $this->pdo->prepare("INSERT INTO sales_tb (inventoryId, name, itemsSold, sale, dateSold, adminId)
			VALUES (:inventoryId, :name, :itemsSold, :sale, :dateSold, :adminId)");
		$stmt->execute([
			':inventoryId' => $sale->getInventoryId(),
			':name' => $sale->getProductName(),
			':itemsSold' => $sale->getItemsSold(),
			':sale' => $sale->getSale(),
			':dateSold' => $sale->getDate()->format('Y-m-d'),
			':adminId' => $this->adminId,

		]);
	}

	public function paginate(int $page = 1, int $limit = 10, string $sortColumn = 'dateSold', string $sortOrder = 'DESC', ?string $filter = 'all', ?int $month = null, ?int $week = null, ?int $year = null): array
	{
		$offset = ($page - 1) * $limit;
		$allowedColumns = ['name', 'itemsSold', 'sale', 'dateSold', 'inventoryId'];
		$allowedOrders = ['ASC', 'DESC'];

		if (!in_array($sortColumn, $allowedColumns)) $sortColumn = 'dateSold';
		if (!in_array(strtoupper($sortOrder), $allowedOrders)) $sortOrder = 'DESC';

		$filterClause = '';
		$params = [':adminId' => $this->adminId];

		switch ($filter) {
			case 'now':
				$filterClause = "AND DATE(dateSold) = CURDATE()";
				break;
			case 'month':
				$filterClause = "AND MONTH(dateSold) = :month AND YEAR(dateSold) = :year";
				$params[':month'] = $month;
				$params[':year']  = $year;
				break;
			case 'week':
				$ranges = [1 => [1, 7], 2 => [8, 14], 3 => [15, 21], 4 => [22, 31]];
				$start = $ranges[$week][0] ?? 1;
				$end   = $ranges[$week][1] ?? 7;
				$filterClause = "AND MONTH(dateSold) = :month AND YEAR(dateSold) = :year AND DAY(dateSold) BETWEEN :start AND :end";
				$params[':month'] = $month;
				$params[':year']  = $year;
				$params[':start'] = $start;
				$params[':end']   = $end;
				break;
		}

		$stmt = $this->pdo->prepare("
        SELECT * FROM sales_tb
        WHERE adminId = :adminId
        $filterClause
        ORDER BY $sortColumn $sortOrder
        LIMIT :limit OFFSET :offset
    ");

		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value);
		}
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
		$stmt->execute();

		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => new Sale(
			$row['name'],
			$row['itemsSold'],
			$row['sale'],
			new DateTime($row['dateSold']),
			$row['inventoryId'],
			$row['adminId'],
			$row['id'],
		), $rows);
	}

	public function countFiltered(?string $filter = 'all', ?int $month = null, ?int $week = null, ?int $year = null): int
	{
		$filterClause = '';
		$params = [':adminId' => $this->adminId];

		switch ($filter) {
			case 'now':
				$filterClause = "AND DATE(dateSold) = CURDATE()";
				break;
			case 'month':
				$filterClause = "AND MONTH(dateSold) = :month AND YEAR(dateSold) = :year";
				$params[':month'] = $month;
				$params[':year']  = $year;
				break;
			case 'week':
				$ranges = [1 => [1, 7], 2 => [8, 14], 3 => [15, 21], 4 => [22, 31]];
				$start = $ranges[$week][0] ?? 1;
				$end   = $ranges[$week][1] ?? 7;
				$filterClause = "AND MONTH(dateSold) = :month AND YEAR(dateSold) = :year AND DAY(dateSold) BETWEEN :start AND :end";
				$params[':month'] = $month;
				$params[':year']  = $year;
				$params[':start'] = $start;
				$params[':end']   = $end;
				break;
		}

		$stmt = $this->pdo->prepare("
        SELECT COUNT(*) FROM sales_tb
        WHERE adminId = :adminId
        $filterClause
    ");
		$stmt->execute($params);
		return (int) $stmt->fetchColumn();
	}
}
