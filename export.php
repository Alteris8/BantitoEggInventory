<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
include_once("database.php");
include_once("capitaltransactionsrepo.php");
include_once("salerepo.php");
include_once("capitalrepo.php");
include_once("inventoryrepo.php");
include_once("producttyperepo.php");
include_once("CapitalTransactionExporter.php");

$pdo = getPDO();
$productTypeRepo         = new ProductTypeRepo($pdo, $_SESSION['admin_id']);
$capitalTransactionsRepo = new CapitalTransactionRepo($pdo, $_SESSION['admin_id']);
$saleRepo                = new SalesRepo($pdo, $_SESSION['admin_id'], $productTypeRepo);
$inventoryRepo           = new InventoryRepo($pdo, $_SESSION['admin_id'], $productTypeRepo);
$capitalRepo             = new CapitalRepo($pdo, $_SESSION['admin_id']);

$filter  = $_GET['filter'] ?? 'all';
$type    = $_GET['type']   ?? null;
$search  = $_GET['search'] ?? '';
$month   = (int)($_GET['month'] ?? date('m'));
$week    = (int)($_GET['week']  ?? 1);
$year    = (int)($_GET['year']  ?? date('Y'));

$summary = $capitalTransactionsRepo->calculateIncomeSummary($search, $type, $filter, $month, $week, $year);
$capital = $capitalRepo->findByAdminId($_SESSION['admin_id']);
$balance = $capital ? $capital->getBalance() : 0.0;

switch ($filter) {
	case 'week':
		$transactions = $capitalTransactionsRepo->findCapitalTransactionsByMonthWeek($month, $week, $year, 'createdAt', 'DESC', $type);
		$sales = $saleRepo->findSalesByMonthWeek($month, $week, $year,  'dateSold', 'DESC', null);
		$inventories = $inventoryRepo->findInventoriesByMonthWeek($month, $week, $year, 'lastUpdated', 'DESC');
		$filename     = "capital_report_week{$week}_{$month}_{$year}";
		break;
	case 'month':
		$transactions = $capitalTransactionsRepo->findCapitalTransactionsByMonth($month, $year, 'createdAt', 'DESC', $type, $search);
		$sales        = $saleRepo->findSalesByMonth($month, $year, 'dateSold', 'DESC', null);
		$inventories = $inventoryRepo->findInventoriesByMonth($month, $year, 'lastUpdated', 'DESC', null);
		$filename     = "capital_report_{$month}_{$year}";
		break;
	case 'now':
		$transactions = $capitalTransactionsRepo->findCapitalTransactionsByToday('createdAt', 'DESC', $type);
		$sales        = $saleRepo->findSalesByToday('dateSold', 'DESC', null);
		$inventories = $inventoryRepo->findInventoriesByToday('lastUpdated', 'DESC', null);
		$filename     = "capital_report_today_" . date('d-m-Y');
		break;
	default:
		$transactions = $capitalTransactionsRepo->findAll('createdAt', 'DESC');
		$sales        = $saleRepo->findAll();
		$inventories = $inventoryRepo->findAll();
		$filename     = "capital_report_all";
		break;
}

$salesTotalForExport = $saleRepo->totalSales($filter, $month, $week, $year);
$exporter = new CapitalTransactionExporter();
$exporter->export($transactions, $sales, $inventories, $filename, $balance, $summary, $salesTotalForExport);
