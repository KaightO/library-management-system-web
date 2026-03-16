<?php
// simple PHP + MySQL dashboard

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'library_db';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$totalBooks = 0;
$availableCopies = 0;
$issuedCopies = 0;
$totalMembers = 0;

$res = $conn->query('SELECT COUNT(*) AS c, COALESCE(SUM(copies_available),0) AS avail, COALESCE(SUM(copies_total),0) AS total FROM books');
if ($res) {
    $row = $res->fetch_assoc();
    $totalBooks = (int)$row['c'];
    $availableCopies = (int)$row['avail'];
    $totalCopies = (int)$row['total'];
    $issuedCopies = max(0, $totalCopies - $availableCopies);
}

$res2 = $conn->query('SELECT COUNT(*) AS c FROM members');
if ($res2) {
    $row2 = $res2->fetch_assoc();
    $totalMembers = (int)$row2['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Library of Alexandria</title>
    <link rel="stylesheet" href="heisenberg.css">
</head>
<body>
  <div class="app">
    <aside class="sidebar">
      <div class="logo">
        <img src="pic.jpg" alt="Not found">
        <div>
            <b>Library of Alexandria</b>  
          <div class="subtitle">Management Dashboard</div>
        </div>
      </div>

      <nav class="nav">
        <div class="nav-section">OVERVIEW</div>
        <a class="active" href="library.php">Dashboard</a>

        <div class="nav-section">CATALOGUE</div>
        <a href="book.php">Books</a>
        <a href="addbook.php">Add Book</a>
        <a href="returnbook.php">Return Book</a>

        <div class="nav-section">MEMBERS</div>
        <a href="members.php">Members</a>
      </nav>

      <div class="sidebar-footer">
        <div class="role">Signed in as</div>
        <b>Admin User</b>
        <div class="role">Role: Librarian</div>
      </div>
    </aside>

    <main class="content">
      <header class="topbar">
        <h1>Dashboard</h1>
        <div class="actions">
          <form class="search" action="book.php" method="get">
            <input type="text" name="q" placeholder="Search by title, author...">
          </form>
          <a class="btn primary" href="addbook.php">+ Add Book</a>
        </div>
      </header>

      <section class="grid">
        <div class="card span-3">
          <h2>Total Books</h2>
          <div class="stat">
            <div class="value"><?php echo (int)$totalBooks; ?></div>
            <div class="hint">catalogued</div>
          </div>
        </div>
        <div class="card span-3">
          <h2>Available</h2>
          <div class="stat">
            <div class="value"><?php echo (int)$availableCopies; ?></div>
            <div class="hint">copies ready to issue</div>
          </div>
        </div>
        <div class="card span-3">
          <h2>Issued</h2>
          <div class="stat">
            <div class="value"><?php echo (int)$issuedCopies; ?></div>
            <div class="hint">copies currently out</div>
          </div>
        </div>
        <div class="card span-3">
          <h2>Members</h2>
          <div class="stat">
            <div class="value"><?php echo (int)$totalMembers; ?></div>
            <div class="hint">in directory</div>
          </div>
        </div>

        <div class="card span-6" style="grid-column: 4 / span 6;">
          <h2>Quick actions</h2>
          <a class="btn primary" href="addbook.php" style="width:100%">Add a new book</a>
          <div style="height:10px"></div>
          <a class="btn" href="book.php" style="width:100%">View / edit catalogue</a>
          <div style="height:10px"></div>
          <a class="btn" href="returnbook.php" style="width:100%">Return a book</a>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
