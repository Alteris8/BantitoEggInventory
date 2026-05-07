<?php

include_once("sale.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SalesExporter
{
	public function export(array $sales, string $filename, float $totalSales): void
	{
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();

		$sheet->fromArray(['Product', 'Items Sold', 'Sale', 'Date Sold'], null, 'A1');

		$row = 2;
		foreach ($sales as $sale) {
			$sheet->fromArray([
				$sale->getProductName(),
				$sale->getItemsSold(),
				$sale->getSale(),
				$sale->getDate()->format('d-m-Y'),
			], null, "A{$row}");
			$row++;
		}
		$sheet->fromArray(['Total Sales', '', $totalSales, ''], null, 'A' . $row);

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header("Content-Disposition: attachment; filename={$filename}.xlsx");

		$writer = new Xlsx($spreadsheet);
		$writer->save('php://output');
		exit;
	}
}
