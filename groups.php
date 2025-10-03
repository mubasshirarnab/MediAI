<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="css\communityHome.css" />
  <link rel="stylesheet" href="css\groups.css" />
  <title>Your Groups | MEDIAi</title>
  <style>
    .discover-btn {
      background-color: #471CC8;
      color: #fff;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      margin-top: 30px;
    }
  </style>
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

  // Get user's joined communities
  $sql = "SELECT c.* 
            FROM community c 
            JOIN community_members cm ON c.id = cm.community_id 
            WHERE cm.user_id = ?";

  $stmt = $conn->prepare($sql);

  if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
  }

  $stmt->bind_param("i", $user_id);

  if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
  }

  $result = $stmt->get_result();
  $joined_communities = $result->fetch_all(MYSQLI_ASSOC);
  ?>
  <iframe
    src="navbar.php"
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
          id = "your-groups-btn"
          onclick="window.location.href='groups.php'">
          <img src="icons/yourgroup.svg" alt="Groups" class="sidebar-icon" />
          Your Groups
        </button>
        <button
          class="sidebar-btn"
          id = "discover-btn"
          onclick="window.location.href='discover.php'">
          <img src="icons/discover.svg" alt="Discover" class="sidebar-icon" />
          Discover
        </button>
        <button class="sidebar-btn" id = "create-group-btn" onclick="window.location.href='createGroup.php'">
          <img src="icons/createpost.svg" alt="Create" class="sidebar-icon" />
          Create Group
        </button>
      </div>
    </aside>
    <main class="groups-main-content">
      <div class="groups-grid">
        <?php if (empty($joined_communities)): ?>
          <div class="no-groups" style="margin-top: 250px;">
            <h2>You haven't joined any communities yet!</h2>
            <p>Discover and join communities to see them here.</p>
            <button onclick="window.location.href='discover.php'" class="discover-btn">Discover Communities</button>
          </div>
        <?php else: ?>
          <?php foreach ($joined_communities as $community): ?>
            <div class="group-card">
              <img
                src="groupsImages/<?php echo htmlspecialchars($community['photo']); ?>"
                alt="<?php echo htmlspecialchars($community['name']); ?>"
                class="group-card-img"
                onerror="this.src='groupsImages/default.jpg'" />
              <h3 class="group-card-title"><?php echo htmlspecialchars($community['name']); ?></h3>
              <p class="group-card-desc"><?php echo htmlspecialchars($community['description']); ?></p>
              <button class="group-card-btn" onclick="window.location.href='yourgroup.php?id=<?php echo $community['id']; ?>'">Visit Group</button>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>

</html>