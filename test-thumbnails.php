<?php
require_once __DIR__ . '/db_config.php';

// Get latest materials to check what URLs are being used
$result = $conn->query('SELECT id, title FROM materials ORDER BY id DESC LIMIT 5');
$materials = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Thumbnail Diagnostic</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .item { margin: 20px 0; padding: 20px; border: 1px solid #ccc; }
        img { width: 400px; height: 300px; object-fit: cover; border: 2px solid red; }
        .info { color: #666; font-size: 12px; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Thumbnail Loading Diagnostic</h1>
    
    <?php foreach ($materials as $m): ?>
        <div class="item">
            <h3><?php echo htmlspecialchars($m['title']); ?> (ID: <?php echo $m['id']; ?>)</h3>
            
            <h4>Direct Image Test:</h4>
            <img src="/NoteShare/api/get-thumbnail.php?id=<?php echo $m['id']; ?>&t=<?php echo time(); ?>" alt="Thumbnail">
            
            <div class="info">
                <p><strong>Direct URL:</strong> /NoteShare/api/get-thumbnail.php?id=<?php echo $m['id']; ?>&t=<?php echo time(); ?></p>
                <p><strong>File exists at:</strong> /NoteShare/uploads/thumbnails/<?php echo $m['id']; ?>_page_1.jpg</p>
            </div>
        </div>
    <?php endforeach; ?>
</body>
</html>
