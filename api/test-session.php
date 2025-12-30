<?php
// Simple test helper - sets a session user id for local testing
// IMPORTANT: Remove or restrict this file on production.
session_start();
// Set user id 1 for testing
$_SESSION['user_id'] = 1;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Test Session — NoteShare</title>
  <style>body{font-family: Arial,Helvetica,sans-serif;padding:30px}</style>
</head>
<body>
  <h2>Test Session Set</h2>
  <p>This page set <code>$_SESSION['user_id'] = 1</code> for your browser session. You can now test uploads as a logged-in user.</p>
  <p>Next steps:</p>
  <ol>
    <li>Open <a href="/NoteShare/uploads.html">/NoteShare/uploads.html</a> in the same browser tab.</li>
    <li>Fill the upload form and submit. The backend will treat you as user ID 1 for this session.</li>
  </ol>
  <p><strong>Security note:</strong> This file is for local testing only. Remove it after use.</p>
</body>
</html>