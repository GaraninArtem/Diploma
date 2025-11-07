<?php
session_start();
include 'db.php';
include 'core.php';
$user = new User();

if
(isset($_POST['register'])) {
    $user->regUser();
}

if (isset($_POST['login'])) {
    $user->authUser();
}

if (isset($_POST['post'])) {
    $user->uploadPost($connection);
}

if (isset($_POST['upload'])) {
    $user->uploadImage($connection);
}

if (isset($_POST['create'])) {
    $user->createFolder($connection);
}

if (isset($_POST['set'])) {
    $user->setAssignment($connection);
    echo "Название задачи: " . $taskName . "<br>";
    echo "Содержимое: " . $taskContent . "<br>";
    echo "Исполнитель: " . $assigneeId . "<br>";
    echo "Создатель: " . $creatorId . "<br>";
}

if (isset($_SESSION['login'])) {
    $userId = $_SESSION['login'];
    $sql = "SELECT status FROM user WHERE id = :userId";
    $stmt = $connection->prepare($sql);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user !== false) {
        $status = $user['status'];
    } else {
        echo "Пользователь не найден";
    }
}

if (isset($_POST['load'])) {
    $fileName = $_POST["fileName"];
    $fileFormat = $_POST["fileFormat"];
    $file = $_FILES["file"]["tmp_name"];
    $user = new User();
    $fileId = $user->saveFileToDatabase($connection, $fileName, $fileFormat, $file, $userId);
    if ($fileId) {
        header("Location: index.php");
    } else {
        echo "Ошибка при загрузке файла.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $userId = $_POST['user_id'];

    // Создаем экземпляр класса, в котором определен метод deleteUser()
    $user = new User();
    $result = $user->deleteUser($connection, $userId);

    if ($result) {
        header("Location: index.php");
    } else {
        echo "Ошибка при удалении пользователя";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.1/css/all.css"
        integrity="sha384-gfdkjb5BdAXd+lj+gudLWI+BXq4IuLW5IT+brZEZsLFm++aCMlF1V92rMkPaX4PP" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <?php
    $articleId = $_GET['id'];
    $query = $connection->prepare("SELECT heading FROM post WHERE id = :articleId");
    $query->bindParam(':articleId', $articleId);
    $query->execute();
    $article = $query->fetch(PDO::FETCH_ASSOC);
    if ($article) {
        $articleTitle = $article['heading'];
        echo '<title>EduBase | ' . $articleTitle . '</title>';
    } else {
        echo '<title>EduBase | Статья не найдена</title>';
    }
    ?>
</head>

<body>
    <d class="container-fluid p-0 m-0 d-flex" style="height: 100vh;">
        <!-- Боковое меню -->
        <div style="background-color: #E1E8E5;" class="container flex-column flex-shrink-0 p-3 col-3">
            <a href="index.php" class="d-flex mb-md-0 link-body-emphasis text-decoration-none align-items-center">
                <span class="fs-3"><strong style="color: #1f573f;">E</strong><strong
                        style="color: #50BF8F;">du</strong><strong style="color: #1f573f;">B</strong><strong
                        style="color: #50BF8F;">ase</strong></span><span class="fs-5"
                    style="margin-left: 75px; color: #2d805c;">Центр Люберцы</span>
            </a>
            <hr class="green-divider">
            <!-- Основное содержимое бокового меню -->
            <div class="overflow-container" style="height: calc(100vh - 175px); overflow-y: auto;">
                <ul class="nav nav-pills flex-column mb-auto">
                    <li class="nav-item">
                        <?php
                        // Проверяем, авторизован ли пользователь
                        if (isset($_SESSION['login'])) {
                            // Пользователь авторизован, кнопка активна
                            echo '<a href="#" class="btn1 nav-link active" data-bs-toggle="modal" data-bs-target="#folderModal">
        <i class="fa fa-plus"></i> Добавить новую папку
    </a>';
                        } else {
                            // Пользователь не авторизован, кнопка неактивна
                            echo '<a href="#" class="btn1 nav-link disabled" data-bs-toggle="modal" data-bs-target="#folderModal">
        <i class="fa fa-plus"></i> Добавить новую папку
    </a>';
                        }
                        ?>
                    </li>
                    <?php
                    $query = $connection->prepare("SELECT f.id AS folder_id, f.name AS folder_name, p.id AS post_id, p.heading AS post_heading
    FROM folders f
    LEFT JOIN post p ON f.id = p.folder_id");
                    $query->execute();
                    $results = $query->fetchAll(PDO::FETCH_ASSOC);

                    $folders = array();
                    foreach ($results as $result) {
                        $folderId = $result['folder_id'];
                        $folderName = $result['folder_name'];
                        $postId = $result['post_id'];
                        $postHeading = $result['post_heading'];

                        // Добавляем статью в соответствующую папку
                        if (!isset($folders[$folderId])) {
                            $folders[$folderId] = array(
                                'folder_name' => $folderName,
                                'posts' => array()
                            );
                        }

                        // Добавляем статью в список статей папки
                        if (!empty($postId) && !empty($postHeading)) {
                            $folders[$folderId]['posts'][] = array(
                                'post_id' => $postId,
                                'post_heading' => $postHeading
                            );
                        }
                    }

                    // Проверяем, авторизован ли пользователь
                    $loggedIn = isset($_SESSION['login']);

                    if (empty($folders)) {
                        // Нет папок
                        if ($loggedIn) {
                            // Авторизован, выводим надпись "Добавь первую папку..."
                            echo '<div class="text-center" style="margin-top: 10px;"><div class="text-muted">Добавь первую папку...</div></div>';
                        } else {
                            // Неавторизован, выводим надпись "Доступ закрыт"
                            echo '<div class="text-center" style="margin-top: 10px;"><div class="text-muted">Доступ закрыт</div></div>';
                        }
                    } elseif ($loggedIn) {
                        // Есть папки и пользователь авторизован
                        foreach ($folders as $folderId => $folder) {
                            $folderName = $folder['folder_name'];
                            $posts = $folder['posts'];

                            echo '<div style="margin-top: 8px;" class="dropdown1 flex-column mb-auto">
            <a href="#" style="margin-left: 15px; color: #50BF8F;"
                class="d-flex link-body-emphasys mb-auto text-decoration-none align-items-center"
                data-bs-toggle="dropdown" aria-expanded="false">
                <i style="margin-right: 4px; color: #50BF8F;" class="fa fa-folder"></i>' . $folderName . '</a>
            <ul class="dropdown-menu text-small shadow">';
                            foreach ($posts as $post) {
                                $postId = $post['post_id'];
                                $postHeading = $post['post_heading'];
                                echo '<li><a class="dropdown-item" href="post.php?id=' . $postId . '">' . $postHeading . '</a></li>';
                            }
                            echo '</ul>
        </div>';
                        }
                    } else {
                        // Есть папки, но пользователь неавторизован
                        echo '<div class="text-center" style="margin-top: 10px;"><div class="text-muted">Доступ закрыт</div></div>';
                    }
                    ?>
                </ul>
            </div>
            <hr class="green-divider">
            <!-- Профиль пользователя -->
            <div class="dropdown" style="bottom: 0; left: 7px;">
                <?php
                // Проверяем, авторизован ли пользователь
                if (isset($_SESSION['login'])) {
                    // Ваше существующее подключение к базе данных
                
                    // SQL-запрос для получения данных пользователя
                    $userId = $_SESSION['login'];
                    $query = $connection->prepare("SELECT name, surname, img FROM user WHERE id = :userId");
                    $query->bindParam(':userId', $userId);
                    $query->execute();
                    $user = $query->fetch(PDO::FETCH_ASSOC);

                    $name = $user['name'];
                    $surname = $user['surname'];
                    $img = $user['img'];

                    // Если авторизован, отображаем имя, фамилию и изображение пользователя
                    echo '
        <a href="#" class="d-flex align-items-center link-body-emphasis text-decoration-none"
            data-bs-toggle="dropdown" aria-expanded="false">
            <img src="data:image/jpeg;base64,' . base64_encode($img) . '" alt="404" onerror="this.onerror=null;this.src=\'user.png\';" width="32" height="32" class="rounded-circle me-2">
            <strong>' . $name . ' ' . $surname . '</strong>
        </a>
    ';
                } else {
                    // Если не авторизован, отображаем базовое изображение и текст "Не авторизирован"
                    echo '
        <a href="#" class="d-flex align-items-center link-body-emphasis text-decoration-none"
            data-bs-toggle="dropdown" aria-expanded="false">
            <img src="user.png" alt="" width="32" height="32" class="rounded-circle me-2">
            <strong>Не авторизирован</strong>
        </a>
    ';
                }
                ?>
                <ul class="dropdown-menu text-small shadow">
                    <?php if (isset($_SESSION['login'])) { ?>
                        <li>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                                Профиль
                            </a>
                        </li>
                        <hr class="dropdown-divider">
                        <li>
                            <a class="dropdown-item" href="logout.php">Выйти</a>
                        </li>
                    <?php } else { ?>
                        <li>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#regModal">
                                Регистрация
                            </a>
                        </li>
                        <hr class="dropdown-divider">
                        <li>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logModal">
                                Войти
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </div>

        <!-- Остальное содержимое -->
        <div class="container-fluid p-0 m-0 col">
            <!-- Хедер -->
            <div style="background-color: #50BF8F;">
                <header class="d-flex justify-content-center p-3 py-3">
                    <ul class="nav nav-pills ms-auto">
                        <li class="nav-item"><a href="index.php" class="nav-link">Главная</a></li>
                        <?php if (isset($_SESSION['login'])) {
                            $userId = $_SESSION['login'];
                            $sql = "SELECT status FROM user WHERE id = :userId";
                            $stmt = $connection->prepare($sql);
                            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
                            $stmt->execute();
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($user !== false) {
                                $status = $user['status'];
                                ?>
                                <!-- Код для авторизованного пользователя -->
                                <li class="nav-item"><a href="#" class="nav-link" data-bs-toggle="modal"
                                        data-bs-target="#wysiwygModal">Написать
                                        пост</a></li>
                                <?php if ($status === 'Администратор') { ?>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link" data-bs-toggle="modal"
                                            data-bs-target="#adminModal">Администрирование</a>
                                    </li>
                                <?php } ?>
                                <li class="nav-item">
                                    <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#docModal">Документы</a>
                                </li>
                                <li class="nav-item">
                                    <a style="background-color: white; color: #50BF8F !important;" href="#" class="btn nav-link"
                                        data-bs-toggle="modal" data-bs-target="#profileModal">Профиль</a>
                                </li>
                                <?php
                            } else {
                                // Обработка ситуации, когда запрос не вернул результатов
                                echo "Пользователь не найден";
                            }
                        } else {
                            // Код для неавторизованного пользователя
                            ?>
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#logModal">Войти</a>
                            </li>
                            <li class="nav-item">
                                <a style="background-color: white; color: #50BF8F !important;" href="#" class="btn nav-link"
                                    data-bs-toggle="modal" data-bs-target="#regModal">Регистрация</a>
                            </li>
                            <?php
                        } ?>
                    </ul>
                </header>
            </div>
            <!-- Пост -->
            <div class="overflow-container"
                style="max-height: calc(100vh - 72px); overflow-y: auto; height: 100%; width: 100%; background-color: #f2fff3;">
                <?php
                // Получение id поста из поисковой строки
                $postId = $_GET['id'];

                // Здесь должен быть ваш код для подключения к базе данных
                
                // Запрос для получения данных из баз данных
                $query = $connection->prepare("SELECT p.user_id, p.id, p.create_dt, p.heading, p.content, u.name, u.surname, u.img
        FROM post p
        INNER JOIN user u ON p.user_id = u.id
        WHERE p.id = :postId");
                $query->bindParam(':postId', $postId);
                $query->execute();
                $post = $query->fetch(PDO::FETCH_ASSOC);

                // Проверка, существует ли пост с указанным id
                if (!$post) {
                    echo '<h1 style="text-align: center; color: grey;">404 страница не существует</h1>';
                } else {
                    $userId = $post['user_id'];
                    $createDt = $post['create_dt'];
                    $heading = $post['heading'];
                    $content = $post['content'];
                    $name = $post['name'];
                    $surname = $post['surname'];
                    $img = $post['img'];

                    // Вывод данных поста
                    echo '<div class="post-container mb-3">
            <div style="background-color: #E1E8E5;" class="post rounded m-3 p-3">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <img src="data:image/jpeg;base64,' . base64_encode($img) . '" height="48" width="48" class="rounded-circle mr-2" alt="404" onerror="this.onerror=null;this.src=\'user.png\';">
                    </div>
                    <div class="col">
                        <h5 class="m-0">' . $name . ' ' . $surname . '</h5>
                        <p class="text-muted m-0">' . $createDt . '</p>
                    </div>
                </div>

                <div class="heading mt-3">
                    <h5>' . $heading . '</h5>
                </div>
                <div class="content mt-3" style="overflow-wrap: break-word; word-wrap: break-word; word-break: break-word;">
                    <p>' . $content . '</p>
                </div>
            </div>
        </div>';
                }
                ?>
            </div>
        </div>
        <!--Модальное окно входа-->
        <div class="modal fade" id="logModal" tabindex="-1" aria-labelledby="logModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="logModalLabel">Вход</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" class="form-signin">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" id="email"
                                    placeholder="example@mail.ru">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль</label>
                                <input type="password" name="password" class="form-control" id="password"
                                    placeholder="Ваш пароль">
                            </div>
                            <button type="submit" name="login" class="btn2 btn btn-primary w-100"
                                value="login">Войти</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!--Модальное окно регистрации-->
        <div class="modal fade" id="regModal" tabindex="-1" aria-labelledby="regModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="regModalLabel">Регистрация</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        < class="row">
                            <div class="col-6">
                                <p>Добро пожаловать в портал <strong style="color: #1f573f;">E</strong><strong
                                        style="color: #50BF8F;">du</strong><strong
                                        style="color: #1f573f;">B</strong><strong style="color: #50BF8F;">ase</strong>!
                                </p>
                                <p>Для регистрации на этой платформе нужно иметь корпоративную почту, которую можно
                                    запросить у ответственного лица. В ином случае данная учетная запись будет
                                    удалена.
                                </p>
                            </div>
                            <div class="col-6">
                                <form method="POST" class="form-signin">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Имя</label>
                                        <input type="text" name="name" class="form-control" id="name"
                                            placeholder="Ваше имя">
                                    </div>
                                    <div class="mb-3">
                                        <label for="surname" class="form-label">Фамилия</label>
                                        <input type="text" name="surname" class="form-control" id="surname"
                                            placeholder="Ваша фамилия">
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" id="email"
                                            placeholder="example@mail.ru">
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Пароль</label>
                                        <input type="password" name="password" class="form-control" id="password"
                                            placeholder="Ваш пароль">
                                    </div>
                                    <div class="mb-3">
                                        <label for="repPassword" class="form-label">Повторите пароль</label>
                                        <input type="password" name="repPassword" class="form-control" id="repPassword"
                                            placeholder="Повтор пароля">
                                    </div>
                                    <button type="submit" name="register" class="btn2 btn btn-primary w-100"
                                        value="register">Зарегистрироваться</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--Модальное окно профиля-->
        <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="profileModalLabel">Профиль пользователя</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="text-center mb-3">
                                    <div class="profile-image">
                                        <?php
                                        if (isset($_SESSION['login'])) {
                                            $userId = $_SESSION['login'];
                                            $query = $connection->prepare("SELECT img FROM user WHERE id = :id");
                                            $query->bindParam(':id', $userId);
                                            $query->execute();
                                            $userData = $query->fetch(PDO::FETCH_ASSOC);

                                            if ($userData && $userData['img'] !== null) {
                                                $img = $userData['img'];
                                                ?>
                                                <img src="data:image/jpeg;base64,<?php echo base64_encode($img); ?>" alt="404"
                                                    height="115" width="115" class="rounded-circle"
                                                    onerror="this.onerror=null;this.src='user.png';">
                                                <?php
                                            } else {
                                                ?>
                                                <img src="user.png" alt="Фотография профиля" height="115" width="115"
                                                    class="rounded-circle">
                                                <?php
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php
                                if (isset($_SESSION['login'])) {
                                    // Получение данных пользователя из базы данных в зависимости от ID из сессии
                                    $userId = $_SESSION['login'];
                                    $query = $connection->prepare("SELECT * FROM user WHERE id = :id");
                                    $query->bindParam(':id', $userId);
                                    $query->execute();
                                    $userData = $query->fetch(PDO::FETCH_ASSOC);

                                    if ($userData) {
                                        // Извлечение значений из массива $userData
                                        $name = $userData['name'];
                                        $surname = $userData['surname'];
                                        $status = $userData['status'];
                                        ?>
                                        <p class="text-center mb-1"><?php echo $name . ' ' . $surname; ?></p>
                                        <p class="text-center text-muted mb-3"><?php echo $status; ?></p>
                                    <?php } else { ?>
                                        <p class="text-center">Пользователь не найден.</p>
                                    <?php }
                                } else { ?>
                                    <p class="text-center">Вы не авторизованы.</p>
                                <?php } ?>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-end justify-content-end">
                                    <form method="POST" class="form-image" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label for="profilePhoto" class="form-label">Фотография профиля</label>
                                            <input type="file" name="profilePhoto" class="form-control"
                                                id="profilePhoto">
                                        </div>
                                        <button type="submit" name="upload" class="btn2 btn btn-primary">Загрузить
                                            фото</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="user-profile">
                                <div class="profile-details">
                                    <?php
                                    if (isset($_SESSION['login'])) {
                                        if ($userData) {
                                            $birth = $userData['birth'];
                                            $regDate = $userData['reg_date'];
                                            $email = $userData['email'];
                                            ?>

                                            <p>Дата рождения: <?php echo $birth; ?></p>
                                            <p>Дата регистрации: <?php echo $regDate; ?></p>
                                            <p>Email: <?php echo $email; ?></p>

                                        <?php } else { ?>
                                            <p>Пользователь не найден.</p>
                                        <?php }
                                    } else { ?>
                                        <p>Вы не авторизованы.</p>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <?php
                        $current_user_id = $_SESSION['login'];

                        $sql = "
    SELECT a.id, a.create_dt, a.heading, a.content, u.name AS user_name, u.surname AS user_surname, c.name AS creator_name, c.surname AS creator_surname
    FROM assigments a
    JOIN user u ON a.user_id = u.id
    JOIN user c ON a.creator_id = c.id
    WHERE a.user_id = :user_id
";
                        $stmt = $connection->prepare($sql);
                        $stmt->bindParam(':user_id', $current_user_id);
                        $stmt->execute();
                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <div class="container-fluid">
                            <div class="row">
                                <div class="col">
                                    <h5>Задачи</h5>
                                </div>
                            </div>
                            <div class="row">
                                <?php foreach ($result as $row) { ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card">
                                            <div class="card-body d-flex flex-column">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span><?php echo $row["creator_name"] . " " . $row["creator_surname"]; ?></span>
                                                    <small class="text-muted"><?php echo $row["create_dt"]; ?></small>
                                                </div>
                                                <h5 class="card-title"><?php echo $row["heading"]; ?></h5>
                                                <p class="card-text"><?php echo $row["content"]; ?></p>
                                                <form method="POST" action="deleteTask.php">
                                                    <input type="hidden" name="id" value="<?php echo $row["id"]; ?>">
                                                    <button class="btn2 btn btn-primary w-100 mt-auto">Завершить
                                                        задачу</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Модальное окно wysiwygModal -->
        <div class="modal fade" id="wysiwygModal" tabindex="-1" aria-labelledby="wysiwygModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="wysiwygModalLabel">Написать пост</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <!-- WYSIWYG -->
                    <div class="modal-body">
                        <form method="POST" class="form-wysiwyg">
                            <div class="container md">
                                <input class="form-control" type="text" name="heading" placeholder="Заголовок"
                                    required />
                                <br>
                                <div class="d-flex btn-group" role="group" aria-label="Basic example">
                                    <button class="spanspanspan btn btn-primary" onclick="execCmd(event, 'bold')"><i
                                            class="fa fa-bold"></i></button>
                                    <button class="spanspan btn btn-primary" onclick="execCmd(event, 'italic')"><i
                                            class="fa fa-italic"></i></button>
                                    <button class="spanspan btn btn-primary" onclick="execCmd(event, 'underline')"><i
                                            class="fa fa-underline"></i></button>
                                    <button class="spanspan btn btn-primary"
                                        onclick="execCmd(event, 'strikethrough')"><i
                                            class="fa fa-strikethrough"></i></button>
                                    <button class="spanspan btn btn-primary" onclick="createLink(event)"><i
                                            class="fa fa-link"></i></button>
                                    <button class="spanspan btn btn-primary"
                                        onclick="formatBlock(event, 'h2')">H2</button>
                                    <button class="spanspan btn btn-primary"
                                        onclick="formatBlock(event, 'h3')">H3</button>
                                    <button class="span btn btn-primary"
                                        onclick="execCmd(event, 'insertHorizontalRule')"><i
                                            class="fa fa-minus"></i></button>
                                </div>
                                <div class="editor form-control" contenteditable="true" name="content_html"
                                    oninput="updateContent()"></div>
                                <input type="hidden" name="content" id="content">
                                <br>
                                <div class="d-flex justify-content-between">
                                    <select style="max-width: 150px;" class="form-control form-select float-end"
                                        name="folder" aria-label="Выберите папку" required>
                                        <option selected>Сохранить в...</option>
                                        <?php
                                        try {
                                            $query = $connection->prepare("SELECT * FROM folders");
                                            $query->execute();
                                            $folders = $query->fetchAll(PDO::FETCH_ASSOC);

                                            foreach ($folders as $folder) {
                                                echo '<option value="' . $folder['id'] . '">' . $folder['name'] . '</option>';
                                            }
                                        } catch (PDOException $e) {
                                            echo "Ошибка выполнения запроса: " . $e->getMessage();
                                        }
                                        ?>
                                    </select>
                                    <div>
                                        <input class="btn2 btn btn-primary" type="submit" name="post" value="Отправить">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Модальное окно wysiwygModal -->
        <div class="modal fade" id="wysiwygModal" tabindex="-1" aria-labelledby="wysiwygModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="wysiwygModalLabel">Написать пост</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <!-- WYSIWYG -->
                    <div class="modal-body">
                        <form method="POST" class="form-wysiwyg">
                            <div class="container md">
                                <input class="form-control" type="text" name="heading" placeholder="Заголовок"
                                    required />
                                <br>
                                <div class="d-flex btn-group" role="group" aria-label="Basic example">
                                    <button class="spanspanspan btn btn-primary" onclick="execCmd(event, 'bold')"><i
                                            class="fa fa-bold"></i></button>
                                    <button class="spanspan btn btn-primary" onclick="execCmd(event, 'italic')"><i
                                            class="fa fa-italic"></i></button>
                                    <button class="spanspan btn btn-primary" onclick="execCmd(event, 'underline')"><i
                                            class="fa fa-underline"></i></button>
                                    <button class="spanspan btn btn-primary"
                                        onclick="execCmd(event, 'strikethrough')"><i
                                            class="fa fa-strikethrough"></i></button>
                                    <button class="spanspan btn btn-primary" onclick="createLink(event)"><i
                                            class="fa fa-link"></i></button>
                                    <button class="spanspan btn btn-primary"
                                        onclick="formatBlock(event, 'h2')">H2</button>
                                    <button class="spanspan btn btn-primary"
                                        onclick="formatBlock(event, 'h3')">H3</button>
                                    <button class="span btn btn-primary"
                                        onclick="execCmd(event, 'insertHorizontalRule')"><i
                                            class="fa fa-minus"></i></button>
                                </div>
                                <div class="editor form-control" contenteditable="true" name="content_html"
                                    oninput="updateContent()"></div>
                                <input type="hidden" name="content" id="content">
                                <br>
                                <div class="d-flex justify-content-between">
                                    <select style="max-width: 150px;" class="form-control form-select float-end"
                                        name="folder" aria-label="Выберите папку" required>
                                        <option selected>Сохранить в...</option>
                                        <?php
                                        try {
                                            $query = $connection->prepare("SELECT * FROM folders");
                                            $query->execute();
                                            $folders = $query->fetchAll(PDO::FETCH_ASSOC);

                                            foreach ($folders as $folder) {
                                                echo '<option value="' . $folder['id'] . '">' . $folder['name'] . '</option>';
                                            }
                                        } catch (PDOException $e) {
                                            echo "Ошибка выполнения запроса: " . $e->getMessage();
                                        }
                                        ?>
                                    </select>
                                    <div>
                                        <input class="btn2 btn btn-primary" type="submit" name="post" value="Отправить">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Модальное окно folderModal -->
        <div class="modal fade" id="folderModal" tabindex="-1" aria-labelledby="folderModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="folderModalLabel">Создать папку</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" class="form-folder">
                            <div class="container md">
                                <input class="form-control" type="text" name="folderName" placeholder="Название папки"
                                    required style="width: 100%;">
                            </div>
                            <br>
                            <div style="text-align: right; padding-right: 11px;">
                                <input class="col btn2 btn btn-primary" type="submit" name="create" value="Создать">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Модальное окно adminModal -->
        <div class="modal fade" id="adminModal" tabindex="-1" aria-labelledby="adminModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="adminModalLabel">Администрирование</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-bordered rounded-3">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Имя</th>
                                        <th>Фамилия</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $servername = "localhost";
                                    $username = "root";
                                    $password = "";
                                    $dbname = "diploma";

                                    $conn = new mysqli($servername, $username, $password, $dbname);
                                    if ($conn->connect_error) {
                                        die("Connection failed: " . $conn->connect_error);
                                    }

                                    $sql = "SELECT id, name, surname FROM user";
                                    $result = $conn->query($sql);

                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . $row["id"] . "</td>";
                                            echo "<td>" . $row["name"] . "</td>";
                                            echo "<td>" . $row["surname"] . "</td>";
                                            echo "<td><form method='POST'><input type='hidden' name='user_id' value='" . $row["id"] . "'><input type='submit' name='delete' class='btn2 btn btn-primary w-100' value='Удалить'></form></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4'>Нет данных</td></tr>";
                                    }

                                    $conn->close();
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <form method="POST" class="w-100">
                            <div class="form-group" style="padding-bottom: 10px;">
                                <label for="task-name">Название задачи</label>
                                <input type="text" class="form-control" name="task-name"
                                    placeholder="Введите название задачи">
                            </div>
                            <div class="form-group" style="padding-bottom: 10px;">
                                <label for="task-content">Содержимое</label>
                                <textarea class="form-control" name="task-content" rows="3"
                                    placeholder="Введите содержимое задачи"></textarea>
                            </div>
                            <div class="form-group" style="padding-bottom: 10px;">
                                <label for="assignee">Исполнитель</label>
                                <select class="form-control" name="assignee">
                                    <option value="">Выберите исполнителя</option>
                                    <?php
                                    $servername = "localhost";
                                    $username = "root";
                                    $password = "";
                                    $dbname = "diploma";

                                    $conn = new mysqli($servername, $username, $password, $dbname);
                                    if ($conn->connect_error) {
                                        die("Connection failed: " . $conn->connect_error);
                                    }

                                    $sql = "SELECT id, name, surname FROM user";
                                    $result = $conn->query($sql);

                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='" . $row["id"] . "'>" . $row["name"] . " " . $row["surname"] . "</option>";
                                        }
                                    }

                                    $conn->close();
                                    ?>
                                </select>
                            </div>
                            <div style="text-align: right; padding-right: 0;">
                                <button type="submit" id="set" name="set" class="btn2 btn btn-primary">Поставить
                                    задачу</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $postQuery = $connection->prepare("SELECT id FROM post");
        $postQuery->execute();
        $posts = $postQuery->fetchAll(PDO::FETCH_ASSOC);

        foreach ($posts as $post) {
            $postId = $post['id'];
            echo '
        <div class="modal fade" id="warningModal' . $postId . '" tabindex="-1"
            aria-labelledby="warningModalLabel' . $postId . '" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="warningModalLabel' . $postId . '">Удалить пост?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" class="form-folder">
                        <input type="hidden" name="post-id-to-delete" value="' . $postId . '">
                            <div class="container md">
                                <p>Вы действительно хотите удалить пост? Восстановить его будет невозможно.</p>
                            </div>
                            <div class="d-flex justify-content-end">
                                <input class="btn2 btn btn-primary" type="submit" name="delete" value="Удалить">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    ';
        } ?>

        <!-- Модальное окно docModal -->
        <div class="modal fade" id="docModal" tabindex="-1" aria-labelledby="#docModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="docModalLabel">Документы</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                    </div>
                    <div class="modal-body">
                        <form method="post" enctype="multipart/form-data" class="w-100">
                            <div class="mb-3">
                                <label for="fileName" class="form-label">Название файла</label>
                                <input type="text" id="fileName" name="fileName" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="fileFormat" class="form-label">Формат файла</label>
                                <select id="fileFormat" name="fileFormat" class="form-control" required>
                                    <option value="">Выберите формат</option>
                                    <option value="docx">DOCX</option>
                                    <option value="pdf">PDF</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <input type="file" id="file" name="file" class="form-control" required>
                            </div>
                            <button type="submit" class="btn2 btn btn-primary w-100" name="load">Загрузить</button>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <table class="table table-bordered table-striped w-100">
                            <thead>
                                <tr>
                                    <th>Название</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT id, name, doc FROM documents";
                                $stmt = $connection->prepare($sql);
                                $stmt->execute();
                                $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                foreach ($documents as $document) {
                                    echo "<tr>";
                                    echo "<td><a href='document.php?id=" . $document['id'] . "'>" . $document['name'] . "</a></td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <script>
            function execCmd(event, command, value = null) {
                event.preventDefault();
                document.execCommand(command, false, value);
            }

            function createLink(event) {
                event.preventDefault();
                var url = prompt("Введите URL ссылки:");
                if (url) {
                    document.execCommand("createLink", false, url);
                }
            }

            function formatBlock(event, tagName) {
                event.preventDefault();
                document.execCommand('formatBlock', false, tagName);
            }

            document.querySelector('form').('submit', function () {
                var contentDiv = document.querySelector('.editor');
                var contentHtmlInput = document.querySelector('#content');
                contentHtmlInput.value = contentDiv.innerHTML;
            });

            function updateContent() {
                var editor = document.querySelector('.editor');
                var content = editor.innerHTML;
                document.getElementById('content').value = content;
            }
        </script>
</body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.0/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
    crossorigin="anonymous"></script>

</html>