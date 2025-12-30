<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_config.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing id']);
    exit;
}
$id = (int)$_GET['id'];

if (!isset($conn)) {
    echo json_encode(['error' => 'DB connection not available']);
    exit;
}

$sql = "SELECT m.id, m.title, m.description, m.price, m.file_path, m.thumbnail_path, m.file_type, m.pages_count, m.created_at, m.purchases_count, c.name AS category_name, c.slug AS category_slug, COALESCE(CONCAT(u.first_name, ' ', u.last_name), u.email, CONCAT('User ', m.user_id)) AS creator_name
        FROM materials m
        JOIN upload_categories c ON m.category_id = c.id
        LEFT JOIN users u ON u.id = m.user_id
        WHERE m.id = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    echo json_encode(['error' => 'Not found']);
    exit;
}
$row = $res->fetch_assoc();

// Build pages array for PDFs if thumbnail pages exist in expected folder
$pages = [];
if (stristr($row['file_type'], 'pdf') || strtolower(pathinfo($row['file_path'], PATHINFO_EXTENSION)) === 'pdf') {
    // try to find thumbnails in uploads/thumbnails/{id}_page_{n}.jpg
    $thumbBase = dirname(__DIR__) . '/uploads/thumbnails/' . $id . '_page_';
    // check first 20 pages
    for ($i = 1; $i <= 50; $i++) {
        $candidate = $thumbBase . $i . '.jpg';
        if (file_exists($candidate)) {
            // convert to web path
            $web = str_replace($_SERVER['DOCUMENT_ROOT'], '', $candidate);
            // if web path empty, use relative path
            if ($web === '') $web = 'uploads/thumbnails/' . $id . '_page_' . $i . '.jpg';
            $pages[] = $web;
        } else {
            // stop when thumbnail not found (assume sequential)
            break;
        }
    }
    // if no thumbnails found, fallback to returning the file_path as single preview
    if (count($pages) === 0 && $row['thumbnail_path']) {
        $pages[] = $row['thumbnail_path'];
    }
}

// if video, include video path
$video = null;
if (stristr($row['file_type'], 'video') || in_array(strtolower(pathinfo($row['file_path'], PATHINFO_EXTENSION)), ['mp4','webm','ogg'])) {
    $video = $row['file_path'];
}

$out = [
    'id' => (int)$row['id'],
    'title' => $row['title'],
    'description' => $row['description'],
    'price' => (float)$row['price'],
    'thumbnail' => $row['thumbnail_path'] ? $row['thumbnail_path'] : $row['file_path'],
    'type' => $row['file_type'] ? $row['file_type'] : 'unknown',
    'pages_count' => $row['pages_count'] ? (int)$row['pages_count'] : (count($pages) > 0 ? count($pages) : 0),
    'pages' => $pages,
    'video' => $video,
    'category' => $row['category_name'],
    'category_slug' => $row['category_slug'],
    'creator' => $row['creator_name'] ? $row['creator_name'] : 'Unknown',
    'purchases_count' => (int)$row['purchases_count']
];

echo json_encode($out);

$stmt->close();
$conn->close();

?>