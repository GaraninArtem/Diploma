<?php

class User
{
    public function regUser()
    {
        include 'db.php';
        if (isset($_POST['register'])) {
            $name = $_POST['name'];
            $surname = $_POST['surname'];
            $email = $_POST['email'];
            $password = $_POST['password'];
            $repPassword = $_POST['repPassword'];

            if ($password != $repPassword) {
                echo "<p>Пароли не совпадают.</p>";
            } else {
                $query = $connection->prepare("SELECT * FROM user WHERE email=:email");
                $query->bindParam(":email", $email, PDO::PARAM_STR);
                $query->execute();

                if ($query->rowCount() > 0) {
                    echo "<p>Такой пользователь уже существует.</p>";
                } else {
                    $query = $connection->prepare("INSERT INTO user (name, surname, email, password, status) VALUES (:name, :surname, :email, :password, 'Пользователь')");
                    $query->bindParam(":name", $name, PDO::PARAM_STR);
                    $query->bindParam(":surname", $surname, PDO::PARAM_STR);
                    $query->bindParam(":email", $email, PDO::PARAM_STR);
                    $query->bindParam(":password", $password, PDO::PARAM_STR);

                    $result = $query->execute();
                    if ($result) {
                        header("Location: index.php");
                        exit();
                    } else {
                        echo "<p>Ошибка.</p>";
                    }
                }
            }
        }
    }

    public function authUser()
    {
        include 'db.php';
        if (isset($_POST['login'])) {
            $email = $_POST['email'];
            $password = $_POST['password'];

            $query = $connection->prepare("SELECT id, password FROM user WHERE email=:email");
            $query->bindParam(":email", $email, PDO::PARAM_STR);
            $query->execute();
            $result = $query->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $storedPassword = $result['password'];
                $userId = $result['id'];

                if ($password == $storedPassword) {
                    session_start();
                    $_SESSION['login'] = $userId;
                    header("Location: index.php");
                    exit;
                } else {
                    echo "<p>Неправильный пароль.</p>";
                }
            } else {
                echo "<p>Неправильный email.</p>";
            }
        }
    }

    public function uploadImage($connection)
    {
        if (isset($_POST['upload']) && isset($_FILES['profilePhoto'])) {
            $img = file_get_contents($_FILES['profilePhoto']['tmp_name']);
            $userId = $_SESSION['login'];

            $query = $connection->prepare("UPDATE user SET img = :img WHERE id = :userId");
            $query->bindParam(":img", $img, PDO::PARAM_STR);
            $query->bindParam(":userId", $userId, PDO::PARAM_INT);
            $query->execute();
            header("Location: index.php");
        } else {
            echo "Ошибка загрузки файла.";
        }
    }

    public function createFolder($connection)
    {
        if (isset($_POST['create'])) {
            $folderName = $_POST['folderName'];
            $query = $connection->prepare("INSERT INTO folders (name) VALUES (:folderName)");
            $query->bindParam(":folderName", $folderName, PDO::PARAM_STR);
            $query->execute();
            header("Location: index.php");
            exit;
        }
    }

    public function uploadPost($connection)
    {
        if (isset($_POST['post'])) {
            $heading = $_POST['heading'];
            $content = $_POST['content'];
            $folderId = $_POST['folder'];
            $userId = $_SESSION['login'];

            try {
                $query = $connection->prepare("INSERT INTO post (folder_id, user_id, create_dt, heading, content)
                                       VALUES (:folderId, :userId, NOW(), :heading, :content)");
                $query->bindParam(":folderId", $folderId, PDO::PARAM_INT);
                $query->bindParam(":userId", $userId, PDO::PARAM_STR);
                $query->bindParam(":heading", $heading, PDO::PARAM_STR);
                $query->bindParam(":content", $content, PDO::PARAM_STR);
                $query->execute();
                
                header("Location: index.php");
                exit();
            } catch (PDOException $e) {

                echo "Ошибка при загрузке поста: " . $e->getMessage();
            }
        } else {
            echo "Ошибка загрузки поста. Не все поля заполнены.";
        }
    }

    public function deleteUser($connection, $userId)
    {
        try {
            // Удаляем посты пользователя
            $deletePostsQuery = "DELETE FROM post WHERE user_id = ?";
            $stmt = $connection->prepare($deletePostsQuery);
            $stmt->bindValue(1, $userId);
            $stmt->execute();

            // Удаляем пользователя
            $deleteUserQuery = "DELETE FROM user WHERE id = ?";
            $stmt = $connection->prepare($deleteUserQuery);
            $stmt->bindValue(1, $userId);
            $stmt->execute();

            // Возвращаем успешный результат
            return true;
        } catch (Exception $e) {
            // Возвращаем ошибку
            return false;
        }
    }

    public function setAssignment($connection)
    {
        if (isset($_POST['set'])) {
            $taskName = $_POST['task-name'];
            $taskContent = $_POST['task-content'];
            $userId = $_POST['assignee'];
            $creatorId = $_SESSION['login'];

            $query = $connection->prepare("INSERT INTO assigments (heading, content, user_id, creator_id) VALUES (:heading, :content, :userId, :creatorId)");
            $query->bindParam(':heading', $taskName, PDO::PARAM_STR);
            $query->bindParam(':content', $taskContent, PDO::PARAM_STR);
            $query->bindParam(':userId', $userId, PDO::PARAM_INT);
            $query->bindParam(':creatorId', $creatorId, PDO::PARAM_INT);

            if ($query->execute()) {
                header("Location: index.php");
                exit();
            } else {
                echo "Error inserting task: " . $query->errorInfo()[2];
            }
        }
    }

    function saveFileToDatabase($connection, $fileName, $fileFormat, $file, $userId)
    {
        // Проверка на наличие данных
        if (!empty($fileName) && !empty($fileFormat) && !empty($file)) {
            $fileContent = file_get_contents($file);

            // Подготовка SQL-запроса
            $sql = "INSERT INTO documents (user_id, doc, name, format) VALUES (:userId, :doc, :name, :format)";
            $stmt = $connection->prepare($sql);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':doc', $fileContent, PDO::PARAM_LOB);
            $stmt->bindParam(':name', $fileName, PDO::PARAM_STR);
            $stmt->bindParam(':format', $fileFormat, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $lastInsertedId = $connection->lastInsertId();
                return $lastInsertedId;
            } else {
                return false;

            }
        }
    }
}
?>