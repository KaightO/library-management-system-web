<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

$pageTitle  = 'Members';
$activePage = 'members';
$message    = '';
$msgType    = 'success';

// ── Handle POST actions ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_member') {
        $id     = trim($_POST['id'] ?? '');
        $name   = trim($_POST['name'] ?? '');
        $status = trim($_POST['status'] ?? 'Active');
        $phone  = trim($_POST['phone'] ?? '');
        $email  = trim($_POST['email'] ?? '');

        if ($id === '' || $name === '') {
            $message = 'Member ID and Name are required.';
            $msgType = 'error';
        } else {
            $stmt = $conn->prepare('INSERT INTO members (id, name, status, phone, email) VALUES (?,?,?,?,?)');
            $stmt->bind_param('sssss', $id, $name, $status, $phone, $email);
            if ($stmt->execute()) {
                $message = 'Member added.';
            } else {
                $message = ($conn->errno === 1062)
                    ? 'Member ID already exists.'
                    : 'Failed to add member: ' . $conn->error;
                $msgType = 'error';
            }
        }
    }

    if ($action === 'delete_member') {
        $id = trim($_POST['id'] ?? '');
        if ($id === '') {
            $message = 'Missing member ID.';
            $msgType = 'error';
        } else {
            $stmt = $conn->prepare('DELETE FROM members WHERE id = ?');
            $stmt->bind_param('s', $id);
            if ($stmt->execute()) {
                $message = 'Member deleted.';
            } else {
                $message = ($conn->errno === 1451)
                    ? 'Cannot delete: this member has borrow records.'
                    : 'Cannot delete member: ' . $conn->error;
                $msgType = 'error';
            }
        }
    }

    if ($action === 'update_member') {
        $id     = trim($_POST['id'] ?? '');
        $name   = trim($_POST['name'] ?? '');
        $status = trim($_POST['status'] ?? 'Active');
        $phone  = trim($_POST['phone'] ?? '');
        $email  = trim($_POST['email'] ?? '');

        if ($id === '' || $name === '') {
            $message = 'Member ID and Name are required.';
            $msgType = 'error';
        } else {
            $stmt = $conn->prepare('UPDATE members SET name=?, status=?, phone=?, email=? WHERE id=?');
            $stmt->bind_param('sssss', $name, $status, $phone, $email, $id);
            if ($stmt->execute()) {
                $message = 'Member updated.';
            } else {
                $message = 'Failed to update: ' . $conn->error;
                $msgType = 'error';
            }
        }
    }
}

// ── Search / list members ────────────────────────────────────────────
$q      = trim($_GET['q'] ?? '');
$editId = trim($_GET['edit'] ?? '');

if ($q !== '') {
    $like = "%$q%";
    $stmt = $conn->prepare('SELECT * FROM members WHERE id LIKE ? OR name LIKE ? OR phone LIKE ? OR email LIKE ? ORDER BY created_at DESC');
    $stmt->bind_param('ssss', $like, $like, $like, $like);
    $stmt->execute();
    $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $members = $conn->query('SELECT * FROM members ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);
}

$editMember = null;
if ($editId !== '') {
    $stmt = $conn->prepare('SELECT * FROM members WHERE id = ?');
    $stmt->bind_param('s', $editId);
    $stmt->execute();
    $editMember = $stmt->get_result()->fetch_assoc();
}

require_once 'header.php';
?>

      <header class="topbar">
        <h1>Members</h1>
        <div class="actions">
          <form class="search" action="members.php" method="get">
            <input type="text" name="q" placeholder="Search members..." value="<?php echo htmlspecialchars($q); ?>">
          </form>
        </div>
      </header>

      <?php if ($message !== ''): ?>
        <div class="flash flash-<?php echo $msgType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <section class="grid">
        <!-- Member table -->
        <div class="card span-12">
          <h2>Member Directory</h2>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Status</th>
                  <th>Phone</th>
                  <th>Email</th>
                  <th style="width:220px">Actions</th>
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
                    <a class="btn" href="members.php?edit=<?php echo urlencode($m['id']); ?>">Edit</a>
                    <form action="members.php" method="post" style="display:inline" onsubmit="return confirm('Delete this member?');">
                      <input type="hidden" name="action" value="delete_member">
                      <input type="hidden" name="id" value="<?php echo htmlspecialchars($m['id']); ?>">
                      <button class="btn danger" type="submit">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($members)): ?>
                <tr><td colspan="6">No members found.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Add member form -->
        <div class="card span-6">
          <h2>Add Member</h2>
          <form action="members.php" method="post" autocomplete="on">
            <input type="hidden" name="action" value="create_member">
            <div class="form-grid">
              <div class="field">
                <label for="member_id">Member ID</label>
                <input id="member_id" name="id" placeholder="MB-0001" required>
              </div>
              <div class="field">
                <label for="member_name">Name</label>
                <input id="member_name" name="name" placeholder="Full name" required>
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

        <!-- Edit member form (shown only when ?edit=ID) -->
        <?php if ($editMember): ?>
        <div class="card span-6">
          <h2>Edit Member</h2>
          <form action="members.php" method="post" autocomplete="on">
            <input type="hidden" name="action" value="update_member">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($editMember['id']); ?>">
            <div class="form-grid">
              <div class="field">
                <label>Member ID</label>
                <input value="<?php echo htmlspecialchars($editMember['id']); ?>" disabled>
              </div>
              <div class="field">
                <label for="edit_name">Name</label>
                <input id="edit_name" name="name" value="<?php echo htmlspecialchars($editMember['name']); ?>" required>
              </div>
              <div class="field">
                <label for="edit_status">Status</label>
                <select id="edit_status" name="status">
                  <?php foreach (['Active','Pending','Blocked'] as $s): ?>
                    <option <?php if ($editMember['status'] === $s) echo 'selected'; ?>><?php echo $s; ?></option>
                  <?php endforeach; ?>
                </select>
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

<?php require_once 'footer.php'; ?>
