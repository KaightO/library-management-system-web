<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

$pageTitle  = 'Return Book';
$activePage = 'returnbook';
$message    = '';
$msgType    = 'success';

// Form defaults
$bookId     = '';
$memberId   = '';
$returnDate = date('Y-m-d');
$condition  = 'Good';
$notes      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookId     = trim($_POST['book_id'] ?? '');
    $memberId   = trim($_POST['member_id'] ?? '');
    $returnDate = trim($_POST['return_date'] ?? date('Y-m-d'));
    $condition  = trim($_POST['book_condition'] ?? 'Good');
    $notes      = trim($_POST['notes'] ?? '');

    if ($bookId === '' || $memberId === '') {
        $message = 'Book ID and Member ID are required.';
        $msgType = 'error';
    } else {
        // Find the active borrow record for this book + member
        $stmt = $conn->prepare(
            "SELECT id FROM borrows WHERE book_id = ? AND member_id = ? AND status = 'Borrowed' ORDER BY borrow_date ASC LIMIT 1"
        );
        $stmt->bind_param('ss', $bookId, $memberId);
        $stmt->execute();
        $borrow = $stmt->get_result()->fetch_assoc();

        if (!$borrow) {
            $message = 'No active borrow record found for this book and member.';
            $msgType = 'error';
        } else {
            // Update the borrow record
            $returnStatus = 'Returned';
            $stmt = $conn->prepare('UPDATE borrows SET return_date = ?, book_condition = ?, status = ?, notes = ? WHERE id = ?');
            $stmt->bind_param('ssssi', $returnDate, $condition, $returnStatus, $notes, $borrow['id']);

            if ($stmt->execute()) {
                // Restore available copy unless the book is lost
                if (strcasecmp($condition, 'Lost') !== 0) {
                    $upd = $conn->prepare('UPDATE books SET copies_available = LEAST(copies_available + 1, copies_total) WHERE id = ?');
                    $upd->bind_param('s', $bookId);
                    $upd->execute();
                    $message = 'Book returned successfully.';
                } else {
                    $message = 'Book marked as lost. Copies not restored.';
                }
                // Clear form after success
                $bookId = $memberId = $notes = '';
                $returnDate = date('Y-m-d');
                $condition  = 'Good';
            } else {
                $message = 'Failed to process return: ' . $conn->error;
                $msgType = 'error';
            }
        }
    }
}

require_once 'header.php';
?>

      <header class="topbar">
        <h1>Return Book</h1>
        <div class="actions">
          <a class="btn" href="book.php">Back to Books</a>
        </div>
      </header>

      <?php if ($message !== ''): ?>
        <div class="flash flash-<?php echo $msgType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <section class="grid">
        <div class="card span-6">
          <h2>Return Form</h2>
          <form action="returnbook.php" method="post" autocomplete="on">
            <div class="form-grid">
              <div class="field">
                <label for="return_book_id">Book ID</label>
                <input id="return_book_id" name="book_id" placeholder="BK-00412" value="<?php echo htmlspecialchars($bookId); ?>" required>
              </div>
              <div class="field">
                <label for="member_id">Member ID</label>
                <input id="member_id" name="member_id" placeholder="MB-0017" value="<?php echo htmlspecialchars($memberId); ?>" required>
              </div>
              <div class="field">
                <label for="return_date">Return Date</label>
                <input id="return_date" name="return_date" type="date" value="<?php echo htmlspecialchars($returnDate); ?>">
              </div>
              <div class="field">
                <label for="book_condition">Condition</label>
                <select id="book_condition" name="book_condition">
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

<?php require_once 'footer.php'; ?>
