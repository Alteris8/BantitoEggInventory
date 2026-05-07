<?php
function getPDO(): PDO
{
	return new PDO("mysql:host=localhost;dbname=egg_db;charset=utf8", "root", "", [
		PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_EMULATE_PREPARES   => false,
		PDO::ATTR_STRINGIFY_FETCHES  => false,
	]);
}
