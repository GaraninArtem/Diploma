<?php
// deleteTask.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskId = $_POST['id'];
    if (deleteTaskFromDatabase($taskId)) {
        header("Location: index.php");
    } else {
        echo "Ошибка при удалении задачи с ID $taskId.";
    }
} else {
    http_response_code(405);
    echo "Метод не поддерживается.";
}

function deleteTaskFromDatabase($taskId)
{
    $pdo = new PDO('mysql:host=localhost;dbname=diploma', 'root', '');
    $stmt = $pdo->prepare("DELETE FROM assigments WHERE id = :id");
    $stmt->bindParam(':id', $taskId, PDO::PARAM_INT);
    return $stmt->execute();
}
?>