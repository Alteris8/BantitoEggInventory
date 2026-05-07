<?php


include_once("database.php");
include_once("inventory.php");
class InventoryRepo
{
	private PDO $pdo;
	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
	}
	public function findById(int $id): ?Inventory
	{
		$stmt = $this->pdo->prepare("SELECT * FROM inventory_tb WHERE id=:id");
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row) return null;

		return new Inventory(
			(int)$row['id'],
			$row['name'],
			$row['quantity'],
			$row['price'],
			new DateTime($row['lastUpdated']),
		);
	}
	public function findAll(string $sortColumn = 'lastUpdated', string $sortOrder = 'DESC'): array
	{
		$allowedColumns = ['name', 'quantity', 'price', 'status', 'lastUpdated'];
		$allowedOrders = ['ASC', 'DESC'];



		if (!in_array($sortColumn, $allowedColumns)) $sortColumn = 'lastUpdated';
		if (!in_array(strtoupper($sortOrder), $allowedOrders)) $sortOrder = 'DESC';

		if ($sortColumn === 'status') {
			$stmt = $this->pdo->query("SELECT * FROM inventory_tb");
		} else {
			$stmt = $this->pdo->query("SELECT * FROM inventory_tb ORDER BY $sortColumn $sortOrder");
		}

		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$inventories =  array_map(fn($row) => new Inventory(
			(int)$row['id'],
			$row['name'],
			$row['quantity'],
			$row['price'],
			new DateTime($row['lastUpdated']),
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
			WHERE name LIKE :name
		");

		$stmt->execute([
			':name' => '%' . $productName . '%'
		]);

		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if (!$rows) return null;

		return array_map(fn($row) => new Inventory(
			(int)$row['id'],
			$row['name'],
			$row['quantity'],
			$row['price'],
			new DateTime($row['lastUpdated'])
		), $rows);
	}


	public function reduceQuantity(int $id, int $amount): void
	{
		$item = $this->findById($id);
		if (!$item) throw new Exception("Item not found");
		if ($item->getQuantity() < 1) throw new Exception("Not enough stock");

		$pricePerUnit = $item->getPrice();
		$salesAmount = $pricePerUnit * $amount;
		$currentDate = date('Y-m-d');

		$stmt = $this->pdo->prepare("
			SELECT id, itemsSold, sale FROM sales_tb
			WHERE inventoryId = :inventoryId
			AND DATE(dateSold) = :currentDate");
		$stmt->execute([':inventoryId' => $id, ':currentDate' => $currentDate]);
		$existingSale = $stmt->fetch(PDO::FETCH_ASSOC);

		$this->pdo->beginTransaction();
		try {
			$stmt = $this->pdo->prepare("
				UPDATE inventory_tb
				SET quantity = quantity - :amount
				WHERE id = :id
				");
			$stmt->execute(['amount' => $amount, 'id' => $id]);

			if ($existingSale) {
				$stmt = $this->pdo->prepare("
					UPDATE sales_tb
					SET itemsSold = itemsSold + :amount,
					sale = sale + :salesAmount
					WHERE id = :saleId
					");
				$stmt->execute([
					':amount' => $amount,
					':sale' => $salesAmount,
					':saleId' => $existingSale['id'],
				]);
			} else {
				$stmt = $this->pdo->prepare("
					INSERT INTO sales_tb (inventoryId, name, itemsSold, sale, dateSold)
					VALUES (:inventoryId, :name, :amount, :sale, NOW())
					");
				$stmt->execute([
					':inventoryId' => $id,
					':name' => $item->getProductName(),
					':amount' => $amount,
					':sale' => $salesAmount,
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
			DELETE FROM inventory_tb WHERE id = :id
			");

		$stmt->execute([
			':id' => $id,
		]);
	}


	public function save(Inventory $inventory): void
	{
		$stmt = $this->pdo->prepare("
			INSERT INTO inventory_tb (name, quantity, price) 
			VALUES (:name, :quantity, :price)");

		$stmt->execute([
			':name' => $inventory->getProductName(),
			':quantity' => $inventory->getQuantity(),
			':price' => $inventory->getPrice(),
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
			WHERE id = :id
			");

			$stmt->execute([
				':id' => $id,
				':name' => $inventory->getProductName(),
				':quantity' => $inventory->getQuantity(),
				':price' => $inventory->getPrice(),
			]);


			$stmt = $this->pdo->prepare("
				UPDATE sales_tb 
				SET name = :name 
				WHERE inventoryId = :id
				");
			$stmt->execute([
				':name' => $inventory->getProductName(),
				':id' => $id
			]);
			$this->pdo->commit();
		} catch (Exception $e) {
			$this->pdo->rollBack();
			throw $e;
		}
	}
}
