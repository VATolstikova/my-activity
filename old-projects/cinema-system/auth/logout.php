<?php
session_start();
session_destroy();

// Перенаправляем на главную страницу
header("Location: ../index.php");
exit();
?>