<?php


include_once("database.php");
include_once("inventory.php");
include_once("baserepo.php");
class InventoryRepo extends BaseRepository
{

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

		return new Inventory(
			$row['name'],
			$row['quantity'],
			$row['price'],
			new DateTime($row['lastUpdated']),
			(int)$row['id'],
			(int)$row['adminId'],
		);
	}
	public function findAll(string $sortColumn = 'lastUpdated', string $sortOrder = 'DESC'): array
	{
		$allowedColumns = ['name', 'quantity', 'price', 'status', 'lastUpdated'];
		$allowedOrders = ['ASC', 'DESC'];

		if (!in_array($sortColumn, $allowedColumns)) $sortColumn = 'lastUpdated';
		if (!in_array(strtoupper($sortOrder), $allowedOrders)) $sortOrder = 'DESC';

		if ($sortColumn === 'status') {
			$stmt = $this->pdo->query("SELECT * FROM inventory_tb WHERE adminId = :adminId");
		} else {
			$stmt = $this->pdo->query("SELECT * FROM inventory_tb  WHERE adminId = $this->adminId ORDER BY $sortColumn $sortOrder");
		}

		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$inventories =  array_map(fn($row) => new Inventory(
			$row['name'],
			$row['quantity'],
			$row['price'],
			new DateTime($row['lastUpdated']),
			(int)$row['id'],
			(int)$row['adminId'],
		), $rows);

		if ($sortColumn === 'status') {
			$priority = ['Out of Stock' => 0, 'Low Stock' => 1, 'Available' => 2];
			usort($inventories, function ($a, $b) use ($priority, $sortOrder) {
				$result = $priority[$a->getStatus()] <=> $priority[$b->getStatus()];
				return strtoupper($sortOrder) === 'ASC' ? $result : -$result;
			});
		}

		return $inventories;
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

		return array_map(fn($row) => new Inventory(
			$row['name'],
			$row['quantity'],
			$row['price'],
			new DateTime($row['lastUpdated']),
			(int)$row['id'],
			(int)$row['adminId'],
		), $rows);
	}


	public function processSales(int $id, int $amount, float $price): void
	{
		$item = $this->findById($id);
		if (!$item) throw new Exception("Item not found");
		if ($item->getQuantity() < $amount) throw new Exception("Not enough stock");

		$pricePerUnit = $price;
		$salesAmount = $pricePerUnit * $amount;
		$currentDate = date('Y-m-d');

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

		$this->pdo->beginTransaction();
		try {
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
					SET amount = :salesAmount
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
					INSERT INTO sales_tb (inventoryId, name, itemsSold, sale, dateSold, adminId)
					VALUES (:inventoryId, :name, :amount, :sale, NOW(), :adminId)
					");
				$stmt->execute([
					':inventoryId' => $id,
					':name' => $item->getProductName(),
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



	public function delete(int $id): void
	{
		$stmt = $this->pdo->prepare("
			DELETE FROM inventory_tb WHERE id = :id AND adminId = :adminId
			");

		$stmt->execute([
			':id' => $id,
			':adminId' => $this->adminId
		]);
	}


	public function save(Inventory $inventory): void
	{
		$stmt = $this->pdo->prepare("
			INSERT INTO inventory_tb (name, quantity, price, adminId) 
			VALUES (:name, :quantity, :price, :adminId)");

		$stmt->execute([
			':name' => $inventory->getProductName(),
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
				quantity = :quantity,
				price = :price
				WHERE id = :id AND
				adminId = :adminId
			");

			$stmt->execute([
				':id' => $id,
				':name' => $inventory->getProductName(),
				':quantity' => $inventory->getQuantity(),
				':price' => $inventory->getPrice(),
				':adminId' => $this->adminId
			]);


			$stmt = $this->pdo->prepare("
				UPDATE inventory_tb 
				SET name = :name 
				WHERE inventoryId = :id
				AND adminId = :adminId
				");
			$stmt->execute([
				':name' => $inventory->getProductName(),
				':id' => $id,
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
	public function paginate(int $page = 1, int $limit = 10, string $sortColumn = 'lastUpdated', string $sortOrder = 'DESC', string $search = ''): array
	{
		$offset = ($page - 1) * $limit;
		$allowedColumns = ['name', 'quantity', 'price', 'lastUpdated'];
		$allowedOrders = ['ASC', 'DESC'];
		if (!in_array($sortColumn, $allowedColumns)) $sortColumn = 'lastUpdated';
		if (!in_array(strtoupper($sortOrder), $allowedOrders)) $sortOrder = 'DESC';

		$searchClause = $search !== '' ? "AND name LIKE :search" : "";
		$params = [':adminId' => $this->adminId];
		if ($search !== '') $params[':search'] = '%' . $search . '%';

		$stmt = $this->pdo->prepare("
        SELECT * FROM inventory_tb
        WHERE adminId = :adminId
        $searchClause
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
		return array_map(fn($row) => new Inventory(
			$row['name'],
			$row['quantity'],
			$row['price'],
			new DateTime($row['lastUpdated']),
			(int)$row['id'],
			(int)$row['adminId'],
		), $rows);
	}

	public function countFiltered(string $search = ''): int
	{
		$searchClause = $search !== '' ? "AND name LIKE :search" : "";
		$params = [':adminId' => $this->adminId];
		if ($search !== '') $params[':search'] = '%' . $search . '%';

		$stmt = $this->pdo->prepare("
        SELECT COUNT(*) FROM inventory_tb
        WHERE adminId = :adminId
        $searchClause
    ");
		$stmt->execute($params);
		return (int) $stmt->fetchColumn();
	}
}
