<?php
// simple PHP + MySQL return (no loans table, just updates copies_available)

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'library_db';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$message = '';
$bookId = '';
$memberId = '';
$returnDate = '';
$condition = 'Good';
$notes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookId = isset($_POST['book_id']) ? trim($_POST['book_id']) : '';
    $memberId = isset($_POST['member_id']) ? trim($_POST['member_id']) : '';
    $returnDate = isset($_POST['return_date']) ? trim($_POST['return_date']) : '';
    $condition = isset($_POST['condition']) ? trim($_POST['condition']) : 'Good';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

    if ($bookId === '') {
        $message = 'Book ID is required.';
    } else {
        $stmt = $conn->prepare('SELECT copies_available, copies_total FROM books WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('s', $bookId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            if (!$row) {
                $message = 'Book not found.';
            } else {
                $avail = (int)$row['copies_available'];
                $total = (int)$row['copies_total'];
                if (strcasecmp($condition, 'Lost') === 0) {
                    $message = 'Book marked as lost. Available copies unchanged.';
                } else {
                    if ($avail >= $total) {
                        $message = 'All copies are already marked as available.';
                    } else {
                        $stmt2 = $conn->prepare('UPDATE books SET copies_available = copies_available + 1 WHERE id = ?');
                        if ($stmt2) {
                            $stmt2->bind_param('s', $bookId);
                            if ($stmt2->execute()) {
                                $message = 'Book returned. Available copies updated.';
                            } else {
                                $message = 'Failed to update book: ' . $conn->error;
                            }
                        }
                    }
                }
            }
        }
    }
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
      <div class="brand">
        <img src="pic.jpg" alt="Library logo">
        <div>
          <div class="title">Library of Alexandria</div>
          <div class="subtitle">Loans</div>
        </div>
      </div>

      <nav class="nav" aria-label="Primary">
        <div class="nav-section">Overview</div>
        <a href="library.php">Dashboard</a>

        <div class="nav-section">Catalogue</div>
        <a href="book.php">Books</a>
        <a href="addbook.php">Add Book</a>
        <a class="active" href="returnbook.php">Return Book</a>

        <div class="nav-section">Members</div>
        <a href="members.php">Members</a>
      </nav>

      <div class="sidebar-footer">
        <div class="role">Signed in as</div>
        <div class="name">Admin User</div>
        <div class="role">Role: Librarian</div>
      </div>
    </aside>

    <main class="content">
      <header class="topbar">
        <h1>Return Book</h1>
        <div class="actions">
          <a class="btn" href="book.php">Books</a>
        </div>
      </header>

      <section class="grid">
        <div class="card span-6">
          <h2>Return form</h2>
          <form action="returnbook.php" method="post" autocomplete="on">
            <div class="form-grid">
              <div class="field half">
                <label for="return_book_id">Book ID</label>
                <input id="return_book_id" name="book_id" placeholder="BK-00412" value="<?php echo htmlspecialchars($bookId); ?>" required>
              </div>
              <div class="field half">
                <label for="member_id">Member ID</label>
                <input id="member_id" name="member_id" placeholder="MB-0017" value="<?php echo htmlspecialchars($memberId); ?>">
              </div>

              <div class="field half">
                <label for="return_date">Return Date</label>
                <input id="return_date" name="return_date" type="date" value="<?php echo htmlspecialchars($returnDate); ?>">
              </div>
              <div class="field half">
                <label for="condition">Condition</label>
                <select id="condition" name="condition">
                  <option <?php if ($condition === 'Good') echo 'selected'; ?>>Good</option>
                  <option <?php if ($condition === 'Damaged') echo 'selected'; ?>>Damaged</option>
                  <option <?php if ($condition === 'Lost') echo 'selected'; ?>>Lost</option>
                </select>
              </div>

              <div class="field">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" placeholder="Optional remarks..."><?php echo htmlspecialchars($notes); ?></textarea>
              </div>
            </div>

            <div class="form-actions">
              <button class="btn" type="reset">Clear</button>
              <button class="btn primary" type="submit">Confirm Return</button>
            </div>
          </form>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
