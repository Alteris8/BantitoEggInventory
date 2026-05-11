<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
include_once("database.php");
include_once("capitaltransactionsrepo.php");
include_once("salerepo.php");
include_once("inventoryrepo.php");
include_once("CapitalTransactionExporter.php");

$pdo = getPDO();
$capitalTransactionsRepo = new CapitalTransactionRepo($pdo, $_SESSION['admin_id']);
$saleRepo                = new SalesRepo($pdo, $_SESSION['admin_id']);
$inventoryRepo           = new InventoryRepo($pdo, $_SESSION['admin_id']);

$filter  = $_GET['filter'] ?? 'all';
$type    = $_GET['type'] ?? null;
$month   = (int)($_GET['month'] ?? date('m'));
$week    = (int)($_GET['week'] ?? 1);
$year    = (int)($_GET['year'] ?? date('Y'));
$balance = (float)($_GET['balance'] ?? 0);

$summary = [
	'totalSales'    => (float)($_GET['totalSales'] ?? 0),
	'totalDeposits' => (float)($_GET['totalDeposits'] ?? 0),
	'totalExpenses' => (float)($_GET['totalExpenses'] ?? 0),
	'totalRestocks' => (float)($_GET['totalRestocks'] ?? 0),
	'netIncome'     => (float)($_GET['netIncome'] ?? 0),
];

switch ($filter) {
	case 'week':
		$transactions = $capitalTransactionsRepo->exportWeeklyCapitalTransactions($month, $week, $year);
		$sales        = $saleRepo->findSalesByMonthWeek($month, $week, $year);
		$filename     = "capital_report_week{$week}_{$month}_{$year}";
		break;
	case 'month':
		$transactions = $capitalTransactionsRepo->findCapitalTransactionsByMonth($month, $year);
		$sales        = $saleRepo->findSalesByMonth($month, $year);
		$filename     = "capital_report_{$month}_{$year}";
		break;
	case 'now':
		$transactions = $capitalTransactionsRepo->exportCapitalTransactionsToday();
		$sales        = $saleRepo->findToday();
		$filename     = "capital_report_today_" . date('d-m-Y');
		break;
	default:
		$transactions = $capitalTransactionsRepo->exportAll();
		$sales        = $saleRepo->findAll();
		$filename     = "capital_report_all";
		break;
}

$inventories = $inventoryRepo->findAll();

$exporter = new CapitalTransactionExporter();
$exporter->export($transactions, $sales, $inventories, $filename, $balance, $summary);
