<?php
include_once("database.php");
include_once("baserepo.php");
include_once("sale.php");
include_once("capitaltransaction.php");

class CapitalTransactionRepo extends BaseRepository
{

	private $allowedColumns = ['amount', 'description', 'createdAt'];
	private $allowedTransactionTypes = ['sale', 'expense', 'restock', 'deposit'];
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
		return $this->mapToCapital($row);
	}
	public function findAll(string $sortColumn = 'createdAt', string $sortOrder = 'DESC'): array
	{
		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'createdAt';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';

		$stmt = $this->pdo->prepare("
			SELECT * FROM capital_transactions_tb
			WHERE adminId = :adminId 
			AND status = 'active'
			ORDER BY $sortColumn $sortOrder
			");
		$stmt->execute([':adminId' => $this->adminId]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => $this->mapToCapital($row), $rows);
	}

	public function findCapitalTransactionsByMonth(int $month, int $year, string $sortColumn = 'createdAt', string $sortOrder = 'DESC', ?string $type = null, string $search = ''): array
	{
		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'createdAt';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';
		$transactionTypeClause = ($type && in_array($type, $this->allowedTransactionTypes)) ? "AND type = :type" : "";
		$searchClause = $search !== '' ? "AND description LIKE :search" : "";

		$stmt = $this->pdo->prepare("
			SELECT * FROM capital_transactions_tb
			WHERE MONTH(createdAt) = :month
			AND YEAR(createdAt) = :year 
			AND adminId = :adminId
			AND status = 'active'
			$transactionTypeClause
			$searchClause
			ORDER BY $sortColumn $sortOrder
		    ");
		$params = [':month' => $month, ':year' => $year, ':adminId' => $this->adminId];
		if ($type && in_array($type, $this->allowedTransactionTypes)) $params[':type'] = $type;
		if ($search !== '') $params[':search'] = '%' . $search . '%';
		$stmt->execute($params);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => $this->mapToCapital($row), $rows);
	}


	public function findCapitalTransactionsByMonthWeek(int $month, int $week, int $year, string $sortColumn = 'createdAt', string $sortOrder = 'DESC', ?string $type = null, string $search = ''): array
	{
		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'createdAt';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';
		$transactionTypeClause = ($type && in_array($type, $this->allowedTransactionTypes)) ? "AND type = :type" : "";
		$searchClause = $search !== '' ? "AND description LIKE :search" : "";
		$ranges = [1 => [1, 7], 2 => [8, 14], 3 => [15, 21], 4 => [22, 31]];
		$start = $ranges[$week][0];
		$end   = $ranges[$week][1];

		$stmt = $this->pdo->prepare("
			SELECT * FROM capital_transactions_tb 
			WHERE MONTH(createdAt) = :month
			AND YEAR(createdAt) = :year
			AND DAY(createdAt) BETWEEN :start AND :end
			AND status = 'active'
			AND adminId = :adminId
			$transactionTypeClause
			$searchClause
			ORDER BY $sortColumn $sortOrder
		    ");
		$params = [':month' => $month, ':year' => $year, ':start' => $start, ':end' => $end, ':adminId' => $this->adminId];
		if ($type && in_array($type, $this->allowedTransactionTypes)) $params[':type'] = $type;
		if ($search !== '') $params[':search'] = '%' . $search . '%';
		$stmt->execute($params);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => $this->mapToCapital($row), $rows);
	}


	public function findCapitalTransactionsByToday(string $sortColumn = 'createdAt', string $sortOrder = 'DESC', ?string $type = null, string $search = ''): array
	{
		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'createdAt';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';
		$transactionTypeClause = ($type && in_array($type, $this->allowedTransactionTypes)) ? "AND type = :type" : "";
		$searchClause = $search !== '' ? "AND description LIKE :search" : "";

		$stmt = $this->pdo->prepare("
			SELECT * FROM capital_transactions_tb 
			WHERE adminId = :adminId 
			AND DATE(createdAt) = CURDATE()
			AND status = 'active'
			$transactionTypeClause
			$searchClause
			ORDER BY $sortColumn $sortOrder
		    ");
		$params = [':adminId' => $this->adminId];
		if ($type && in_array($type, $this->allowedTransactionTypes)) $params[':type'] = $type;
		if ($search !== '') $params[':search'] = '%' . $search . '%';
		$stmt->execute($params);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => $this->mapToCapital($row), $rows);
	}
	public function findByType(string $type, string $sortColumn = 'createdAt', string $sortOrder = 'DESC', string $statusFilter = 'actvie'): array
	{
		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'createdAt';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';
		if (!in_array($type, $this->allowedTransactionTypes)) return [];

		$stmt = $this->pdo->prepare("
				SELECT * FROM capital_transactions_tb 
				WHERE adminId = :adminId 
				AND type = :type
				AND status = :statusFilter
				ORDER BY $sortColumn $sortOrder
			    ");
		$stmt->execute([
			':adminId' => $this->adminId,
			':type'    => $type,
			':statusFilter'    => $statusFilter
		]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => $this->mapToCapital($row), $rows);
	}

	public function save(CapitalTransaction $capitalTransaction): void
	{
		$stmt = $this->pdo->prepare("
			INSERT INTO capital_transactions_tb 
			    (type, amount, description, adminId, saleId, inventoryId, quantity) 
			VALUES 
			    (:type, :amount, :description, :adminId, :saleId, :inventoryId, :quantity)
		    ");
		$stmt->execute([
			':type'        => $capitalTransaction->getType(),
			':amount'      => $capitalTransaction->getAmount(),
			':description' => $capitalTransaction->getDescription(),
			':adminId'     => $this->adminId,
			':saleId'      => $capitalTransaction->getSaleId(),
			':inventoryId' => $capitalTransaction->getInventoryId(),
			':quantity'    => $capitalTransaction->getQuantity(),
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


	public function calculateIncomeSummary(string $search = '', ?string $type = null, string $filter = 'all', ?int $month = null, ?int $week = null, ?int $year = null): array
	{
		$searchClause = $search !== '' ? "AND description LIKE :search" : "";
		$typeClause   = ($type !== null && $type !== '' && in_array($type, $this->allowedTransactionTypes))
			? "AND type = :type"
			: "";

		$dateClause = "";
		if ($filter === 'now') {
			$dateClause = "AND DATE(createdAt) = CURDATE()";
		} elseif ($filter === 'month' && $month && $year) {
			$dateClause = "AND MONTH(createdAt) = :month AND YEAR(createdAt) = :year";
		} elseif ($filter === 'week' && $month && $week && $year) {
			$ranges     = [1 => [1, 7], 2 => [8, 14], 3 => [15, 21], 4 => [22, 31]];
			$dateClause = "AND MONTH(createdAt) = :month AND YEAR(createdAt) = :year 
                       AND DAY(createdAt) BETWEEN :weekStart AND :weekEnd";
		}

		$params = [':adminId' => $this->adminId];
		if ($search !== '')  $params[':search'] = '%' . $search . '%';
		if ($typeClause !== '') $params[':type'] = $type;
		if ($filter === 'month' && $month && $year) {
			$params[':month'] = $month;
			$params[':year']  = $year;
		} elseif ($filter === 'week' && $month && $week && $year) {
			$ranges = [1 => [1, 7], 2 => [8, 14], 3 => [15, 21], 4 => [22, 31]];
			$params[':month']     = $month;
			$params[':year']      = $year;
			$params[':weekStart'] = $ranges[$week][0];
			$params[':weekEnd']   = $ranges[$week][1];
		}

		$stmt = $this->pdo->prepare("
			SELECT 
			    SUM(CASE WHEN type = 'sale' AND (status IS NULL OR status != 'voided') THEN amount ELSE 0 END) AS totalSales,
			    SUM(CASE WHEN type = 'deposit' AND (status IS NULL OR status != 'voided') THEN amount ELSE 0 END) AS totalDeposits,
			    SUM(CASE WHEN type = 'expense' AND (status IS NULL OR status != 'voided') THEN amount ELSE 0 END) AS totalExpenses,
			    SUM(CASE WHEN type = 'restock' AND (status IS NULL OR status != 'voided') THEN amount ELSE 0 END) AS totalRestocks
			FROM capital_transactions_tb
			WHERE adminId = :adminId
			$searchClause $typeClause $dateClause
		    ");
		$stmt->execute($params);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return [
			'totalSales'    => (float)($row['totalSales']    ?? 0),
			'totalDeposits' => (float)($row['totalDeposits'] ?? 0),
			'totalExpenses' => (float)($row['totalExpenses'] ?? 0),
			'totalRestocks' => (float)($row['totalRestocks'] ?? 0),
			'netIncome'     => ((float)($row['totalSales']    ?? 0) + (float)($row['totalDeposits'] ?? 0)) - ((float)($row['totalExpenses'] ?? 0) + (float)($row['totalRestocks'] ?? 0)),
		];
	}

	public function recalculateBalance(): void
	{
		$stmt = $this->pdo->prepare("
			UPDATE capital_tb
			SET balance = initialBalance + COALESCE((
			    SELECT 
				SUM(CASE WHEN type = 'sale' AND (status IS NULL OR status != 'voided') THEN amount ELSE 0 END)
			      + SUM(CASE WHEN type = 'deposit' AND (status IS NULL OR status != 'voided') THEN amount ELSE 0 END) 
			      - SUM(CASE WHEN type = 'expense' AND (status IS NULL OR status != 'voided') THEN amount ELSE 0 END) 
			      - SUM(CASE WHEN type = 'restock' AND (status IS NULL OR status != 'voided') THEN amount ELSE 0 END) 
			    FROM capital_transactions_tb
			    WHERE adminId = :adminId_sub
			), 0)
			WHERE adminId = :adminId_main
		    ");
		$stmt->execute([
			':adminId_sub'  => $this->adminId,
			':adminId_main' => $this->adminId,
		]);
	}

	public function voidTransaction(int $id, ?SalesRepo $saleRepo = null): void
	{
		$transaction = $this->findById($id);
		$sale = $saleRepo?->findById($transaction->getSaleId());
		if ($transaction->getSaleId() !== null && $transaction->getType() == 'sale') {
			$this->pdo->beginTransaction();
			try {
				$stmt = $this->pdo->prepare("
			    UPDATE sales_tb SET status = 'voided'
			    WHERE id = :id AND adminId = :adminId
			");
				$stmt->execute([':id' => $transaction->getSaleId(), ':adminId' => $this->adminId]);

				$stmt = $this->pdo->prepare("
			    UPDATE capital_transactions_tb SET status = 'voided'
					WHERE 
					id = :id AND
					saleId = :saleId AND adminId = :adminId
			");
				$stmt->execute([
					':id' => $id,
					':saleId' => $transaction->getSaleId(),
					':adminId' => $this->adminId
				]);

				$stmt = $this->pdo->prepare("
			    UPDATE inventory_tb SET quantity = quantity + :quantitySold
			    WHERE id = :inventoryId AND adminId = :adminId
			");
				$stmt->execute([
					':quantitySold' => $sale->getItemsSold(),
					':inventoryId'  => $sale->getInventoryId(),
					':adminId'      => $this->adminId
				]);

				$this->pdo->commit();
			} catch (Exception $e) {
				$this->pdo->rollBack();
				throw $e;
			}
		} elseif ($transaction->getType() == 'restock') {
			$this->pdo->beginTransaction();
			try {
				$stmt = $this->pdo->prepare("
			    UPDATE capital_transactions_tb SET status = 'voided'
			    WHERE id = :id AND adminId = :adminId
			");
				$stmt->execute([':id' => $id, ':adminId' => $this->adminId]);

				$stmt = $this->pdo->prepare("
			    UPDATE inventory_tb SET quantity = quantity - :voidedQuantity
			    WHERE id = :inventoryId AND adminId = :adminId
			");
				$stmt->execute([
					':inventoryId' => $transaction->getInventoryId(),
					':adminId' => $this->adminId,
					':voidedQuantity' => $transaction->getQuantity()
				]);

				$this->pdo->commit();
			} catch (Exception $e) {
				$this->pdo->rollBack();
				throw $e;
			}
		} else {
			$stmt = $this->pdo->prepare("
			    UPDATE capital_transactions_tb SET status = 'voided'
			    WHERE id = :id AND adminId = :adminId
			");
			$stmt->execute([':id' => $id, ':adminId' => $this->adminId]);
		}
	}

	public function makeTransactionActive(int $id, ?SalesRepo $saleRepo = null): void
	{
		$transaction = $this->findById($id);
		$sale = $saleRepo->findById($transaction->getSaleId());
		if ($transaction->getSaleId() !== null && $transaction->getType() == 'sale') {
			$this->pdo->beginTransaction();
			try {
				$stmt = $this->pdo->prepare("
			    UPDATE sales_tb SET status = 'active'
			    WHERE id = :id AND adminId = :adminId
			");
				$stmt->execute([':id' => $transaction->getSaleId(), ':adminId' => $this->adminId]);

				$stmt = $this->pdo->prepare("
			    UPDATE capital_transactions_tb SET status = 'active'
					WHERE 
					id = :id AND
					saleId = :saleId AND adminId = :adminId
			");
				$stmt->execute([
					':id' => $id,
					':saleId' => $$transaction->getSaleId(),
					':adminId' => $this->adminId
				]);

				$stmt = $this->pdo->prepare("
			    UPDATE inventory_tb SET quantity = quantity - :quantitySold
			    WHERE id = :inventoryId AND adminId = :adminId
			");
				$stmt->execute([
					':quantitySold' => $sale->getItemsSold(),
					':inventoryId'  => $sale->getInventoryId(),
					':adminId'      => $this->adminId
				]);

				$this->pdo->commit();
			} catch (Exception $e) {
				$this->pdo->rollBack();
				throw $e;
			}
		} elseif ($transaction->getType() == 'restock') {
			$this->pdo->beginTransaction();
			try {
				$stmt = $this->pdo->prepare("
			    UPDATE capital_transactions_tb SET status = 'active'
			    WHERE id = :id AND adminId = :adminId
			");
				$stmt->execute([':id' => $id, ':adminId' => $this->adminId]);

				$stmt = $this->pdo->prepare("
			    UPDATE inventory_tb SET quantity = quantity + :voidedQuantity
			    WHERE id = :inventoryId AND adminId = :adminId
			");
				$stmt->execute([
					':inventoryId' => $transaction->getInventoryId(),
					':adminId' => $this->adminId,
					':quantity' => $transaction->getQuantity()
				]);

				$this->pdo->commit();
			} catch (Exception $e) {
				$this->pdo->rollBack();
				throw $e;
			}
		} else {
			$stmt = $this->pdo->prepare("
			    UPDATE capital_transactions_tb SET status = 'active'
			    WHERE id = :id AND adminId = :adminId
			");
			$stmt->execute([':id' => $id, ':adminId' => $this->adminId]);
		}
	}

	public function paginate(int $page = 1, int $limit = 10, string $sortColumn = 'createdAt', string $sortOrder = 'DESC', string $search = '', string $type = '', string $filter = 'all', ?int $month = null, ?int $week = null, ?int $year = null, string $statusFilter = 'active'): array
	{
		$offset = ($page - 1) * $limit;
		if (!in_array($sortColumn, $this->allowedColumns)) $sortColumn = 'createdAt';
		if (!in_array(strtoupper($sortOrder), $this->allowedOrders)) $sortOrder = 'DESC';

		$searchClause = ($search !== null && $search !== '') ? "AND description LIKE :search" : "";
		$transactionTypeClause = ($type !== '' && in_array($type, $this->allowedTransactionTypes)) ? "AND type = :type" : "";
		$statusClause = "AND status = :statusFilter";

		$dateClause = "";
		if ($filter === 'now') {
			$dateClause = "AND DATE(createdAt) = CURDATE()";
		} elseif ($filter === 'month' && $month && $year) {
			$dateClause = "AND MONTH(createdAt) = :month AND YEAR(createdAt) = :year";
		} elseif ($filter === 'week' && $month && $week && $year) {
			$ranges = [1 => [1, 7], 2 => [8, 14], 3 => [15, 21], 4 => [22, 31]];
			$start  = $ranges[$week][0];
			$end    = $ranges[$week][1];
			$dateClause = "AND MONTH(createdAt) = :month AND YEAR(createdAt) = :year AND DAY(createdAt) BETWEEN :weekStart AND :weekEnd";
		}

		$params = [':adminId' => $this->adminId];
		if ($search !== '')              $params[':search'] = '%' . $search . '%';
		if ($transactionTypeClause !== '') $params[':type'] = $type;
		if ($filter === 'month' && $month && $year) {
			$params[':month'] = $month;
			$params[':year']  = $year;
		} elseif ($filter === 'week' && $month && $week && $year) {
			$params[':month']     = $month;
			$params[':year']      = $year;
			$params[':weekStart'] = $start;
			$params[':weekEnd']   = $end;
		}
		$params[':statusFilter'] = $statusFilter;

		$stmt = $this->pdo->prepare("
				SELECT * FROM capital_transactions_tb
				WHERE adminId = :adminId
				$searchClause
				$transactionTypeClause
				$dateClause
				$statusClause
				ORDER BY $sortColumn $sortOrder
				LIMIT :limit OFFSET :offset
			    ");
		foreach ($params as $key => $value) $stmt->bindValue($key, $value);
		$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_map(fn($row) => $this->mapToCapital($row), $rows);
	}

	public function countFiltered(?string $search = null, ?string $type = null, string $filter = 'all', ?int $month = null, ?int $week = null, ?int $year = null, string $statusFilter = 'active'): int
	{
		$searchClause          = $search !== '' ? "AND description LIKE :search" : "";
		$transactionTypeClause = ($type !== '' && in_array($type, $this->allowedTransactionTypes)) ? "AND type = :type" : "";
		$statusClause = "AND status = :statusFilter";

		$dateClause = "";
		if ($filter === 'now') {
			$dateClause = "AND DATE(createdAt) = CURDATE()";
		} elseif ($filter === 'month' && $month && $year) {
			$dateClause = "AND MONTH(createdAt) = :month AND YEAR(createdAt) = :year";
		} elseif ($filter === 'week' && $month && $week && $year) {
			$ranges = [1 => [1, 7], 2 => [8, 14], 3 => [15, 21], 4 => [22, 31]];
			$start  = $ranges[$week][0];
			$end    = $ranges[$week][1];
			$dateClause = "AND MONTH(createdAt) = :month AND YEAR(createdAt) = :year AND DAY(createdAt) BETWEEN :weekStart AND :weekEnd";
		}

		$params = [':adminId' => $this->adminId];
		if ($search !== '')                $params[':search'] = '%' . $search . '%';
		if ($transactionTypeClause !== '') $params[':type']   = $type;
		if ($filter === 'month' && $month && $year) {
			$params[':month'] = $month;
			$params[':year']  = $year;
		} elseif ($filter === 'week' && $month && $week && $year) {
			$params[':month']     = $month;
			$params[':year']      = $year;
			$params[':weekStart'] = $start;
			$params[':weekEnd']   = $end;
		}
		$params[':statusFilter'] = $statusFilter;

		$stmt = $this->pdo->prepare("
			SELECT COUNT(*) FROM capital_transactions_tb  
			WHERE adminId = :adminId
			$searchClause
			$transactionTypeClause
			$dateClause
			$statusClause
		    ");
		$stmt->execute($params);
		return (int) $stmt->fetchColumn();
	}
	public function delete(int $id): void
	{
		$transaction = $this->findById($id);
		$stmt = $this->pdo->prepare("
			DELETE FROM capital_transactions_tb 
			WHERE id = :id AND adminId = :adminId
		    ");
		$stmt->execute([':id' => $id, ':adminId' => $this->adminId]);
		if ($transaction->getSaleId() !== null && $transaction->getType() == 'sale') {
			$stmt2 = $this->pdo->prepare("
				DELETE FROM sales_tb
				WHERE id = :saleId AND adminId = :adminId
		    ");
			$stmt2->execute([':saleId' => $transaction->getSaleId(), ':adminId' => $this->adminId]);
		}
	}
	private function mapToCapital(array $row): CapitalTransaction
	{
		return new CapitalTransaction(
			type: $row['type'],
			amount: $row['amount'],
			description: $row['description'],
			createdAt: new DateTime($row['createdAt']),
			id: (int) $row['id'],
			adminId: (int) $row['adminId'],
			saleId: (int) $row['saleId'],
			inventoryId: (int) $row['inventoryId'],
			quantity: (int) $row['quantity'],
		);
	}
}
