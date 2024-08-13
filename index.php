<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'monitoring');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Handle form submission to add a new host
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_host') {
        $name = isset($_POST['name']) ? $conn->real_escape_string($_POST['name']) : '';
        $ip = isset($_POST['ip']) ? $conn->real_escape_string($_POST['ip']) : '';

        if ($name && $ip) {
            $stmt = $conn->prepare("INSERT INTO hosts (name, ip, status) VALUES (?, ?, 'Loading')");
            $stmt->bind_param('ss', $name, $ip);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($_POST['action'] === 'edit_host') {
        $id = intval($_POST['id']);
        $name = isset($_POST['name']) ? $conn->real_escape_string($_POST['name']) : '';
        $ip = isset($_POST['ip']) ? $conn->real_escape_string($_POST['ip']) : '';

        if ($id && $name && $ip) {
            $stmt = $conn->prepare("UPDATE hosts SET name = ?, ip = ? WHERE id = ?");
            $stmt->bind_param('ssi', $name, $ip, $id);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($_POST['action'] === 'delete_host') {
        $id = intval($_POST['id']);

        if ($id) {
            $stmt = $conn->prepare("DELETE FROM hosts WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Handle ping request
if (isset($_GET['action']) && $_GET['action'] === 'ping') {
    $host = isset($_GET['host']) ? escapeshellarg($_GET['host']) : '';
    if ($host) {
        $output = [];
        $returnValue = 0;

        // Detect OS and adjust ping command
        $os = PHP_OS_FAMILY;
        if ($os === 'Windows') {
            exec("ping -n 1 $host 2>&1", $output, $returnValue);
        } else {
            exec("ping -c 1 $host 2>&1", $output, $returnValue);
        }

        $status = ($returnValue === 0) ? 'Online' : 'Offline';
        echo json_encode([
            'status' => $status,
            'output' => implode("\n", $output)
        ]);
    } else {
        echo json_encode(['status' => 'Error', 'message' => 'No host specified']);
    }
    exit;
}

// Fetch hosts from the database
$sql = "SELECT id, name, ip, status FROM hosts";
$result = $conn->query($sql);
$hosts = [];
while ($row = $result->fetch_assoc()) {
    $hosts[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PingPuls - Real Time Monitoring</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #5d6d7e;
            color: #fff;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: -webkit-sticky; /* Safari */
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        header img {
            height: 40px;
        }
        header input[type="text"] {
            padding: 5px;
            border: none;
            border-radius: 5px;
        }
        #container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            padding: 20px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 10px;
            padding: 20px;
            width: 250px;
            text-align: center;
            position: relative;
            background-color: #d5d8dc; /* Default background color for Loading */
        }
        .card.loading {
            background-color: #d5d8dc;
        }
        .card.down {
            background-color: #fcf3cf;
        }
        .card.offline {
            background-color: #fadbd8;
        }
        .card.online {
            background-color: #d4efdf;
        }
        .actions {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .actions button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
        }
        .actions button.edit {
            color: #5d6d7e;
        }
        .actions button.delete {
            color: #f44336;
        }
        .actions button.view {
            color: #2196F3;
        }
        form {
            text-align: center;
            margin: 20px;
        }
        input[type="text"] {
            padding: 10px;
            margin: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        input[type="submit"] {
            padding: 10px 20px;
            background-color: #5d6d7e;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        .modal {
            display: none;
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            width: 300px;
            text-align: center;
        }
        .modal-content input[type="text"] {
            width: calc(100% - 22px);
        }
        .modal-content button {
            margin: 5px;
        }
        .live-output {
            white-space: pre-wrap; /* Maintain formatting of ping output */
            max-height: 200px;
            overflow-y: auto;
            text-align: left;
        }
    </style>
</head>
<body>
    <header>
    <a href="index.php">
        <img src="logo.png" alt="Logo">
    </a>
        <input type="text" id="search" placeholder="Search...">
    </header>

    <form method="POST">
        <input type="hidden" name="action" value="add_host">
        <input type="text" name="name" placeholder="Host or FQDN or DNS" required>
        <input type="text" name="ip" placeholder="IP" required>
        <input type="submit" value="Add Host">
    </form>
    <hr>
    <div id="container">
        <?php foreach ($hosts as $host): ?>
            <div class="card" data-id="<?php echo $host['id']; ?>" data-ip="<?php echo $host['ip']; ?>">
                <div class="actions">
                    <button class="edit" onclick="openEditModal(<?php echo $host['id']; ?>, '<?php echo addslashes($host['name']); ?>', '<?php echo addslashes($host['ip']); ?>')">✎</button>
                    <button class="delete" onclick="openDeleteModal(<?php echo $host['id']; ?>)">🗑️</button>
                    <button class="view" onclick="openLiveViewModal('<?php echo $host['ip']; ?>')">👁️</button>
                </div>
                <br>
                <hr>
                <p><b>Host: </b><?php echo htmlspecialchars($host['name']); ?></p>
                <p><b>IP: </b><?php echo htmlspecialchars($host['ip']); ?></p>
                <p><b>Status: </b><span class="status"><?php echo htmlspecialchars($host['status']); ?></span></p>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Edit Host</h2>
            <form id="editForm" method="POST">
                <input type="hidden" name="action" value="edit_host">
                <input type="hidden" id="editId" name="id">
                <input type="text" id="editName" name="name" placeholder="Host or FQDN or DNS" required>
                <input type="text" id="editIP" name="ip" placeholder="IP" required>
                <button type="submit">Update</button>
                <button type="button" onclick="closeEditModal()">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h2>Are you sure?</h2>
            <p>This action cannot be undone.</p>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" value="delete_host">
                <input type="hidden" id="deleteId" name="id">
                <button type="submit">Delete</button>
                <button type="button" onclick="closeDeleteModal()">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Live View Modal -->
    <div id="liveViewModal" class="modal">
        <div class="modal-content">
            <h2>Live View</h2>
            <div class="live-output" id="liveOutput"></div>
            <button type="button" onclick="closeLiveViewModal()">Close</button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.card').forEach(card => {
                pingHost(card.dataset.ip, card);
                setInterval(() => pingHost(card.dataset.ip, card), 1000);
            });
        });

        function pingHost(ip, card) {
            card.classList.add('loading');
            fetch(`?action=ping&host=${encodeURIComponent(ip)}`)
                .then(response => response.json())
                .then(data => {
                    card.querySelector('.status').innerText = data.status;
                    card.classList.remove('loading');
                    card.classList.remove('online');
                    card.classList.remove('offline');
                    card.classList.remove('down');
                    if (data.status === 'Online') {
                        card.classList.add('online');
                    } else {
                        card.classList.add('offline');
                    }
                })
                .catch(error => {
                    card.querySelector('.status').innerText = 'Down';
                    card.classList.remove('loading');
                    card.classList.remove('online');
                    card.classList.remove('offline');
                    card.classList.add('down');
                });
        }

        function openEditModal(id, name, ip) {
            document.getElementById('editId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editIP').value = ip;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function openDeleteModal(id) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        function openLiveViewModal(ip) {
            document.getElementById('liveOutput').innerText = 'Loading...';
            document.getElementById('liveViewModal').style.display = 'flex';
            fetch(`?action=ping&host=${encodeURIComponent(ip)}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('liveOutput').innerText = data.output;
                })
                .catch(error => {
                    document.getElementById('liveOutput').innerText = 'Error fetching data';
                });
        }

        function closeLiveViewModal() {
            document.getElementById('liveViewModal').style.display = 'none';
        }

        document.getElementById('search').addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            document.querySelectorAll('.card').forEach(card => {
                const name = card.querySelector('p:first-of-type').innerText.toLowerCase();
                const ip = card.querySelector('p:nth-of-type(2)').innerText.toLowerCase();
                if (name.includes(searchValue) || ip.includes(searchValue)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
