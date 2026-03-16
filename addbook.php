<?php
// very simple PHP + MySQL (no includes, no custom functions)

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
    $id   = isset($_POST['book_id']) ? trim($_POST['book_id']) : '';
    $isbn = isset($_POST['isbn']) ? trim($_POST['isbn']) : '';
    $year = isset($_POST['year']) && $_POST['year'] !== '' ? (int)$_POST['year'] : null;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $author = isset($_POST['author']) ? trim($_POST['author']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $copies = isset($_POST['copies']) ? (int)$_POST['copies'] : 1;
    $shelf = isset($_POST['shelf']) ? trim($_POST['shelf']) : '';
    $language = isset($_POST['language']) ? trim($_POST['language']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    if ($id === '' || $title === '' || $author === '' || $copies < 1) {
        $message = 'Please fill Book ID, Title, Author, and Copies (>=1).';
    } else {
        $sql = "INSERT INTO books (id, isbn, title, author, category, publish_year, copies_total, copies_available, shelf, language, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $copiesAvail = $copies;
            $stmt->bind_param(
                "sssssiissss",
                $id,
                $isbn,
                $title,
                $author,
                $category,
                $year,
                $copies,
                $copiesAvail,
                $shelf,
                $language,
                $description
            );
            if ($stmt->execute()) {
                header("Location: book.php");
                exit;
            } else {
                if ($conn->errno === 1062) {
                    $message = 'Book ID already exists.';
                } else {
                    $message = 'Failed to add book: ' . $conn->error;
                }
            }
        } else {
            $message = 'Failed to prepare query.';
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
      <div class="logo">
        <img src="pic.jpg" alt="Library logo">
        <div>
          <b>Library of Alexandria</b>
          <div class="subtitle">Catalogue</div>
        </div>
      </div>

      <nav class="nav">
        <div class="nav-section">OVERVIEW</div>
        <a href="library.php">Dashboard</a>

        <div class="nav-section">CATALOGUE</div>
        <a href="book.php">Books</a>
        <a class="active" href="addbook.php">Add Book</a>
        <a href="returnbook.php">Return Book</a>

        <div class="nav-section">MEMBERS</div>
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
        <h1>Add a New Book</h1>
        <div class="actions">
          <a class="btn" href="book.php">Back to Books</a>
        </div>
      </header>

      <section class="grid">
        <div class="card span-8">
          <h2>Book details</h2>
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
                <label>Status</label>
                <input value="Auto (based on available copies)" readonly>
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
    </main>
  </div>
</body>
</html>
