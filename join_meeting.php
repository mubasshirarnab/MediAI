<?php
session_start();
require_once 'dbConnect.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Video Meeting</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap');

        body {
            font-family: 'Roboto', Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #000117 0%, #00022a 100%);
            color: #e0e0e0;
        }
        .container {
            background-color: rgba(10, 25, 47, 0.85); /* Darker, slightly transparent background */
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.37);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px); /* For Safari */
            border: 1px solid rgba(255, 255, 255, 0.18);
            text-align: center;
            width: 90%;
            max-width: 400px;
        }
        h1 {
            color: #ffffff;
            margin-bottom: 25px;
            font-weight: 700;
            font-size: 2.2em;
        }
        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }
        label {
            display: block;
            margin-bottom: 10px;
            color: #b0b0b0; /* Lighter grey for label */
            font-weight: 400;
            font-size: 0.95em;
        }
        input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #334155; /* Darker border */
            border-radius: 6px;
            box-sizing: border-box;
            background-color: #1e293b; /* Dark input background */
            color: #e0e0e0; /* Light text color for input */
            font-size: 1em;
        }
        input[type="text"]::placeholder {
            color: #64748b; /* Muted placeholder color */
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        button {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 14px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 700;
            transition: all 0.3s ease;
            width: 100%;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.2);
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.3);
        }
        button:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(0, 123, 255, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Join Video Meeting</h1>
        <div class="form-group">
            <label for="roomID">Enter Meeting Code:</label>
            <input type="text" id="roomID" name="roomID" placeholder="e.g., 12345" required>
        </div>
        <button onclick="joinMeeting()">Join Meeting</button>
    </div>

    <script>
        function joinMeeting() {
            const roomID = document.getElementById('roomID').value;
            if (roomID) {
                // Redirect to the correct video counselling page with the roomID
                window.location.href = `video_counselling.php?roomID=${roomID}`;
            } else {
                alert("Please enter a meeting code.");
            }
        }
    </script>
</body>
</html>
