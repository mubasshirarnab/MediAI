<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Go To Community</title>
    <style>
        #com {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            width: 100vw;
        }
        .community-btn {
            padding: 15px 30px;
            font-size: 18px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .community-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div id="com">
        <button class="community-btn" onclick="window.location.href='feed.php'">
            Go To Community
        </button>
        <button class="community-btn" onclick="window.location.href='ai.php'">
            Go To AI
        </button>
    </div>
</body>
</html>