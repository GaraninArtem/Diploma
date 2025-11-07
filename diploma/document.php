<?php
include 'db.php';

// Получение идентификатора файла из GET-параметра
$fileId = $_GET['id'];

// Получение информации о файле из базы данных
$sql = "SELECT name, format, doc FROM documents WHERE id = :id";
$stmt = $connection->prepare($sql);
$stmt->bindParam(':id', $fileId, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    $fileName = $result['name'];
    $fileType = $result['format'];
    $fileContent = $result['doc'];

    // Установка правильных заголовков в зависимости от типа файла
    switch ($fileType) {
        case 'docx':
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="' . $fileName . '.docx"');
            break;
        default:
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            break;
    }

    echo $fileContent;
    exit;
} else {
    echo 'Файл не найден';
}
?>