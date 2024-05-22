<?php
session_start();

header('Content-Type: application/json');

// Datenbankverbindung herstellen
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "blog";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$request_method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '/';

function response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function is_logged_in() {
    return isset($_SESSION['user']);
}

function get_logged_in_user() {
    return $_SESSION['user'] ?? null;
}

// API Endpoints
switch ($path) {
    case '/api/session':
        if ($request_method == 'GET') {
            if (is_logged_in()) {
                response(['status' => 'logged_in'], 200);
            } else {
                response(['status' => 'not_logged_in'], 401);
            }
        } elseif ($request_method == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $user = $input['user'] ?? '';
            $pass = $input['pass'] ?? '';

            $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
            $stmt->bind_param("s", $user);
            $stmt->execute();
            $stmt->bind_result($hashed_password);
            $stmt->fetch();
            $stmt->close();

            if ($hashed_password && password_verify($pass, $hashed_password)) {
                $_SESSION['user'] = $user;
                response(['status' => 'logged_in'], 200);
            } else {
                response(['status' => 'invalid_credentials'], 401);
            }
        } elseif ($request_method == 'DELETE') {
            session_destroy();
            response(['status' => 'logged_out'], 200);
        }
        break;

    case '/api/articles':
        if ($request_method == 'GET') {
            $id = $_GET['id'] ?? null;
            if ($id) {
                $stmt = $conn->prepare("SELECT * FROM articles WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $article = $result->fetch_assoc();
                $stmt->close();
                if ($article) {
                    response($article, 200);
                } else {
                    response(['status' => 'not_found'], 404);
                }
            } else {
                $limit = $_GET['limit'] ?? 3;
                $offset = $_GET['offset'] ?? 0;
                $created_since = $_GET['created_since'] ?? date('Y-m-d H:i:s');
                $author = $_GET['author'] ?? null;

                $query = "SELECT * FROM articles WHERE created >= ?";
                $params = [$created_since];
                $types = "s";

                if ($author) {
                    $query .= " AND author = ?";
                    $params[] = $author;
                    $types .= "s";
                }

                $query .= " LIMIT ? OFFSET ?";
                $params[] = (int)$limit;
                $params[] = (int)$offset;
                $types .= "ii";

                $stmt = $conn->prepare($query);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                $articles = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                response($articles, 200);
            }
        } elseif ($request_method == 'POST') {
            if (!is_logged_in()) {
                response(['status' => 'not_logged_in'], 401);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $author = get_logged_in_user();
            $title = $input['title'];
            $created = date('Y-m-d H:i:s');

            $stmt = $conn->prepare("INSERT INTO articles (author, title, created) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $author, $title, $created);
            $stmt->execute();
            $new_article_id = $stmt->insert_id;
            $stmt->close();

            $new_article = ['id' => $new_article_id, 'author' => $author, 'title' => $title, 'created' => $created];
            response($new_article, 201);
        }
        break;

    case '/api/comments':
        if ($request_method == 'GET') {
            $article_id = $_GET['article_id'] ?? null;
            if ($article_id) {
                $stmt = $conn->prepare("SELECT * FROM comments WHERE article_id = ?");
                $stmt->bind_param("i", $article_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $comments = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                response($comments, 200);
            } else {
                response(['status' => 'article_id_missing'], 400);
            }
        } elseif ($request_method == 'POST') {
            if (!is_logged_in()) {
                response(['status' => 'not_logged_in'], 401);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $article_id = $input['article_id'];
            $comment = $input['comment'];
            $author = get_logged_in_user();

            $stmt = $conn->prepare("INSERT INTO comments (article_id, comment, author) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $article_id, $comment, $author);
            $stmt->execute();
            $new_comment_id = $stmt->insert_id;
            $stmt->close();

            $new_comment = ['id' => $new_comment_id, 'article_id' => $article_id, 'comment' => $comment, 'author' => $author];
            response($new_comment, 201);
        } elseif ($request_method == 'DELETE') {
            if (!is_logged_in()) {
                response(['status' => 'not_logged_in'], 401);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $comment_id = $input['id'] ?? null;
            if ($comment_id) {
                $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
                $stmt->bind_param("i", $comment_id);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    response(['status' => 'deleted'], 200);
                } else {
                    response(['status' => 'not_found'], 404);
                }
                $stmt->close();
            } else {
                response(['status' => 'id_missing'], 400);
            }
        }
        break;

    default:
        response(['status' => 'not_found'], 404);
        break;
}

$conn->close();
?>