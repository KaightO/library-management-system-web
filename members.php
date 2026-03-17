<?php
// simple PHP + MySQL for members (no includes)

require_once 'db_connect.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'create_member') {
        $id = isset($_POST['id']) ? trim($_POST['id']) : '';
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'Active';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';

        if ($id === '' || $name === '') {
            $message = 'Member ID and Name are required.';
        } else {
            $stmt = $conn->prepare('INSERT INTO members (id, name, status, phone, email) VALUES (?,?,?,?,?)');
            if ($stmt) {
                $stmt->bind_param('sssss', $id, $name, $status, $phone, $email);
                if ($stmt->execute()) {
                    $message = 'Member added.';
                } else {
                    if ($conn->errno === 1062) {
                        $message = 'Member ID already exists.';
                    } else {
                        $message = 'Failed to add member: ' . $conn->error;
                    }
                }
            }
        }
    }

    if ($action === 'delete_member') {
        $id = isset($_POST['id']) ? trim($_POST['id']) : '';
        if ($id === '') {
            $message = 'Missing member id.';
        } else {
            $stmt = $conn->prepare('DELETE FROM members WHERE id=?');
            if ($stmt) {
                $stmt->bind_param('s', $id);
                if ($stmt->execute()) {
                    $message = 'Member deleted.';
                } else {
                    $message = 'Cannot delete member: ' . $conn->error;
                }
            }
        }
    }

    if ($action === 'update_member') {
        $id = isset($_POST['id']) ? trim($_POST['id']) : '';
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'Active';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';

        if ($id === '' || $name === '') {
            $message = 'Member ID and Name are required.';
        } else {
            $stmt = $conn->prepare('UPDATE members SET name=?, status=?, phone=?, email=? WHERE id=?');
            if ($stmt) {
                $stmt->bind_param('sssss', $name, $status, $phone, $email, $id);
                if ($stmt->execute()) {
                    $message = 'Member updated.';
                } else {
                    $message = 'Failed to update: ' . $conn->error;
                }
            }
        }
    }
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$editId = isset($_GET['edit']) ? trim($_GET['edit']) : '';

$members = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = $conn->prepare('SELECT * FROM members WHERE id LIKE ? OR name LIKE ? OR phone LIKE ? OR email LIKE ? ORDER BY created_at DESC');
    if ($stmt) {
        $stmt->bind_param('ssss', $like, $like, $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $members = $result->fetch_all(MYSQLI_ASSOC);
        }
    }
} else {
    $res = $conn->query('SELECT * FROM members ORDER BY created_at DESC');
    if ($res) {
        $members = $res->fetch_all(MYSQLI_ASSOC);
    }
}

$editMember = null;
if ($editId !== '') {
    $stmt = $conn->prepare('SELECT * FROM members WHERE id=?');
    if ($stmt) {
        $stmt->bind_param('s', $editId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $editMember = $result->fetch_assoc();
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
          <div class="subtitle">People</div>
        </div>
      </div>

      <nav class="nav">
        <div class="nav-section">OVERVIEW</div>
        <a href="library.php">Dashboard</a>

        <div class="nav-section">CATALOGUE</div>
        <a href="book.php">Books</a>
        <a href="addbook.php">Add Book</a>
        <a href="returnbook.php">Return Book</a>

        <div class="nav-section">MEMBERS</div>
        <a class="active" href="members.php">Members</a>
      </nav>

      <div class="sidebar-footer">
        <div class="role">Signed in as</div>
        <div class="name">Admin User</div>
        <div class="role">Role: Librarian</div>
      </div>
    </aside>

    <main class="content">
      <header class="topbar">
        <h1>Members</h1>
        <div class="actions">
          <form class="search" action="members.php" method="get">
            <input type="text" name="q" placeholder="Search members..." value="<?php echo htmlspecialchars($q); ?>">
          </form>
        </div>
      </header>

      <section class="grid">
        <div class="card span-12">
          <h2>Member directory</h2>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Member ID</th>
                  <th>Name</th>
                  <th>Status</th>
                  <th>Phone</th>
                  <th>Email</th>
                  <th style="width: 260px">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($members as $m): ?>
                <tr>
                  <td><?php echo htmlspecialchars($m['id']); ?></td>
                  <td><?php echo htmlspecialchars($m['name']); ?></td>
                  <td><?php echo htmlspecialchars($m['status']); ?></td>
                  <td><?php echo htmlspecialchars($m['phone']); ?></td>
                  <td><?php echo htmlspecialchars($m['email']); ?></td>
                  <td>
                    <a class="btn small" href="members.php?edit=<?php echo urlencode($m['id']); ?>">Edit</a>
                    <form action="members.php" method="post" style="display:inline" onsubmit="return confirm('Delete this member?');">
                      <input type="hidden" name="action" value="delete_member">
                      <input type="hidden" name="id" value="<?php echo htmlspecialchars($m['id']); ?>">
                      <button class="btn small danger" type="submit">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (count($members) === 0): ?>
                <tr><td colspan="6">No members found.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card span-6">
          <h2>Add member</h2>
          <form action="members.php" method="post" autocomplete="on">
            <input type="hidden" name="action" value="create_member">
            <div class="form-grid">
              <div class="field">
                <label for="member_id">Member ID</label>
                <input id="member_id" name="id" placeholder="MB-0001" required>
              </div>
              <div class="field">
                <label for="member_status">Status</label>
                <select id="member_status" name="status">
                  <option>Active</option>
                  <option>Pending</option>
                  <option>Blocked</option>
                </select>
              </div>
              <div class="field">
                <label for="member_name">Name</label>
                <input id="member_name" name="name" placeholder="Full name" required>
              </div>
              <div class="field">
                <label for="member_phone">Phone</label>
                <input id="member_phone" name="phone" placeholder="+1 555 0000">
              </div>
              <div class="field">
                <label for="member_email">Email</label>
                <input id="member_email" name="email" placeholder="name@example.com">
              </div>
            </div>
            <div class="form-actions">
              <button class="btn primary" type="submit">Add</button>
            </div>
          </form>
        </div>

        <?php if ($editMember): ?>
          <div class="card span-6">
            <h2>Edit member</h2>
            <form action="members.php" method="post" autocomplete="on">
              <input type="hidden" name="action" value="update_member">
              <input type="hidden" name="id" value="<?php echo htmlspecialchars($editMember['id']); ?>">
              <div class="form-grid">
                <div class="field">
                  <label>Member ID</label>
                  <input value="<?php echo htmlspecialchars($editMember['id']); ?>" readonly>
                </div>
                <div class="field">
                  <label for="edit_status">Status</label>
                  <select id="edit_status" name="status">
                    <?php foreach (['Active','Pending','Blocked'] as $s): ?>
                      <option <?php if ($editMember['status'] === $s) echo 'selected'; ?>><?php echo htmlspecialchars($s); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="field">
                  <label for="edit_name">Name</label>
                  <input id="edit_name" name="name" value="<?php echo htmlspecialchars($editMember['name']); ?>" required>
                </div>
                <div class="field">
                  <label for="edit_phone">Phone</label>
                  <input id="edit_phone" name="phone" value="<?php echo htmlspecialchars($editMember['phone']); ?>">
                </div>
                <div class="field">
                  <label for="edit_email">Email</label>
                  <input id="edit_email" name="email" value="<?php echo htmlspecialchars($editMember['email']); ?>">
                </div>
              </div>
              <div class="form-actions">
                <a class="btn" href="members.php">Cancel</a>
                <button class="btn primary" type="submit">Save</button>
              </div>
            </form>
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>
</body>
</html>
