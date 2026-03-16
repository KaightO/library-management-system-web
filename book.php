<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'library_db';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'delete_book') {
        $id = isset($_POST['id']) ? trim($_POST['id']) : '';
        if ($id === '') {
            $message = 'Missing book id.';
        } else {
            $stmt = $conn->prepare('DELETE FROM books WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('s', $id);
                if ($stmt->execute()) {
                    $message = 'Book deleted.';
                } else {
                    $message = 'Cannot delete book: ' . $conn->error;
                }
            }
        }
    }

    if ($action === 'update_book') {
        $id = isset($_POST['id']) ? trim($_POST['id']) : '';
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $author = isset($_POST['author']) ? trim($_POST['author']) : '';
        $category = isset($_POST['category']) ? trim($_POST['category']) : '';
        $isbn = isset($_POST['isbn']) ? trim($_POST['isbn']) : '';
        $year = isset($_POST['year']) && $_POST['year'] !== '' ? (int)$_POST['year'] : null;
        $shelf = isset($_POST['shelf']) ? trim($_POST['shelf']) : '';
        $language = isset($_POST['language']) ? trim($_POST['language']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $copiesTotal = isset($_POST['copies_total']) ? (int)$_POST['copies_total'] : 1;
        $copiesAvail = isset($_POST['copies_available']) ? (int)$_POST['copies_available'] : 0;

        if ($id === '' || $title === '' || $author === '' || $copiesTotal < 1 || $copiesAvail < 0 || $copiesAvail > $copiesTotal) {
            $message = 'Invalid book data (check copies fields).';
        } else {
            $stmt = $conn->prepare('UPDATE books SET isbn=?, title=?, author=?, category=?, publish_year=?, copies_total=?, copies_available=?, shelf=?, language=?, description=? WHERE id=?');
            if ($stmt) {
                $stmt->bind_param(
                    'ssssiiissss',
                    $isbn,
                    $title,
                    $author,
                    $category,
                    $year,
                    $copiesTotal,
                    $copiesAvail,
                    $shelf,
                    $language,
                    $description,
                    $id
                );
                if ($stmt->execute()) {
                    $message = 'Book updated.';
                } else {
                    $message = 'Failed to update: ' . $conn->error;
                }
            }
        }
    }

    if ($action === 'borrow_book') {
        $bookId = isset($_POST['book_id']) ? trim($_POST['book_id']) : '';
        if ($bookId === '') {
            $message = 'Book ID is required to borrow.';
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
                    if ($avail <= 0) {
                        $message = 'No available copies for this book.';
                    } else {
                        $stmt2 = $conn->prepare('UPDATE books SET copies_available = copies_available - 1 WHERE id = ?');
                        if ($stmt2) {
                            $stmt2->bind_param('s', $bookId);
                            if ($stmt2->execute()) {
                                $message = 'Book borrowed. Available copies updated.';
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

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$editId = isset($_GET['edit']) ? trim($_GET['edit']) : '';

$books = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = $conn->prepare('SELECT * FROM books WHERE id LIKE ? OR title LIKE ? OR author LIKE ? OR isbn LIKE ? ORDER BY created_at DESC');
    if ($stmt) {
        $stmt->bind_param('ssss', $like, $like, $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $books = $result->fetch_all(MYSQLI_ASSOC);
        }
    }
} else {
    $res = $conn->query('SELECT * FROM books ORDER BY created_at DESC');
    if ($res) {
        $books = $res->fetch_all(MYSQLI_ASSOC);
    }
}

$editBook = null;
if ($editId !== '') {
    $stmt = $conn->prepare('SELECT * FROM books WHERE id=?');
    if ($stmt) {
        $stmt->bind_param('s', $editId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $editBook = $result->fetch_assoc();
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
          <div class="subtitle">Catalogue</div>
        </div>
      </div>

      <nav class="nav" aria-label="Primary">
        <div class="nav-section">Overview</div>
        <a href="library.php">Dashboard</a>

        <div class="nav-section">Catalogue</div>
        <a class="active" href="book.php">Books</a>
        <a href="addbook.php">Add Book</a>

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
        <h1>Books</h1>
        <div class="actions">
          <form class="search" action="book.php" method="get">
            <input type="text" name="q" placeholder="Search books..." aria-label="Search books" value="<?php echo htmlspecialchars($q); ?>">
          </form>
          <a class="btn primary" href="addbook.php">+ Add Book</a>
        </div>
      </header>

      <section class="grid">
        <div class="card span-12">
          <h2>Catalogue list</h2>
          <div class="table-wrap" role="region" aria-label="Books table" tabindex="0">
            <table>
              <thead>
                <tr>
                  <th>Book ID</th>
                  <th>Title</th>
                  <th>Author</th>
                  <th>Category</th>
                  <th>Available / Total</th>
                  <th style="width: 320px">Actions</th>
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
                    <a class="btn small" href="book.php?edit=<?php echo urlencode($b['id']); ?>">Edit</a>
                    <form action="book.php" method="post" style="display:inline" onsubmit="return confirm('Delete this book?');">
                      <input type="hidden" name="action" value="delete_book">
                      <input type="hidden" name="id" value="<?php echo htmlspecialchars($b['id']); ?>">
                      <button class="btn small danger" type="submit">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (count($books) === 0): ?>
                <tr><td colspan="6">No books found.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card span-6">
          <h2>Borrow a book</h2>
          <form action="book.php" method="post" autocomplete="on">
            <input type="hidden" name="action" value="borrow_book">
            <div class="form-grid">
              <div class="field half">
                <label for="borrow_book_id">Book ID</label>
                <input id="borrow_book_id" name="book_id" placeholder="BK-00001" required>
              </div>
            </div>
            <div class="form-actions">
              <button class="btn primary" type="submit">Borrow</button>
            </div>
          </form>
        </div>

        <?php if ($editBook): ?>
        <div class="card span-6">
          <h2>Edit book</h2>
          <form action="book.php" method="post" autocomplete="on">
            <input type="hidden" name="action" value="update_book">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($editBook['id']); ?>">
            <div class="form-grid">
              <div class="field third">
                <label>Book ID</label>
                <input value="<?php echo htmlspecialchars($editBook['id']); ?>" readonly>
              </div>
              <div class="field third">
                <label for="isbn">ISBN</label>
                <input id="isbn" name="isbn" value="<?php echo htmlspecialchars($editBook['isbn']); ?>">
              </div>
              <div class="field third">
                <label for="year">Year</label>
                <input id="year" name="year" type="number" min="0" value="<?php echo htmlspecialchars((string)$editBook['publish_year']); ?>">
              </div>

              <div class="field half">
                <label for="title">Title</label>
                <input id="title" name="title" value="<?php echo htmlspecialchars($editBook['title']); ?>" required>
              </div>
              <div class="field half">
                <label for="author">Author</label>
                <input id="author" name="author" value="<?php echo htmlspecialchars($editBook['author']); ?>" required>
              </div>

              <div class="field half">
                <label for="category">Category</label>
                <input id="category" name="category" value="<?php echo htmlspecialchars($editBook['category']); ?>">
              </div>
              <div class="field half">
                <label for="shelf">Shelf</label>
                <input id="shelf" name="shelf" value="<?php echo htmlspecialchars($editBook['shelf']); ?>">
              </div>

              <div class="field third">
                <label for="copies_total">Total copies</label>
                <input id="copies_total" name="copies_total" type="number" min="1" value="<?php echo (int)$editBook['copies_total']; ?>" required>
              </div>
              <div class="field third">
                <label for="copies_available">Available copies</label>
                <input id="copies_available" name="copies_available" type="number" min="0" value="<?php echo (int)$editBook['copies_available']; ?>" required>
              </div>
              <div class="field third">
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
    </main>
  </div>
</body>
</html>
