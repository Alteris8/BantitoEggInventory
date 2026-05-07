<?php
require_once 'vendor/autoload.php';
include_once("database.php");
include_once("salerepo.php");
include_once("salesexport.php");

$pdo = getPDO();
$saleRepo = new SalesRepo($pdo);
$exporter = new SalesExporter();

$filter = $_GET['filter'] ?? 'all';
$month  = isset($_GET['month']) && $_GET['month'] !== '' ? (int)$_GET['month'] : (int)date('m');
$week   = isset($_GET['week'])  && $_GET['week']  !== '' ? (int)$_GET['week']  : null;
$year   = isset($_GET['year'])  && $_GET['year']  !== '' ? (int)$_GET['year']  : (int)date('Y');
switch ($filter) {
	case 'week':
		if ($month && $week && $year) {
			$sales = $saleRepo->exportWeeklySales($month, $week, $year);
			$filename = "sales_week{$week}_month{$month}_{$year}";
		} else {
			$sales = [];
			$filename = "sales_export";
		}
		break;

	case 'month':
		$sales = $saleRepo->exportMonthlySales($month, $year);
		$filename = "sales_month{$month}_{$year}";
		break;
	default:
		$sales = $saleRepo->exportAll();
		$filename = "sales_all_" . date('Y-m-d');
		break;
}

$total = $saleRepo->totalSales($filter, $month, $week, $year);
$exporter->export($sales, $filename, $total);
