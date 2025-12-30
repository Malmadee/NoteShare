<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_config.php';

// Check which timestamp column exists
$columnCheck = $conn->query("SHOW COLUMNS FROM materials LIKE 'created_at'");
$hasCreatedAt = $columnCheck && $columnCheck->num_rows > 0;

$columnCheck2 = $conn->query("SHOW COLUMNS FROM materials LIKE 'upload_timestamp'");
$hasUploadTimestamp = $columnCheck2 && $columnCheck2->num_rows > 0;

// Determine which column to use for timestamps
if ($hasCreatedAt) {
  $timestampColumn = 'm.created_at';
  $timestampSelect = 'm.created_at';
} elseif ($hasUploadTimestamp) {
  $timestampColumn = 'm.upload_timestamp';
  $timestampSelect = 'm.upload_timestamp as created_at';
} else {
  $timestampColumn = 'm.id';
  $timestampSelect = 'NOW() as created_at';
}

// params: page, per_page, q (keyword), category (slug), sort
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 9;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'default';
$offset = ($page - 1) * $per_page;

// basic validation
if (!isset($conn)) {
    echo json_encode(['error' => 'DB connection not available']);
    exit;
}

// Map category slugs to category names
$categorySlugMap = [
    'videos' => 'Videos',
    'exam-papers' => 'Exam Papers',
    'others' => 'Others',
    'notes' => 'Notes'
];

// Build WHERE clauses
$where = [];
$params = [];
$types = '';

if ($q !== '') {
    // search title first then description. We'll search both but scoring handled client-side.
    $where[] = "(m.title LIKE ? OR m.description LIKE ? )";
    $like = '%' . $q . '%';
    $params[] = $like; $params[] = $like;
    $types .= 'ss';
}
if ($category !== '' && isset($categorySlugMap[$category])) {
    // Map slug to category name
    $categoryName = $categorySlugMap[$category];
    $where[] = 'c.name = ?';
    $params[] = $categoryName;
    $types .= 's';
}

$whereSQL = '';
if (count($where) > 0) $whereSQL = 'WHERE ' . implode(' AND ', $where);

// Sorting
$orderBy = 'm.views DESC, ' . $timestampColumn . ' DESC';
switch (strtolower($sort)) {
    case 'most recent':
    case 'recent':
    case 'most_recent':
    case 'most recent':
        $orderBy = $timestampColumn . ' DESC';
        break;
    case 'popularity':
        $orderBy = 'm.purchases_count DESC, ' . $timestampColumn . ' DESC';
        break;
    case 'price':
    case 'price_asc':
        $orderBy = 'm.price ASC';
        break;
    case 'default':
    default:
        $orderBy = 'm.views DESC, ' . $timestampColumn . ' DESC';
}

// Count total
$countSql = "SELECT COUNT(*) as cnt FROM materials m JOIN upload_categories c ON m.category_id = c.id " . $whereSQL;
$stmt = $conn->prepare($countSql);
if ($stmt && $types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
$total = 0;
if ($res) {
    $row = $res->fetch_assoc();
    $total = (int)$row['cnt'];
}
$stmt->close();

// Main query
$sql = "SELECT m.id, m.title, m.description, m.price, m.file_path, m.thumbnail_path, m.file_type, m.pages_count, m.views, " . $timestampSelect . ", m.purchases_count, c.name AS category_name, COALESCE(CONCAT(u.first_name, ' ', u.last_name), u.email, CONCAT('User ', m.user_id)) AS creator_name
        FROM materials m
        JOIN upload_categories c ON m.category_id = c.id
        LEFT JOIN users u ON u.id = m.user_id
        " . $whereSQL . " ORDER BY " . $orderBy . " LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    exit;
}

// bind params
$bindParams = [];
if ($types !== '') {
    $bindParams[] = $types . 'ii';
    $bindParams = array_merge($bindParams, $params);
    $bindParams[] = $per_page;
    $bindParams[] = $offset;
    // build call
    $tmp = [];
    $finalTypes = $types . 'ii';
    $values = $params;
    $values[] = $per_page;
    $values[] = $offset;
    $bindNames = [];
    $bindNames[] = $finalTypes;
    for ($i = 0; $i < count($values); $i++) {
        $bindNames[] = &$values[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindNames);
} else {
    $stmt->bind_param('ii', $per_page, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    // Generate slug from category name
    $categorySlug = strtolower(str_replace(' ', '-', $row['category_name']));
    
    $items[] = [
        'id' => (int)$row['id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'price' => (float)$row['price'],
        'file_path' => $row['file_path'],
        'thumbnail' => $row['thumbnail_path'] ? $row['thumbnail_path'] : $row['file_path'],
        'type' => $row['file_type'] ? $row['file_type'] : 'unknown',
        'pages_count' => $row['pages_count'] ? (int)$row['pages_count'] : 0,
        'created_at' => $row['created_at'],
        'purchases_count' => (int)$row['purchases_count'],
        'views' => (int)$row['views'],
        'category' => $row['category_name'],
        'category_slug' => $categorySlug,
        'creator' => $row['creator_name'] ? $row['creator_name'] : 'Unknown'
    ];
}

echo json_encode([
    'page' => $page,
    'per_page' => $per_page,
    'total' => $total,
    'items' => $items
]);

$stmt->close();
$conn->close();

?>