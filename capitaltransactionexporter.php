<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CapitalTransactionExporter
{
	public function export(
		array $transactions,
		array $sales,
		array $inventories,
		string $filename,
		float $balance,
		array $summary,
		float $salesTotalFromSalesRepo
	): void {
		$spreadsheet = new Spreadsheet();

		$grouped = ['sale' => [], 'deposit' => [], 'expense' => [], 'restock' => []];
		foreach ($transactions as $t) {
			$grouped[$t->getType()][] = $t;
		}

		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setTitle('Summary');
		$sheet->fromArray(['Balance',        $balance],                  null, 'A1');
		$sheet->fromArray(['Total Sales',    $summary['totalSales']],    null, 'A2');
		$sheet->fromArray(['Total Deposits', $summary['totalDeposits']], null, 'A3');
		$sheet->fromArray(['Total Expenses', $summary['totalExpenses']], null, 'A4');
		$sheet->fromArray(['Total Restocks', $summary['totalRestocks']], null, 'A5');
		$sheet->fromArray(['Net Income',     $summary['netIncome']],     null, 'A6');

		//Salesexporter.php
		$salesSheet = $spreadsheet->createSheet();
		$salesSheet->setTitle('Sales');
		$salesSheet->fromArray(['Product', 'Product Type', 'Unit Price', 'Items Sold', 'Amount', 'Date Sold'], null, 'A1');
		$row = 2;
		foreach ($sales as $sale) {
			$salesSheet->fromArray([
				$sale->getProductName(),
				$sale->getProductType(),
				$sale->getPrice(),
				$sale->getItemsSold(),
				$sale->getSale(),
				$sale->getDate()->format('d-m-Y'),
			], null, "A{$row}");
			$row++;
		}
		$salesSheet->fromArray(['',  '', '', 'Total: ',  $salesTotalFromSalesRepo], null, "A{$row}");
		//END

		$depositSheet = $spreadsheet->createSheet();
		$depositSheet->setTitle('Deposits');
		$depositSheet->fromArray(['Description', 'Amount', 'Date'], null, 'A1');
		$row = 2;
		foreach ($grouped['deposit'] as $t) {
			$depositSheet->fromArray([
				$t->getDescription(),
				$t->getAmount(),
				$t->getCurrentDate()->format('d-m-Y'),
			], null, "A{$row}");
			$row++;
		}
		$depositSheet->fromArray(['Total', $summary['totalDeposits']], null, "A{$row}");

		$expenseSheet = $spreadsheet->createSheet();
		$expenseSheet->setTitle('Expenses');
		$expenseSheet->fromArray(['Description', 'Amount', 'Date'], null, 'A1');
		$row = 2;
		foreach ($grouped['expense'] as $t) {
			$expenseSheet->fromArray([
				$t->getDescription(),
				$t->getAmount(),
				$t->getCurrentDate()->format('d-m-Y'),
			], null, "A{$row}");
			$row++;
		}
		$expenseSheet->fromArray(['Total', $summary['totalExpenses']], null, "A{$row}");

		$restockSheet = $spreadsheet->createSheet();
		$restockSheet->setTitle('Restocks');
		$restockSheet->fromArray(['Product', 'Quantity', 'Cost', 'Date'], null, 'A1');
		$row = 2;
		foreach ($grouped['restock'] as $t) {
			$restockSheet->fromArray([
				$t->getDescription(),
				$t->getQuantity() ?? 'N/A',
				$t->getAmount(),
				$t->getCurrentDate()->format('d-m-Y'),
			], null, "A{$row}");
			$row++;
		}
		$restockSheet->fromArray(['Total', '', $summary['totalRestocks']], null, "A{$row}");

		//Inventoryexporter.php
		$inventorySheet = $spreadsheet->createSheet();
		$inventorySheet->setTitle('Inventory');
		$inventorySheet->fromArray(['Product', 'Quantity', 'Product Type', 'Price', 'Status', 'Last Updated'], null, 'A1');
		$row = 2;
		foreach ($inventories as $inv) {
			$inventorySheet->fromArray([
				$inv->getProductName(),
				$inv->getQuantity(),
				$inv->getProductType(),
				$inv->getPrice(),
				$inv->getStatus(),
				$inv->getDateUpdated()->format('d-m-Y'),
			], null, "A{$row}");
			$row++;
		}
		$totalInventoryValue = array_reduce(
			$inventories,
			fn($carry, $inv) => $carry + ($inv->getQuantity() * $inv->getPrice()),
			0.0
		);
		$inventorySheet->fromArray(['Total Value', $totalInventoryValue], null, "A{$row}");
		//END

		$spreadsheet->setActiveSheetIndex(0);
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header("Content-Disposition: attachment; filename={$filename}.xlsx");
		$writer = new Xlsx($spreadsheet);
		$writer->save('php://output');
		exit;
	}
}
