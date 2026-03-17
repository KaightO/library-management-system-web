<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

// Aggregate statistics
$totalBooks = $availableCopies = $issuedCopies = $totalMembers = $activeBorrows = 0;

$res = $conn->query('SELECT COUNT(*) AS c, COALESCE(SUM(copies_available),0) AS avail, COALESCE(SUM(copies_total),0) AS total FROM books');
if ($res) {
    $row             = $res->fetch_assoc();
    $totalBooks      = (int)$row['c'];
    $availableCopies = (int)$row['avail'];
    $issuedCopies    = max(0, (int)$row['total'] - $availableCopies);
}

$res = $conn->query('SELECT COUNT(*) AS c FROM members');
if ($res) $totalMembers = (int)$res->fetch_assoc()['c'];

$res = $conn->query("SELECT COUNT(*) AS c FROM borrows WHERE status = 'Borrowed'");
if ($res) $activeBorrows = (int)$res->fetch_assoc()['c'];

require_once 'header.php';
?>

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
            <div class="value"><?php echo $totalBooks; ?></div>
            <div class="hint">catalogued</div>
          </div>
        </div>
        <div class="card span-3">
          <h2>Available</h2>
          <div class="stat">
            <div class="value"><?php echo $availableCopies; ?></div>
            <div class="hint">copies on shelf</div>
          </div>
        </div>
        <div class="card span-3">
          <h2>Borrowed</h2>
          <div class="stat">
            <div class="value"><?php echo $activeBorrows; ?></div>
            <div class="hint">active borrows</div>
          </div>
        </div>
        <div class="card span-3">
          <h2>Members</h2>
          <div class="stat">
            <div class="value"><?php echo $totalMembers; ?></div>
            <div class="hint">in directory</div>
          </div>
        </div>

        <div class="card span-6" style="grid-column: 4 / span 6;">
          <h2>Quick Actions</h2>
          <a class="btn primary" href="addbook.php" style="width:100%">Add a new book</a>
          <div style="height:10px"></div>
          <a class="btn" href="book.php" style="width:100%">View / edit catalogue</a>
          <div style="height:10px"></div>
          <a class="btn" href="returnbook.php" style="width:100%">Return a book</a>
        </div>
      </section>

<?php require_once 'footer.php'; ?>
