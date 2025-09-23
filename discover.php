<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="css\communityHome.css" />
  <link rel="stylesheet" href="css\groups.css" />
  <title>Discover Communities | MEDIAi</title>
</head>

<body>
  <?php
  session_start();
  require_once 'dbConnect.php';

  if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
  }

  $user_id = $_SESSION['user_id'];

  // Get all communities
  $sql = "SELECT c.*, 
            CASE WHEN cm.user_id IS NOT NULL THEN 1 ELSE 0 END as is_member
            FROM community c 
            LEFT JOIN community_members cm ON c.id = cm.community_id AND cm.user_id = ?
            ORDER BY c.name";

  $stmt = $conn->prepare($sql);

  if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
  }

  $stmt->bind_param("i", $user_id);

  if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
  }

  $result = $stmt->get_result();
  $communities = $result->fetch_all(MYSQLI_ASSOC);

  // Debug information
  echo "<!-- Debug Info: ";
  print_r($communities);
  echo " -->";
  ?>
  <iframe
    src="Navbar\navbar.html"
    frameborder="0"
    style="width: 100%; height: 80px"></iframe>

  <?php require_once 'navbar.php'; ?>


  <div class="main-layout">
    <aside class="sidebar">
      <div class="sidebar-search">
        <img src="icons/searchbar.svg" alt="Search" class="sidebar-icon" />
        <input type="text" placeholder="Search Community" />
      </div>
      <div class="sidebar-menu">
        <button
          class="sidebar-btn"
          onclick="window.location.href='feed.php'">
          <img src="icons/youfeed.svg" alt="Feed" class="sidebar-icon" />
          Your Feed
        </button>
        <button
          class="sidebar-btn"
          onclick="window.location.href='groups.php'">
          <img src="icons/yourgroup.svg" alt="Groups" class="sidebar-icon" />
          Your Groups
        </button>
        <button
          class="sidebar-btn"
          onclick="window.location.href='discover.php'">
          <img src="icons/discover.svg" alt="Discover" class="sidebar-icon" />
          Discover
        </button>
        <button class="sidebar-btn" onclick="window.location.href='createGroup.php'">
          <img src="icons/createpost.svg" alt="Create" class="sidebar-icon" />
          Create Group
        </button>
      </div>
    </aside>
    <main class="groups-main-content">
      <div class="groups-grid">
        <?php if (empty($communities)): ?>
          <div class="no-groups">
            <h2>No communities found!</h2>
            <p>Be the first to create a community.</p>
            <button onclick="window.location.href='createGroup.php'">Create Community</button>
          </div>
        <?php else: ?>
          <?php foreach ($communities as $community): ?>
            <div class="group-card">
              <img
                src="groupsImages/<?php echo htmlspecialchars($community['photo']); ?>"
                alt="<?php echo htmlspecialchars($community['name']); ?>"
                class="group-card-img"
                onerror="this.src='groupsImages/default.jpg'" />
              <h3 class="group-card-title"><?php echo htmlspecialchars($community['name']); ?></h3>
              <p class="group-card-desc"><?php echo htmlspecialchars($community['description']); ?></p>
              <?php if ($community['is_member']): ?>
                <button class="group-card-btn" onclick="window.location.href='yourgroup.php?id=<?php echo $community['id']; ?>'">Visit Group</button>
              <?php else: ?>
                <button class="group-card-btn" onclick="joinCommunity(<?php echo $community['id']; ?>, this)">Join Group</button>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </main>
  </div>
  <script>
    function joinCommunity(communityId, button) {
      fetch('joinCommunity.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            community_id: communityId
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            button.textContent = 'Visit Group';
            button.onclick = function() {
              window.location.href = 'yourgroup.php' + communityId;
            };
          } else {
            alert('Failed to join community');
          }
        });
    }
  </script>
</body>

</html>