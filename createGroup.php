<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="css\communityHome.css" />

  <title>Create Community | MEDIAi</title>
  <style>
    .create-group-form {
      background: rgba(20, 18, 40, 0.7);
      border: 2px solid #a259ff;
      border-radius: 18px;
      box-shadow: 0 2px 24px 0 #0004;
      padding: 2.5rem 2rem 2rem 2rem;
      width: 100%;
      max-width: 420px;
      color: #fff;
      display: flex;
      flex-direction: column;
      gap: 1.2rem;
    }

    .create-group-form h2 {
      text-align: center;
      margin-bottom: 1.5rem;
      color: #fff;
      font-weight: 600;
      letter-spacing: 1px;
      font-size: 1.4rem;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      margin-bottom: 1.2rem;
    }

    .form-group label {
      margin-bottom: 0.5rem;
      color: #fff;
      font-size: 1.05rem;
      font-weight: 500;
    }

    .form-group input[type='text'],
    .form-group textarea {
      background: transparent;
      border: 2px solid #a3a3a3;
      border-radius: 14px;
      padding: 0.7rem 1rem;
      color: #fff;
      font-size: 1rem;
      outline: none;
      margin-bottom: 0.2rem;
      transition: border 0.2s;
    }

    .form-group input[type='text']:focus,
    .form-group textarea:focus {
      border-color: #a259ff;
    }

    .form-group textarea {
      min-height: 110px;
      resize: none;
    }

    .form-group input[type='file'] {
      background: transparent;
      color: #fff;
      border: none;
      font-size: 1rem;
      margin-top: 0.3rem;
    }

    .form-group input[type='file']::-webkit-file-upload-button {
      background: #a259ff;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 0.5rem 1.2rem;
      font-size: 1rem;
      cursor: pointer;
      margin-right: 1rem;
      transition: background 0.3s;
    }

    .form-group input[type='file']::-webkit-file-upload-button:hover {
      background: #471cc8;
    }

    .create-btn {
      width: 100%;
      background: linear-gradient(90deg, #a259ff 0%, #471cc8 100%);
      color: #fff;
      border: 1.5px solid #fff;
      border-radius: 14px;
      padding: 1rem 0;
      font-size: 1.15rem;
      font-weight: 600;
      cursor: pointer;
      margin-top: 1.2rem;
      box-shadow: 0 2px 8px #0003;
      transition: background 0.2s, border 0.2s;
    }

    .create-btn:hover {
      background: linear-gradient(90deg, #471cc8 0%, #a259ff 100%);
      border: 1.5px solid #a259ff;
    }
  </style>
</head>

<body>
  <?php
  session_start();
  require_once 'dbConnect.php';

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    if (!isset($_SESSION['user_id'])) {
      $response['message'] = 'User not logged in.';
      echo json_encode($response);
      exit();
    }
    $user_id = $_SESSION['user_id'];
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $photoFileName = null;

    // Handle image upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
      $uploadDir = 'communityImages/';
      if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
      }
      $originalName = basename($_FILES['photo']['name']);
      $targetFile = $uploadDir . $originalName;

      // Ensure unique file name
      $fileExt = pathinfo($originalName, PATHINFO_EXTENSION);
      $baseName = pathinfo($originalName, PATHINFO_FILENAME);
      $i = 1;
      while (file_exists($targetFile)) {
        $targetFile = $uploadDir . $baseName . '_' . $i . '.' . $fileExt;
        $i++;
      }

      if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
        $photoFileName = basename($targetFile);
      } else {
        $response['message'] = 'Failed to upload image.';
        echo json_encode($response);
        exit();
      }
    }

    // Insert into community table
    $stmt = $conn->prepare("INSERT INTO community (name, description, photo, community_creator) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $name, $description, $photoFileName, $user_id);
    if ($stmt->execute()) {
      $community_id = $stmt->insert_id;
      // Auto-join creator
      $join_stmt = $conn->prepare("INSERT INTO community_members (user_id, community_id) VALUES (?, ?)");
      $join_stmt->bind_param("ii", $user_id, $community_id);
      $join_stmt->execute();
      $response['success'] = true;
      $response['message'] = 'Community created and joined successfully!';
    } else {
      $response['message'] = 'Failed to create community: ' . $stmt->error;
    }
    echo json_encode($response);
    exit();
  }
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
        <button class="sidebar-btn" id="createGroupBtn" onclick="window.location.href='createGroup.php'">
          <img src="icons/createpost.svg" alt="Create" class="sidebar-icon" />
          Create Group
        </button>
      </div>
    </aside>
    <main class="create-group-main"
      style="display: flex; justify-content: center; align-items: center;
     margin-left: 700px;">

      <div class="create-group-form">
        <h2>Create New Community</h2>
        <form id="createGroupForm" enctype="multipart/form-data">
          <div class="form-group">
            <label for="communityName">Community Name</label>
            <input type="text" id="communityName" name="name" required />
          </div>
          <div class="form-group">
            <label for="communityDescription">Description</label>
            <textarea id="communityDescription" name="description" required></textarea>
          </div>
          <div class="form-group">
            <label for="communityPhoto">Community Photo</label>
            <input type="file" id="communityPhoto" name="photo" accept="image/*" />
          </div>
          <button type="submit" class="create-btn" id="createCommunityBtn">Create Community</button>
        </form>
      </div>
    </main>
  </div>
  <script>
    document.getElementById('createGroupForm').addEventListener('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(this);

      fetch('createCommunity.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Community created successfully!');
            window.location.href = 'groups.php';
          } else {
            alert('Failed to create community: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while creating the community');
        });
    });
  </script>
</body>

</html>