<?php
include_once("database.php");
include_once("baserepo.php");
include_once("capitaltransaction.php");

class CapitalTransactionRepo extends BaseRepository
{
	protected function table(): string
	{
		return 'capital_transactions_tb';
	}


	public function findById(int $id): ?CapitalTransaction
	{
		$stmt = $this->pdo->prepare("SELECT * FROM capital_transactions_tb WHERE id=:id AND adminId = :adminId");
		$stmt->execute([':id' => $id, ':adminId' => $this->adminId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row) return null;

		return new CapitalTransaction(
			$row['type'],
			$row['amount'],
			$row['description'],
			new DateTime($row['createdAt']),
			(int) $row['id'],
			(int) $row['adminId'],
			(int) $row['saleId'],
			(int) $row['inventoryId'],
			(int) $row['quantity'],
		);
	}
	public function findAll(string $sortColumn = 'createdAt', string $sortOrder = 'DESC', ?string $type = null): array
	{
		$allowedColumns = ['amount', 'description', 'createdAt'];
		$allowedOrders = ['ASC', 'DESC'];
		$allowedTypes = ['sale', 'expense', 'restock', 'deposit'];



		if (!in_array($sortColumn, $allowedColumns)) $sortColumn = 'createdAt';
		if (!in_array(strtoupper($sortOrder), $allowedOrders)) $sortOrder = 'DESC';
		$typeClause = ($type && in_array($type, $allowedTypes)) ? "AND type = :type" : "";

		$stmt = $this->pdo->prepare("
			SELECT * FROM capital_transactions_tb
			WHERE adminId = :adminId $typeClause ORDER BY $sortColumn $sortOrder
			");

		$params = [':adminId' => $this->adminId];
		if ($type && in_array($type, $allowedTypes)) $params[':type'] = $type;
		$stmt->execute($params);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => new CapitalTransaction(
			$row['type'],
			$row['amount'],
			$row['description'],
			new DateTime($row['createdAt']),
			(int) $row['id'],
			(int) $row['adminId'],
			(int) $row['saleId'],
			(int) $row['inventoryId'],
			(int) $row['quantity'],
		), $rows);
	}

	public function findCapitalTransactionsByMonth(int $month, int $year, string $sortColumn = 'createdAt', string $sortOrder = 'DESC', ?string $type = null): array
	{

		$allowedColumns = ['amount', 'description', 'createdAt'];
		$allowedOrders = ['ASC', 'DESC'];
		$allowedTypes = ['sale', 'expense', 'restock', 'deposit'];

		if (!in_array($sortColumn, $allowedColumns)) $sortColumn = 'createdAt';
		if (!in_array(strtoupper($sortOrder), $allowedOrders)) $sortOrder = 'DESC';
		$typeClause = ($type && in_array($type, $allowedTypes)) ? "AND type = :type" : "";

		$stmt = $this->pdo->prepare("
        SELECT * FROM capital_transactions_tb
        WHERE MONTH(createdAt) = :month
        AND YEAR(createdAt) = :year 
        AND adminId = :adminId
        $typeClause
        ORDER BY $sortColumn $sortOrder
    ");

		$params = [':month' => $month, ':year' => $year, ':adminId' => $this->adminId];
		if ($type && in_array($type, $allowedTypes)) $params[':type'] = $type;

		$stmt->execute($params);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => new CapitalTransaction(
			$row['type'],
			$row['amount'],
			$row['description'],
			new DateTime($row['createdAt']),
			(int) $row['id'],
			(int) $row['adminId'],
			(int) $row['saleId'],
			(int) $row['inventoryId'],
			(int) $row['quantity'],
		), $rows);
	}



	public function findCapitalTransactionsByMonthWeek(int $month, int $week, int $year, string $sortColumn = 'createdAt', string $sortOrder = 'DESC', ?string $type = null): array
	{

		$allowedColumns = ['amount', 'description', 'createdAt'];
		$allowedOrders = ['ASC', 'DESC'];
		$allowedTypes = ['sale', 'expense', 'restock', 'deposit'];

		if (!in_array($sortColumn, $allowedColumns)) $sortColumn = 'createdAt';
		if (!in_array(strtoupper($sortOrder), $allowedOrders)) $sortOrder = 'DESC';

		$typeClause = ($type && in_array($type, $allowedTypes)) ? "AND type = :type" : "";

		$ranges = [
			1 => [1, 7],
			2 => [8, 14],
			3 => [15, 21],
			4 => [22, 31],
		];

		$start = $ranges[$week][0];
		$end   = $ranges[$week][1];

		$stmt = $this->pdo->prepare("
		SELECT * FROM capital_transactions_tb 
		WHERE MONTH(createdAt) = :month
		AND YEAR(createdAt) = :year
		AND DAY(createdAt) BETWEEN :start AND :end AND adminId = :adminId $typeClause
		ORDER BY $sortColumn $sortOrder
	");

		$params = [
			':month' => $month,
			':year'  => $year,
			':start' => $start,
			':end'   => $end,
			':adminId'   => $this->adminId,
		];
		if ($type && in_array($type, $allowedTypes)) $params[':type'] = $type;

		$stmt->execute($params);

		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => new CapitalTransaction(
			$row['type'],
			$row['amount'],
			$row['description'],
			new DateTime($row['createdAt']),
			(int) $row['id'],
			(int) $row['adminId'],
			(int) $row['saleId'],
			(int) $row['inventoryId'],
			(int) $row['quantity'],
		), $rows);
	}


	public function findCapitalTransactionsByToday(string $sortColumn = 'createdAt', string $sortOrder = 'DESC', ?string $type = null): array
	{
		$allowedColumns = ['amount', 'description', 'createdAt'];
		$allowedOrders = ['ASC', 'DESC'];
		$allowedTypes = ['sale', 'expense', 'restock', 'deposit'];

		if (!in_array($sortColumn, $allowedColumns)) $sortColumn = 'createdAt';
		if (!in_array(strtoupper($sortOrder), $allowedOrders)) $sortOrder = 'DESC';
		$typeClause = ($type && in_array($type, $allowedTypes)) ? "AND type = :type" : "";

		$stmt = $this->pdo->prepare("
        SELECT * FROM capital_transactions_tb 
        WHERE adminId = :adminId 
        AND DATE(createdAt) = CURDATE() $typeClause
        ORDER BY $sortColumn $sortOrder
    ");
		$params = [
			':adminId' => $this->adminId
		];

		if ($type && in_array($type, $allowedTypes)) {
			$params[':type'] = $type;
		}

		$stmt->execute($params);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => new CapitalTransaction(
			$row['type'],
			$row['amount'],
			$row['description'],
			new DateTime($row['createdAt']),
			(int) $row['id'],
			(int) $row['adminId'],
			(int) $row['saleId'],
			(int) $row['inventoryId'],
			(int) $row['quantity'],
		), $rows);
	}
	public function findByType(string $type, string $sortColumn = 'createdAt', string $sortOrder = 'DESC'): array
	{
		$allowedColumns = ['amount', 'description', 'createdAt'];
		$allowedOrders = ['ASC', 'DESC'];
		$allowedTypes = ['sale', 'expense', 'restock', 'deposit'];

		if (!in_array($sortColumn, $allowedColumns)) $sortColumn = 'createdAt';
		if (!in_array(strtoupper($sortOrder), $allowedOrders)) $sortOrder = 'DESC';
		if (!in_array($type, $allowedTypes)) return [];

		$stmt = $this->pdo->prepare("
        SELECT * FROM capital_transactions_tb 
        WHERE adminId = :adminId 
        AND type = :type
        ORDER BY $sortColumn $sortOrder
    ");
		$stmt->execute([
			':adminId' => $this->adminId,
			':type'    => $type,
		]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => new CapitalTransaction(
			$row['type'],
			$row['amount'],
			$row['description'],
			new DateTime($row['createdAt']),
			(int) $row['id'],
			(int) $row['adminId'],
			(int) $row['saleId'],
			(int) $row['inventoryId'],
			(int) $row['quantity'],
		), $rows);
	}

	public function exportWeeklyCapitalTransactions(int $month, int $week, int $year): array
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
		SELECT * FROM capital_transactions_tb 
		WHERE MONTH(createdAt) = :month
		AND YEAR(createdAt) = :year
		AND DAY(createdAt) BETWEEN :start AND :end AND adminId = :adminId
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

		return array_map(fn($row) => new CapitalTransaction(
			$row['type'],
			$row['amount'],
			$row['description'],
			new DateTime($row['createdAt']),
			(int) $row['id'],
			(int) $row['adminId'],
			(int) $row['saleId'],
			(int) $row['inventoryId'],
			(int) $row['quantity'],
		), $rows);
	}
	public function exportCapitalTransactionsToday(): array
	{

		$stmt = $this->pdo->prepare("
			SELECT * FROM capital_transactions_tb
		   	WHERE DATE(createdAt) = CURDATE()
			AND
		       	adminId = :adminId
	ORDER BY name ASC
    ");
		$stmt->execute([
			':adminId' => $this->adminId,
		]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => new CapitalTransaction(
			$row['type'],
			$row['amount'],
			$row['description'],
			new DateTime($row['createdAt']),
			(int) $row['id'],
			(int) $row['adminId'],
			(int) $row['saleId'],
			(int) $row['inventoryId'],
			(int) $row['quantity'],
		), $rows);
	}
	public function exportAll(): array
	{
		$stmt = $this->pdo->prepare("SELECT * FROM capital_transactions_tb WHERE adminId = :adminId");
		$stmt->execute([
			':adminId' => $this->adminId
		]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => new CapitalTransaction(
			$row['type'],
			$row['amount'],
			$row['description'],
			new DateTime($row['createdAt']),
			(int) $row['id'],
			(int) $row['adminId'],
			(int) $row['saleId'],
			(int) $row['inventoryId'],
		), $rows);
	}
	public function exportToday(): array
	{
		$stmt = $this->pdo->prepare("SELECT * FROM capital_transactions_tb WHERE DATE(createdAt) = CURDATE() AND adminId = :adminId");
		$stmt->execute([
			':adminId' => $this->adminId
		]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array_map(fn($row) => new CapitalTransaction(
			$row['type'],
			$row['amount'],
			$row['description'],
			new DateTime($row['createdAt']),
			(int) $row['id'],
			(int) $row['adminId'],
			(int) $row['saleId'],
			(int) $row['inventoryId'],
			(int) $row['quantity'],
		), $rows);
	}

	public function save(CapitalTransaction $capitalTransaction): void
	{
		$stmt = $this->pdo->prepare("
			INSERT INTO capital_transactions_tb (type, amount, description, adminId, saleId) 
			VALUES (:type, :amount, :description, :adminId, :saleId) ");

		$stmt->execute([
			':type' => $capitalTransaction->getType(),
			':amount' => $capitalTransaction->getAmount(),
			':description' => $capitalTransaction->getDescription(),
			':adminId' => $this->adminId,
			':saleId' => $capitalTransaction->getSaleId(),
		]);

	}

	public function update(int $id, CapitalTransaction $capitalTransaction): void
	{
		
		$stmt = $this->pdo->prepare("
			UPDATE capital_transactions_tb
			SET type = :type,
				amount = :amount,
				description = :description
			WHERE id = :id AND adminId = :adminId
			");

		$stmt->execute([
			':id' => $id,
			':type' => $capitalTransaction->getType(),
			':amount' => $capitalTransaction->getAmount(),
			':description' => $capitalTransaction->getDescription(),
			':adminId' => $this->adminId,
		]);
		
	}


	public function calculateIncomeSummary(): array
	{
		$stmt = $this->pdo->prepare("
        SELECT 
            SUM(CASE WHEN type = 'sale'    THEN amount ELSE 0 END) AS totalSales,
            SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) AS totalDeposits,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS totalExpenses,
            SUM(CASE WHEN type = 'restock' THEN amount ELSE 0 END) AS totalRestocks
        FROM capital_transactions_tb
        WHERE adminId = :adminId
    ");
		$stmt->execute([':adminId' => $this->adminId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$transactionTotalRows = [
			'totalSales'    => (float) $row['totalSales'],
			'totalDeposits' => (float) $row['totalDeposits'],
			'totalExpenses' => (float) $row['totalExpenses'],
			'totalRestocks' => (float) $row['totalRestocks'],
			'netIncome'     => ((float)$row['totalSales'] + (float)$row['totalDeposits'])
				- ((float)$row['totalExpenses'] + (float)$row['totalRestocks']),
		];



		$stmt2 = $this->pdo->prepare("
		UPDATE capital_tb c
		JOIN (
			SELECT 
				adminId,
				COALESCE(
					SUM(CASE WHEN type = 'sale' THEN amount ELSE 0 END)
					+
					SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END)
					-
					SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END)
					-
					SUM(CASE WHEN type = 'restock' THEN amount ELSE 0 END)
				,0) AS netIncome
			FROM capital_transactions_tb
			WHERE adminId = :adminId_sub
			GROUP BY adminId
		) t ON c.adminId = t.adminId
		SET c.balance = c.initialBalance + t.netIncome
		WHERE c.adminId = :adminId_main");

		$stmt2->execute([':adminId_sub' => $this->adminId, 
		':adminId_main' => $this->adminId]);
		return $transactionTotalRows;

		
	}
	public function paginate(int $page = 1, int $limit = 10, string $sortColumn = 'createdAt', string $sortOrder = 'DESC', ?string $type = null, ?string $filter = 'all', ?int $month = null, ?int $week = null, ?int $year = null): array
	{
		$offset = ($page - 1) * $limit;
		$allowedColumns = ['amount', 'description', 'createdAt'];
		$allowedOrders = ['ASC', 'DESC'];
		$allowedTypes = ['sale', 'expense', 'restock', 'deposit'];

		if (!in_array($sortColumn, $allowedColumns)) $sortColumn = 'createdAt';
		if (!in_array(strtoupper($sortOrder), $allowedOrders)) $sortOrder = 'DESC';

		$typeClause = ($type && in_array($type, $allowedTypes)) ? "AND type = :type" : "";

		$filterClause = '';
		$params = [':adminId' => $this->adminId];

		switch ($filter) {
			case 'now':
				$filterClause = "AND DATE(createdAt) = CURDATE()";
				break;
			case 'month':
				$filterClause = "AND MONTH(createdAt) = :month AND YEAR(createdAt) = :year";
				$params[':month'] = $month;
				$params[':year']  = $year;
				break;
			case 'week':
				$ranges = [1 => [1, 7], 2 => [8, 14], 3 => [15, 21], 4 => [22, 31]];
				$start = $ranges[$week][0] ?? 1;
				$end   = $ranges[$week][1] ?? 7;
				$filterClause = "AND MONTH(createdAt) = :month AND YEAR(createdAt) = :year AND DAY(createdAt) BETWEEN :start AND :end";
				$params[':month'] = $month;
				$params[':year']  = $year;
				$params[':start'] = $start;
				$params[':end']   = $end;
				break;
		}

		if ($type && in_array($type, $allowedTypes)) $params[':type'] = $type;

		$stmt = $this->pdo->prepare("
        SELECT * FROM capital_transactions_tb
        WHERE adminId = :adminId
        $filterClause
        $typeClause
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
		return array_map(fn($row) => new CapitalTransaction(
			$row['type'],
			$row['amount'],
			$row['description'],
			new DateTime($row['createdAt']),
			(int)$row['id'],
			(int)$row['adminId'],
			(int) $row['saleId'],
			(int) $row['inventoryId'],
			(int) $row['quantity'],
		), $rows);
	}

	public function countFiltered(?string $type = null, ?string $filter = 'all', ?int $month = null, ?int $week = null, ?int $year = null): int
	{
		$allowedTypes = ['sale', 'expense', 'restock', 'deposit'];
		$typeClause = ($type && in_array($type, $allowedTypes)) ? "AND type = :type" : "";

		$filterClause = '';
		$params = [':adminId' => $this->adminId];

		switch ($filter) {
			case 'now':
				$filterClause = "AND DATE(createdAt) = CURDATE()";
				break;
			case 'month':
				$filterClause = "AND MONTH(createdAt) = :month AND YEAR(createdAt) = :year";
				$params[':month'] = $month;
				$params[':year']  = $year;
				break;
			case 'week':
				$ranges = [1 => [1, 7], 2 => [8, 14], 3 => [15, 21], 4 => [22, 31]];
				$start = $ranges[$week][0] ?? 1;
				$end   = $ranges[$week][1] ?? 7;
				$filterClause = "AND MONTH(createdAt) = :month AND YEAR(createdAt) = :year AND DAY(createdAt) BETWEEN :start AND :end";
				$params[':month'] = $month;
				$params[':year']  = $year;
				$params[':start'] = $start;
				$params[':end']   = $end;
				break;
		}

		if ($type && in_array($type, $allowedTypes)) $params[':type'] = $type;

		$stmt = $this->pdo->prepare("
        SELECT COUNT(*) FROM capital_transactions_tb
        WHERE adminId = :adminId
        $filterClause
        $typeClause ");
		$stmt->execute($params);
		return (int) $stmt->fetchColumn();
	}

}
