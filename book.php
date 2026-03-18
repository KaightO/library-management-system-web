<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

$pageTitle  = 'Books';
$activePage = 'books';
$message    = '';
$msgType    = 'success';

// ── Handle POST actions ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Delete a book
    if ($action === 'delete_book') {
        $id = trim($_POST['id'] ?? '');
        if ($id === '') {
            $message = 'Missing book ID.';
            $msgType = 'error';
        } else {
            $stmt = $conn->prepare('DELETE FROM books WHERE id = ?');
            $stmt->bind_param('s', $id);
            if ($stmt->execute()) {
                $message = 'Book deleted.';
            } else {
                $message = ($conn->errno === 1451)
                    ? 'Cannot delete: this book has borrow records.'
                    : 'Cannot delete book: ' . $conn->error;
                $msgType = 'error';
            }
        }
    }

    // Update a book
    if ($action === 'update_book') {
        $id       = trim($_POST['id'] ?? '');
        $title    = trim($_POST['title'] ?? '');
        $author   = trim($_POST['author'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $isbn     = trim($_POST['isbn'] ?? '');
        $year     = ($_POST['year'] ?? '') !== '' ? (int)$_POST['year'] : null;
        $shelf    = trim($_POST['shelf'] ?? '');
        $language = trim($_POST['language'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $cTotal   = (int)($_POST['copies_total'] ?? 1);
        $cAvail   = (int)($_POST['copies_available'] ?? 0);

        if ($id === '' || $title === '' || $author === '' || $cTotal < 1 || $cAvail < 0 || $cAvail > $cTotal) {
            $message = 'Invalid data — check required fields and copies.';
            $msgType = 'error';
        } else {
            $stmt = $conn->prepare('UPDATE books SET isbn=?, title=?, author=?, category=?, publish_year=?, copies_total=?, copies_available=?, shelf=?, language=?, description=? WHERE id=?');
            $stmt->bind_param('ssssiiissss', $isbn, $title, $author, $category, $year, $cTotal, $cAvail, $shelf, $language, $desc, $id);
            if ($stmt->execute()) {
                $message = 'Book updated.';
            } else {
                $message = 'Failed to update: ' . $conn->error;
                $msgType = 'error';
            }
        }
    }

    // Borrow a book
    if ($action === 'borrow_book') {
        $bookId   = trim($_POST['book_id'] ?? '');
        $memberId = trim($_POST['member_id'] ?? '');

        if ($bookId === '' || $memberId === '') {
            $message = 'Book and Member are required.';
            $msgType = 'error';
        } else {
            // Check book availability
            $stmt = $conn->prepare('SELECT copies_available FROM books WHERE id = ?');
            $stmt->bind_param('s', $bookId);
            $stmt->execute();
            $book = $stmt->get_result()->fetch_assoc();

            if (!$book || (int)$book['copies_available'] <= 0) {
                $message = 'Book not available.';
                $msgType = 'error';
            } else {
                $borrowDate = date('Y-m-d');
                $dueDate    = date('Y-m-d', strtotime('+14 days'));

                $stmt = $conn->prepare('INSERT INTO borrows (book_id, member_id, borrow_date, due_date) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('ssss', $bookId, $memberId, $borrowDate, $dueDate);

                if ($stmt->execute()) {
                    $upd = $conn->prepare('UPDATE books SET copies_available = copies_available - 1 WHERE id = ?');
                    $upd->bind_param('s', $bookId);
                    $upd->execute();

                    $message = "Book borrowed. Due: $dueDate";
                } else {
                    $message = 'Borrow failed: ' . $conn->error;
                    $msgType = 'error';
                }
            }
        }
    }
}

// Fetch books
$books = $conn->query('SELECT * FROM books ORDER BY id DESC')->fetch_all(MYSQLI_ASSOC);

// Fetch dropdown data
$booksList   = $conn->query("SELECT id, title, copies_available FROM books WHERE copies_available > 0");
$membersList = $conn->query("SELECT id, name FROM members");

require_once 'header.php';
?>

<header class="topbar">
    <h1>Books</h1>
    <a class="btn primary" href="addbook.php">+ Add Book</a>
</header>

<?php if ($message !== ''): ?>
<div class="flash flash-<?php echo $msgType; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<section class="grid">

<!-- Books Table -->
<div class="card span-12">
<h2>Catalogue</h2>
<table>
<tr>
    <th>ID</th><th>Title</th><th>Author</th>
    <th>Available</th><th>Actions</th>
</tr>

<?php foreach ($books as $b): ?>
<tr>
<td><?php echo htmlspecialchars($b['id']); ?></td>
<td><?php echo htmlspecialchars($b['title']); ?></td>
<td><?php echo htmlspecialchars($b['author']); ?></td>
<td><?php echo $b['copies_available']."/".$b['copies_total']; ?></td>
<td>
<form method="post" style="display:inline">
    <input type="hidden" name="action" value="delete_book">
    <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
    <button class="btn danger">Delete</button>
</form>
</td>
</tr>
<?php endforeach; ?>

</table>
</div>

<!-- Borrow Form -->
<div class="card span-6">
<h2>Borrow Book</h2>

<form method="post">
<input type="hidden" name="action" value="borrow_book">

<label>Book</label>
<select name="book_id" required>
<option value="">Select Book</option>
<?php while($row = $booksList->fetch_assoc()): ?>
<option value="<?php echo $row['id']; ?>">
<?php echo $row['title']." (".$row['copies_available']." available)"; ?>
</option>
<?php endwhile; ?>
</select>

<label>Member</label>
<select name="member_id" required>
<option value="">Select Member</option>
<?php while($row = $membersList->fetch_assoc()): ?>
<option value="<?php echo $row['id']; ?>">
<?php echo $row['name']; ?>
</option>
<?php endwhile; ?>
</select>

<br><br>
<button class="btn primary">Borrow</button>
</form>
</div>

</section>

<?php require_once 'footer.php'; ?>