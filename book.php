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

    // Borrow a book (creates a record in the borrows table)
    if ($action === 'borrow_book') {
        $bookId   = trim($_POST['book_id'] ?? '');
        $memberId = trim($_POST['member_id'] ?? '');

        if ($bookId === '' || $memberId === '') {
            $message = 'Book ID and Member ID are both required.';
            $msgType = 'error';
        } else {
            // Verify member exists
            $stmt = $conn->prepare('SELECT id FROM members WHERE id = ?');
            $stmt->bind_param('s', $memberId);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) {
                $message = 'Member not found.';
                $msgType = 'error';
            } else {
                // Check book availability
                $stmt = $conn->prepare('SELECT copies_available FROM books WHERE id = ?');
                $stmt->bind_param('s', $bookId);
                $stmt->execute();
                $book = $stmt->get_result()->fetch_assoc();

                if (!$book) {
                    $message = 'Book not found.';
                    $msgType = 'error';
                } elseif ((int)$book['copies_available'] <= 0) {
                    $message = 'No available copies of this book.';
                    $msgType = 'error';
                } else {
                    // Insert borrow record and decrement available copies
                    $borrowDate = date('Y-m-d');
                    $dueDate    = date('Y-m-d', strtotime('+14 days'));

                    $stmt = $conn->prepare('INSERT INTO borrows (book_id, member_id, borrow_date, due_date) VALUES (?, ?, ?, ?)');
                    $stmt->bind_param('ssss', $bookId, $memberId, $borrowDate, $dueDate);

                    if ($stmt->execute()) {
                        $upd = $conn->prepare('UPDATE books SET copies_available = copies_available - 1 WHERE id = ?');
                        $upd->bind_param('s', $bookId);
                        $upd->execute();
                        $message = "Book borrowed successfully. Due back by $dueDate.";
                    } else {
                        $message = 'Failed to record borrow: ' . $conn->error;
                        $msgType = 'error';
                    }
                }
            }
        }
    }
}

// ── Search / list books ──────────────────────────────────────────────
$q      = trim($_GET['q'] ?? '');
$editId = trim($_GET['edit'] ?? '');

if ($q !== '') {
    $like = "%$q%";
    $stmt = $conn->prepare('SELECT * FROM books WHERE id LIKE ? OR title LIKE ? OR author LIKE ? OR isbn LIKE ? ORDER BY created_at DESC');
    $stmt->bind_param('ssss', $like, $like, $like, $like);
    $stmt->execute();
    $books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $books = $conn->query('SELECT * FROM books ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);
}

// Fetch a single book for the edit form
$editBook = null;
if ($editId !== '') {
    $stmt = $conn->prepare('SELECT * FROM books WHERE id = ?');
    $stmt->bind_param('s', $editId);
    $stmt->execute();
    $editBook = $stmt->get_result()->fetch_assoc();
}

require_once 'header.php';
?>

      <header class="topbar">
        <h1>Books</h1>
        <div class="actions">
          <form class="search" action="book.php" method="get">
            <input type="text" name="q" placeholder="Search books..." value="<?php echo htmlspecialchars($q); ?>">
          </form>
          <a class="btn primary" href="addbook.php">+ Add Book</a>
        </div>
      </header>

      <?php if ($message !== ''): ?>
        <div class="flash flash-<?php echo $msgType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <section class="grid">
        <!-- Book catalogue table -->
        <div class="card span-12">
          <h2>Catalogue</h2>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Title</th>
                  <th>Author</th>
                  <th>Category</th>
                  <th>Available / Total</th>
                  <th style="width:280px">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($books as $b): ?>
                <tr>
                  <td><?php echo htmlspecialchars($b['id']); ?></td>
                  <td><?php echo htmlspecialchars($b['title']); ?></td>
                  <td><?php echo htmlspecialchars($b['author']); ?></td>
                  <td><?php echo htmlspecialchars($b['category']); ?></td>
                  <td><?php echo (int)$b['copies_available']; ?> / <?php echo (int)$b['copies_total']; ?></td>
                  <td>
                    <a class="btn" href="book.php?edit=<?php echo urlencode($b['id']); ?>">Edit</a>
                    <form action="book.php" method="post" style="display:inline" onsubmit="return confirm('Delete this book?');">
                      <input type="hidden" name="action" value="delete_book">
                      <input type="hidden" name="id" value="<?php echo htmlspecialchars($b['id']); ?>">
                      <button class="btn danger" type="submit">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($books)): ?>
                <tr><td colspan="6">No books found.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Borrow form -->
        <div class="card span-6">
          <h2>Borrow a Book</h2>
          <form action="book.php" method="post">
            <input type="hidden" name="action" value="borrow_book">
            <div class="form-grid">
              <div class="field">
                <label for="borrow_book_id">Book ID</label>
                <input id="borrow_book_id" name="book_id" placeholder="BK-00001" required>
              </div>
              <div class="field">
                <label for="borrow_member_id">Member ID</label>
                <input id="borrow_member_id" name="member_id" placeholder="MB-0017" required>
              </div>
            </div>
            <div class="form-actions">
              <button class="btn primary" type="submit">Borrow</button>
            </div>
          </form>
        </div>

        <!-- Edit form (shown only when ?edit=ID) -->
        <?php if ($editBook): ?>
        <div class="card span-6">
          <h2>Edit Book</h2>
          <form action="book.php" method="post">
            <input type="hidden" name="action" value="update_book">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($editBook['id']); ?>">
            <div class="form-grid">
              <div class="field">
                <label>Book ID</label>
                <input value="<?php echo htmlspecialchars($editBook['id']); ?>" disabled>
              </div>
              <div class="field">
                <label for="isbn">ISBN</label>
                <input id="isbn" name="isbn" value="<?php echo htmlspecialchars($editBook['isbn']); ?>">
              </div>
              <div class="field">
                <label for="title">Title</label>
                <input id="title" name="title" value="<?php echo htmlspecialchars($editBook['title']); ?>" required>
              </div>
              <div class="field">
                <label for="author">Author</label>
                <input id="author" name="author" value="<?php echo htmlspecialchars($editBook['author']); ?>" required>
              </div>
              <div class="field">
                <label for="category">Category</label>
                <input id="category" name="category" value="<?php echo htmlspecialchars($editBook['category']); ?>">
              </div>
              <div class="field">
                <label for="year">Year</label>
                <input id="year" name="year" type="number" min="0" value="<?php echo (int)$editBook['publish_year']; ?>">
              </div>
              <div class="field">
                <label for="copies_total">Total Copies</label>
                <input id="copies_total" name="copies_total" type="number" min="1" value="<?php echo (int)$editBook['copies_total']; ?>" required>
              </div>
              <div class="field">
                <label for="copies_available">Available Copies</label>
                <input id="copies_available" name="copies_available" type="number" min="0" value="<?php echo (int)$editBook['copies_available']; ?>" required>
              </div>
              <div class="field">
                <label for="shelf">Shelf</label>
                <input id="shelf" name="shelf" value="<?php echo htmlspecialchars($editBook['shelf']); ?>">
              </div>
              <div class="field">
                <label for="language">Language</label>
                <input id="language" name="language" value="<?php echo htmlspecialchars($editBook['language']); ?>">
              </div>
              <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?php echo htmlspecialchars($editBook['description']); ?></textarea>
              </div>
            </div>
            <div class="form-actions">
              <a class="btn" href="book.php">Cancel</a>
              <button class="btn primary" type="submit">Save Changes</button>
            </div>
          </form>
        </div>
        <?php endif; ?>
      </section>

<?php require_once 'footer.php'; ?>
