<?php
session_start();
require_once 'dbConnect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($group_id <= 0) {
    echo "<h2>Invalid group ID.</h2>";
    exit();
}

// Fetch group info
$stmt = $conn->prepare("SELECT * FROM community WHERE id = ?");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$group = $stmt->get_result()->fetch_assoc();
if (!$group) {
    echo "<h2>Group not found.</h2>";
    exit();
}

// Fetch posts for this group
$sql = "SELECT p.id, p.caption, p.photo, p.created_at, u.name as username,
            (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.id) as likes,
            (SELECT COUNT(*) FROM comments cm WHERE cm.post_id = p.id) as comments
        FROM posts p
        JOIN users u ON p.post_creator = u.id
        WHERE p.community_id = ?
        ORDER BY p.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $group_id);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($group['name']); ?> | MEDIAi</title>
    <link rel="stylesheet" href="css/communityHome.css" />
    <link rel="stylesheet" href="css/feed.css" />
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
    <?php require_once 'navbar.php'; ?>

    <div class="main-layout">
        <aside class="sidebar">
            <div class="sidebar-search">
                <img src="icons/searchbar.svg" alt="Search" class="sidebar-icon" />
                <input type="text" placeholder="Search Community" />
            </div>
            <div class="sidebar-menu">
                <button class="sidebar-btn" onclick="window.location.href='feed.php'"><img src="icons/youfeed.svg" alt="Feed" class="sidebar-icon" />Your Feed</button>
                <button class="sidebar-btn" onclick="window.location.href='groups.php'"><img src="icons/yourgroup.svg" alt="Groups" class="sidebar-icon" />Your Groups</button>
                <button class="sidebar-btn" onclick="window.location.href='discover.php'"><img src="icons/discover.svg" alt="Discover" class="sidebar-icon" />Discover</button>
                <button class="sidebar-btn" onclick="window.location.href='createGroup.php'"><img src="icons/createpost.svg" alt="Create" class="sidebar-icon" />Create Group</button>
            </div>
        </aside>
        <main class="feed-scrollable">
            <div class="post-card" style="margin-bottom: 2.5rem; background: #181d36;">
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <img src="groupsImages/<?php echo htmlspecialchars($group['photo']); ?>" alt="<?php echo htmlspecialchars($group['name']); ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 16px; border: 2px solid #a259ff; background: #222;" onerror="this.src='groupsImages/default.jpg'" />
                    <div>
                        <h2 style="margin: 0; color: #fff; font-size: 2rem; font-weight: 700; letter-spacing: 1px;"> <?php echo htmlspecialchars($group['name']); ?> </h2>
                        <p style="color: #b3b3b3; margin: 0.5rem 0 0 0; font-size: 1.1rem;"> <?php echo htmlspecialchars($group['description']); ?> </p>
                    </div>
                </div>
            </div>
            <?php if (empty($posts)): ?>
                <div class="no-posts">
                    <h2>No posts yet!</h2>
                    <p>Be the first to post in this community.</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-card">
                        <div class="post-header">
                            <div class="post-user-info">
                                <span class="post-user"><?php echo htmlspecialchars($post['username']); ?></span>
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
                                $stmt2 = $conn->prepare("SELECT c.*, u.name as username FROM comments c JOIN users u ON c.commentor = u.id WHERE c.post_id = ? ORDER BY c.commented_at DESC");
                                $stmt2->bind_param("i", $post['id']);
                                $stmt2->execute();
                                $comments = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
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
        <aside class="rightbar-static">
            <div class="write-post-section">
                <div class="write-title">Write Post</div>
                <textarea class="write-box" id="post-content" placeholder="Write Something"></textarea>
                <input type="file" class="write-image-input" id="post-image" accept="image/*" />
                <div class="write-actions">
                    <!-- Community select is hidden because we're in a specific group -->
                    <button class="write-post-btn" onclick="createPost()">Post</button>
                </div>
            </div>
        </aside>
    </div>
    <script src="https://unpkg.com/feather-icons"></script>
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
                        'Content-Type': 'application/json'
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
                            'Content-Type': 'application/json'
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
            const content = document.getElementById('post-content').value.trim();
            const imageInput = document.getElementById('post-image');
            const groupId = <?php echo json_encode($group_id); ?>;

            if (!content && !imageInput.files.length) {
                alert('Please write something or select an image.');
                return;
            }

            const formData = new FormData();
            formData.append('caption', content);
            formData.append('community_id', groupId);
            if (imageInput.files.length) {
                formData.append('photo', imageInput.files[0]);
            }

            fetch('createPost.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Post created successfully!');
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