<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Community Feed | MEDIAi</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="css\communityHome.css" />
  <link rel="stylesheet" href="css\feed.css" />
  <script src="https://unpkg.com/feather-icons"></script>
  <style>
    .write-image-input {
      margin-bottom: 1rem;
      color: #fff;
      background: #181d36;
      border: 1px solid rgb(163, 184, 239);
      border-radius: 30px;
      padding: 0.7rem 1.2rem;
      font-size: 1rem;
      cursor: pointer;
      width: 100%;
      transition: background 0.3s ease;
    }

    .feed-scrollable {
      overflow-y: auto;
      overflow-x: hidden;
      /* Remove or reduce left/right margins if present */
      margin-left: 0;
      margin-right: 0;
      /* Optionally, add padding for content spacing */
      padding: 40px 0;
      margin-left: 480px;
      margin-right: 340px;
      scrollbar-width: thin;
      /* Firefox */
      scrollbar-color: #471cc8 #181d36;
      /* Firefox */
    }

    .feed-scrollable::-webkit-scrollbar {
      width: 8px;
    }

    .feed-scrollable::-webkit-scrollbar-thumb {
      background: #471cc8;
      border-radius: 8px;
    }

    .feed-scrollable::-webkit-scrollbar-track {
      background: #181d36;
    }


    .write-image-input:hover {
      background: #1e223a;
    }

    .write-image-input::-webkit-file-upload-button {
      background: #471cc8;
      color: #fff;
      border: none;
      border-radius: 20px;
      padding: 0.5rem 1rem;
      font-size: 0.9rem;
      cursor: pointer;
      margin-right: 1rem;
      transition: background 0.3s ease;
    }

    .write-image-input::-webkit-file-upload-button:hover {
      background: #5a2de0;
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

  // Verify database connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  // Get user's joined communities
  $sql = "SELECT c.id, c.name FROM community c 
            JOIN community_members cm ON c.id = cm.community_id 
            WHERE cm.user_id = ?";

  $stmt = $conn->prepare($sql);
  if ($stmt === false) {
    die("Error preparing communities query: " . $conn->error . "<br>SQL: " . $sql);
  }

  $stmt->bind_param("i", $user_id);
  if (!$stmt->execute()) {
    die("Error executing communities query: " . $stmt->error);
  }

  $result = $stmt->get_result();
  $joined_communities = $result->fetch_all(MYSQLI_ASSOC);

  // Get posts from joined communities
  $posts = [];
  if (!empty($joined_communities)) {
    $community_ids = array_column($joined_communities, 'id');
    $safe_ids = array_map('intval', $community_ids);
    $in = implode(',', $safe_ids);
    $sql = "SELECT p.id, p.caption, p.photo, p.created_at, u.name as username, c.name as community_name,
                (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.id) as likes,
                (SELECT COUNT(*) FROM comments cm WHERE cm.post_id = p.id) as comments
                FROM posts p
                JOIN users u ON p.post_creator = u.id
                JOIN community c ON p.community_id = c.id
                WHERE p.community_id IN ($in)
                ORDER BY p.created_at DESC";
    $result = $conn->query($sql);
    if ($result === false) {
      die("Error executing posts query: " . $conn->error . "<br>SQL: " . htmlspecialchars($sql));
    }
    $posts = $result->fetch_all(MYSQLI_ASSOC);
  }
  ?>
  <?php require_once 'navbar.php'; ?>

  <div class="main-layout">
    <!-- Left Sidebar -->
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
    <!-- Middle Feed (Scrollable) -->
    <main class="feed-scrollable">
      <?php if (empty($joined_communities)): ?>
        <div class="no-communities" style="margin-top: 250px;margin-left: 200px;">
          <h2>You haven't joined any communities yet!</h2>
          <p>Discover and join communities to see posts here.</p>
          <button onclick="window.location.href='discover.php'"
            style="background-color: #471CC8; 
                   color: #fff; border: none; padding: 10px 20px;
                   border-radius: 5px; cursor: pointer;margin-top: 30px;">Discover Communities</button>
        </div>
      <?php elseif (empty($posts)): ?>
        <div class="no-posts">
          <h2>No posts yet!</h2>
          <p>Be the first to post in your communities.</p>
        </div>
      <?php else: ?>
        <?php foreach ($posts as $post): ?>
          <div class="post-card">
            <div class="post-header">
              <div class="post-user-info">
                <span class="post-user"><?php echo htmlspecialchars($post['username']); ?></span> &gt;
                <span class="post-group"><?php echo htmlspecialchars($post['community_name']); ?></span>
                <div class="post-meta"><?php echo date('H:i | d M Y', strtotime($post['created_at'])); ?></div>
              </div>
            </div>
            <div class="post-question"><?php echo htmlspecialchars($post['caption']); ?></div>
            <?php if (!empty($post['photo'])): ?>
              <div class="post-images">
                <img src="postImages/<?php echo htmlspecialchars($post['photo']); ?>" alt="Post Image" class="post-img" />
              </div>
            <?php endif; ?>
            <div class="post-actions">
              <span class="post-action" onclick="likePost(<?php echo $post['id']; ?>, this)">
                <i data-feather="thumbs-up"></i>
                <span class="like-count"><?php echo $post['likes']; ?></span>
              </span>
              <span class="post-action" onclick="toggleComments(this)">
                <i data-feather="message-circle"></i>
                <span class="comment-count"><?php echo $post['comments']; ?></span>
              </span>
            </div>
            <div class="comment-section">
              <div class="comments-list" style="display: none">
                <?php
                $stmt = $conn->prepare("SELECT c.*, u.name as username 
                                        FROM comments c 
                                        JOIN users u ON c.commentor = u.id 
                                        WHERE c.post_id = ? 
                                        ORDER BY c.commented_at DESC");
                $stmt->bind_param("i", $post['id']);
                $stmt->execute();
                $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                foreach ($comments as $comment):
                ?>
                  <div class="comment-item">
                    <b><?php echo htmlspecialchars($comment['username']); ?>:</b>
                    <?php echo htmlspecialchars($comment['comment']); ?>
                  </div>
                <?php endforeach; ?>
              </div>
              <input class="comment-input" type="text" placeholder="Write Answer" />
              <button class="comment-btn" onclick="addComment(<?php echo $post['id']; ?>, this)">Answer</button>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </main>
    <!-- Right Sidebar -->
    <aside class="rightbar-static">
      <div class="write-post-section">
        <div class="write-title">Write Post</div>
        <textarea class="write-box" id="post-content" placeholder="Write Something"></textarea>
        <input type="file" class="write-image-input" id="post-image" accept="image/*" />
        <div class="write-actions">
          <select class="write-community" id="community-select" required>
            <option value="">Community</option>
            <?php foreach ($joined_communities as $community): ?>
              <option value="<?php echo $community['id']; ?>"><?php echo htmlspecialchars($community['name']); ?></option>
            <?php endforeach; ?>
          </select>
          <button class="write-post-btn" onclick="createPost()" <?php if (empty($joined_communities)) echo 'disabled'; ?>>Post</button>
        </div>
        <?php if (empty($joined_communities)): ?>
          <div style="color: #ffb3b3; margin-top: 10px;">Join a community to post!</div>
        <?php endif; ?>
      </div>
    </aside>
  </div>
  <script>
    feather.replace();

    function toggleComments(element) {
      const commentSection = element.closest('.post-card').querySelector('.comments-list');
      commentSection.style.display = commentSection.style.display === 'none' ? 'block' : 'none';
    }

    function likePost(postId, button) {
      fetch('likePost.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            post_id: postId
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const likeCount = button.querySelector('.like-count');
            likeCount.textContent = data.likes;
          }
        });
    }

    function addComment(postId, button) {
      const commentInput = button.previousElementSibling;
      const comment = commentInput.value.trim();

      if (comment) {
        fetch('addComment.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              post_id: postId,
              comment: comment
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              const commentsList = button.closest('.comment-section').querySelector('.comments-list');
              const newComment = document.createElement('div');
              newComment.className = 'comment-item';
              newComment.innerHTML = `<b>${data.username}:</b> ${comment}`;
              commentsList.insertBefore(newComment, commentsList.firstChild);
              commentInput.value = '';
            }
          });
      }
    }

    function createPost() {
      const communityId = document.getElementById('community-select').value;
      const caption = document.getElementById('post-content').value;
      const photoInput = document.getElementById('post-image');

      if (!communityId) {
        alert('Please select a community');
        return;
      }

      if (!caption.trim()) {
        alert('Please write something in your post');
        return;
      }

      const formData = new FormData();
      formData.append('community_id', communityId);
      formData.append('caption', caption);

      if (photoInput.files.length > 0) {
        formData.append('photo', photoInput.files[0]);
      }

      fetch('createPost.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Post created successfully!');
            // Clear the form
            document.getElementById('post-content').value = '';
            document.getElementById('post-image').value = '';
            // Refresh the page to show the new post
            window.location.reload();
          } else {
            alert('Failed to create post: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while creating the post');
        });
    }
  </script>
</body>

</html>