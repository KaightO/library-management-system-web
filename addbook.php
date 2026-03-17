<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

$pageTitle  = 'Add Book';
$activePage = 'addbook';
$message    = '';
$msgType    = 'error'; // only shown on validation failure (success redirects)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = trim($_POST['book_id'] ?? '');
    $isbn     = trim($_POST['isbn'] ?? '');
    $year     = ($_POST['year'] ?? '') !== '' ? (int)$_POST['year'] : null;
    $title    = trim($_POST['title'] ?? '');
    $author   = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $copies   = (int)($_POST['copies'] ?? 1);
    $shelf    = trim($_POST['shelf'] ?? '');
    $language = trim($_POST['language'] ?? '');
    $desc     = trim($_POST['description'] ?? '');

    if ($id === '' || $title === '' || $author === '' || $copies < 1) {
        $message = 'Book ID, Title, Author, and Copies (>= 1) are required.';
    } else {
        $stmt = $conn->prepare(
            'INSERT INTO books (id, isbn, title, author, category, publish_year, copies_total, copies_available, shelf, language, description)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $copiesAvail = $copies;
        $stmt->bind_param('sssssiissss', $id, $isbn, $title, $author, $category, $year, $copies, $copiesAvail, $shelf, $language, $desc);

        if ($stmt->execute()) {
            header('Location: book.php');
            exit;
        } else {
            $message = ($conn->errno === 1062)
                ? 'Book ID already exists.'
                : 'Failed to add book: ' . $conn->error;
        }
    }
}

require_once 'header.php';
?>

      <header class="topbar">
        <h1>Add a New Book</h1>
        <div class="actions">
          <a class="btn" href="book.php">Back to Books</a>
        </div>
      </header>

      <?php if ($message !== ''): ?>
        <div class="flash flash-<?php echo $msgType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <section class="grid">
        <div class="card span-8">
          <h2>Book Details</h2>
          <form action="addbook.php" method="post" autocomplete="on">
            <div class="form-grid">
              <div class="field">
                <label for="book_id">Book ID</label>
                <input id="book_id" name="book_id" placeholder="BK-00001" required>
              </div>
              <div class="field">
                <label for="isbn">ISBN</label>
                <input id="isbn" name="isbn" placeholder="9780132350884">
              </div>
              <div class="field">
                <label for="year">Year</label>
                <input id="year" name="year" type="number" placeholder="2008" min="0">
              </div>
              <div class="field">
                <label for="title">Title</label>
                <input id="title" name="title" placeholder="Clean Code" required>
              </div>
              <div class="field">
                <label for="author">Author</label>
                <input id="author" name="author" placeholder="Robert C. Martin" required>
              </div>
              <div class="field">
                <label for="category">Category</label>
                <select id="category" name="category">
                  <option value="">Select category</option>
                  <option>Fiction</option>
                  <option>Non-fiction</option>
                  <option>Science</option>
                  <option>Technology</option>
                  <option>History</option>
                </select>
              </div>
              <div class="field">
                <label for="copies">Copies</label>
                <input id="copies" name="copies" type="number" value="1" min="1" required>
              </div>
              <div class="field">
                <label for="shelf">Shelf</label>
                <input id="shelf" name="shelf" placeholder="A-3">
              </div>
              <div class="field">
                <label for="language">Language</label>
                <input id="language" name="language" placeholder="English">
              </div>
              <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Optional notes about the book..."></textarea>
              </div>
            </div>
            <div class="form-actions">
              <button class="btn" type="reset">Clear</button>
              <button class="btn primary" type="submit">Save Book</button>
            </div>
          </form>
        </div>
      </section>

<?php require_once 'footer.php'; ?>
