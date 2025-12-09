<?php

/**
 * SQLite Database connection helper (No MySQL needed!)
 */

require_once __DIR__ . '/config.php';

function getDB()
{
  static $connection = null;

  if ($connection === null) {
    try {
      $dbPath = __DIR__ . '/../reindeer_games.db';
      $connection = new PDO('sqlite:' . $dbPath);
      $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      // Create tables if they don't exist
      initializeTables($connection);
    } catch (PDOException $e) {
      error_log("Database connection failed: " . $e->getMessage());
      die("Database connection failed: " . $e->getMessage() . "<br>DB Path: " . $dbPath);
    }
  }

  return $connection;
}

function executeQuery($query, $types = '', $params = [])
{
  $db = getDB();

  try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt;
  } catch (PDOException $e) {
    error_log("Query failed: " . $e->getMessage());
    return false;
  }
}

function initializeTables($db)
{
  $schema = file_get_contents(__DIR__ . '/../schema_sqlite.sql');
  if ($schema) {
    try {
      $db->exec($schema);
    } catch (PDOException $e) {
      // Tables might already exist
    }
  }
}

function generateRoomCode($length = 6)
{
  $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  $code = '';
  $max = strlen($characters) - 1;

  for ($i = 0; $i < $length; $i++) {
    $code .= $characters[random_int(0, $max)];
  }

  return $code;
}

function sanitize($data)
{
  return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
