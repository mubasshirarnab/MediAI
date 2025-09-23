<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Community | MEDIAi</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="css\communityHome.css" />
  </head>
  <body>
    <iframe
      src="Navbar\navbar.html"
      frameborder="0"
      style="width: 100%; height: 80px"
    ></iframe>
    <div class="main-layout">
      <!-- Left Sidebar: User Groups -->
      <aside class="sidebar">
        <div class="sidebar-search">
          <img src="icons/searchbar.svg" alt="Search" class="sidebar-icon" />
          <input type="text" placeholder="Search Community" />
        </div>
        <div class="sidebar-menu">
          <button
            class="sidebar-btn"
            onclick="window.location.href='feed.php'"
          >
            <img src="icons/youfeed.svg" alt="Feed" class="sidebar-icon" />
            Your Feed
          </button>
          <button
            class="sidebar-btn"
            onclick="window.location.href='groups.php'"
          >
            <img src="icons/yourgroup.svg" alt="Groups" class="sidebar-icon" />
            Your Groups
          </button>
          <button
            class="sidebar-btn"
            onclick="window.location.href='discover.php'"
          >
            <img src="icons/discover.svg" alt="Discover" class="sidebar-icon" />
            Discover
          </button>
          <button class="sidebar-btn">
            <img src="icons/createpost.svg" alt="Create" class="sidebar-icon" />
            Create Group
          </button>
        </div>
      </aside>
      <div class="vertical-divider"></div>
      <main class="main-content-centered">
        <img src="icons/texticon.svg" alt="No Group" class="no-group-icon" />
        <div class="no-group-text">You are not connected with any group!</div>
        <div class="no-group-actions">
          <button class="discover-btn">Discover</button>
          <button class="create-group-btn">+ Create Group</button>
        </div>
      </main>
    </div>
  </body>
</html>
