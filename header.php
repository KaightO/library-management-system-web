<?php
// Shared layout header.
// Set these variables before including: $pageTitle, $activePage
// $adminName is provided by auth_check.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> &mdash; Library of Alexandria</title>
    <link rel="stylesheet" href="heisenberg.css">
</head>
<body>
  <div class="app">
    <aside class="sidebar">
      <div class="logo">
        <img src="pic.jpg" alt="Library logo">
        <div>
          <b>Library of Alexandria</b>
          <div class="subtitle">Management System</div>
        </div>
      </div>

      <nav class="nav">
        <div class="nav-section">OVERVIEW</div>
        <a <?php if ($activePage === 'dashboard') echo 'class="active"'; ?> href="library.php">Dashboard</a>

        <div class="nav-section">CATALOGUE</div>
        <a <?php if ($activePage === 'books') echo 'class="active"'; ?> href="book.php">Books</a>
        <a <?php if ($activePage === 'addbook') echo 'class="active"'; ?> href="addbook.php">Add Book</a>

        <div class="nav-section">CIRCULATION</div>
        <a <?php if ($activePage === 'returnbook') echo 'class="active"'; ?> href="returnbook.php">Return Book</a>

        <div class="nav-section">MEMBERS</div>
        <a <?php if ($activePage === 'members') echo 'class="active"'; ?> href="members.php">Members</a>
      </nav>

      <div class="sidebar-footer">
        <div class="role">Signed in as</div>
        <b><?php echo htmlspecialchars($adminName); ?></b>
        <div class="role">Role: Librarian</div>
        <a class="btn logout-btn" href="logout.php">Logout</a>
      </div>
    </aside>

    <main class="content">
