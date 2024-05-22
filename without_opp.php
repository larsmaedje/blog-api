<?php
session_start();

header('Content-Type: application/json');

$request_method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '/';

// Simulierte Datenbanken (Array als Beispiel)
$users = [
    'user1' => 'password1',
    'user2' => 'password2'
];

$articles = [
    ['id' => 1, 'author' => 'user1', 'title' => 'Article 1', 'created' => '2023-05-20'],
    ['id' => 2, 'author' => 'user2', 'title' => 'Article 2', 'created' => '2023-05-21'],
];

$comments = [
    ['id' => 1, 'article_id' => 1, 'comment' => 'Nice article!', 'author' => 'user2'],
    ['id' => 2, 'article_id' => 1, 'comment' => 'I disagree', 'author' => 'user1'],
];

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

            if (isset($users[$user]) && $users[$user] == $pass) {
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
                foreach ($articles as $article) {
                    if ($article['id'] == $id) {
                        response($article, 200);
                    }
                }
                response(['status' => 'not_found'], 404);
            } else {
                $limit = $_GET['limit'] ?? 3;
                $offset = $_GET['offset'] ?? 0;
                $created_since = $_GET['created_since'] ?? date('Y-m-d H:i:s');
                $author = $_GET['author'] ?? null;

                $filtered_articles = array_filter($articles, function($article) use ($created_since, $author) {
                    return $article['created'] >= $created_since && (!$author || $article['author'] == $author);
                });

                $paged_articles = array_slice($filtered_articles, $offset, $limit);
                response($paged_articles, 200);
            }
        } elseif ($request_method == 'POST') {
            if (!is_logged_in()) {
                response(['status' => 'not_logged_in'], 401);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $new_article = [
                'id' => count($articles) + 1,
                'author' => get_logged_in_user(),
                'title' => $input['title'],
                'created' => date('Y-m-d H:i:s')
            ];
            $articles[] = $new_article;
            response($new_article, 201);
        }
        break;

    case '/api/comments':
        if ($request_method == 'GET') {
            $article_id = $_GET['article_id'] ?? null;
            if ($article_id) {
                $article_comments = array_filter($comments, function($comment) use ($article_id) {
                    return $comment['article_id'] == $article_id;
                });
                response($article_comments, 200);
            } else {
                response(['status' => 'article_id_missing'], 400);
            }
        } elseif ($request_method == 'POST') {
            if (!is_logged_in()) {
                response(['status' => 'not_logged_in'], 401);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $new_comment = [
                'id' => count($comments) + 1,
                'article_id' => $input['article_id'],
                'comment' => $input['comment'],
                'author' => get_logged_in_user()
            ];
            $comments[] = $new_comment;
            response($new_comment, 201);
        } elseif ($request_method == 'DELETE') {
            if (!is_logged_in()) {
                response(['status' => 'not_logged_in'], 401);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $comment_id = $input['id'] ?? null;
            if ($comment_id) {
                foreach ($comments as $key => $comment) {
                    if ($comment['id'] == $comment_id) {
                        unset($comments[$key]);
                        response(['status' => 'deleted'], 200);
                    }
                }
                response(['status' => 'not_found'], 404);
            } else {
                response(['status' => 'id_missing'], 400);
            }
        }
        break;

    default:
        response(['status' => 'not_found'], 404);
        break;
}
?>