<?php
include 'config.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') header("Location: index.php");
$id = (int)$_GET['id'];
$conn->query("DELETE FROM pesanan WHERE id = $id");
header("Location: index.php");
?>