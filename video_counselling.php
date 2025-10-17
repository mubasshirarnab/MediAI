<?php
session_start();
require_once 'dbConnect.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];
?>


<html>

<head>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #000117; /* User requested background color */
            color: #fff; /* Default text color for better contrast */
        }
        #root {
            width: 100vw;
            height: 100vh;
        }
        .send-code-container {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: rgba(0, 0, 0, 0.7);
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.5);
            z-index: 1000; /* Ensure it's above the ZegoCloud UI */
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .send-code-container input[type="text"] {
            padding: 8px;
            border: 1px solid #555;
            border-radius: 4px;
            background-color: #222;
            color: #fff;
            font-size: 14px;
        }
        .send-code-container input[type="text"]::placeholder {
            color: #888;
        }
        .send-code-container button {
            padding: 8px 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }
        .send-code-container button:hover {
            background-color: #0056b3;
        }
        /* Minimal styling for Zego root if needed, though it mostly controls its own UI */
    </style>
</head>


<body>
    <div id="root"></div>
    <div class="send-code-container">
        <input type="text" id="manualRoomID" placeholder="Enter Room ID to send">
        <button onclick="sendCode()">Send Code</button>
    </div>
</body>
<script src="https://unpkg.com/@zegocloud/zego-uikit-prebuilt/zego-uikit-prebuilt.js"></script>
<script>
window.onload = function () {
    function getUrlParams(url) {
        let urlStr = url.split('?')[1];
        const urlSearchParams = new URLSearchParams(urlStr);
        const result = Object.fromEntries(urlSearchParams.entries());
        return result;
    }


        // Generate a Token by calling a method.
        // @param 1: appID
        // @param 2: serverSecret
        // @param 3: Room ID
        // @param 4: User ID
        // @param 5: Username
    const roomID = getUrlParams(window.location.href)['roomID'] || (Math.floor(Math.random() * 10000) + "");
    const userID = Math.floor(Math.random() * 10000) + "";
    const userName = "userName" + userID;
    const appID = 88719353;
    const serverSecret = "9ee3c7c8c54c273e30f58094dd1a7ca9";
    const kitToken = ZegoUIKitPrebuilt.generateKitTokenForTest(appID, serverSecret, roomID, userID, userName);

    
        const zp = ZegoUIKitPrebuilt.create(kitToken);
        zp.joinRoom({
            container: document.querySelector("#root"),
            sharedLinks: [{
                name: 'Personal link',
                url: window.location.protocol + '//' + window.location.host  + window.location.pathname + '?roomID=' + roomID,
            }],
            scenario: {
                mode: ZegoUIKitPrebuilt.VideoConference,
            },
                
           	turnOnMicrophoneWhenJoining: true,
           	turnOnCameraWhenJoining: true,
           	showMyCameraToggleButton: true,
           	showMyMicrophoneToggleButton: true,
           	showAudioVideoSettingsButton: true,
           	showScreenSharingButton: true,
           	showTextChat: false,
           	showUserList: true,
           	maxUsers: 2,
           	layout: "Auto",
           	showLayoutButton: false,

            onJoinRoom: () => {
                // Hide the send code container when successfully joined
                const sendCodeContainer = document.querySelector('.send-code-container');
                if (sendCodeContainer) {
                    sendCodeContainer.style.display = 'none';
                }
            },
            onLeaveRoom: () => {
                // Optionally, show the container again if the user leaves the room
                const sendCodeContainer = document.querySelector('.send-code-container');
                if (sendCodeContainer) {
                    sendCodeContainer.style.display = 'flex'; // Or 'block', depending on original display
                }
            }
         
            });

    // Display the current roomID in the input field if it's available
    // This helps the creator to easily see and send the current roomID
    if (roomID) {
        const personalLinkElement = document.querySelector('.ZegoUIKitSharedLink-personal-link .ZegoUIKitSharedLink-link');
        if (personalLinkElement && personalLinkElement.value) {
             const urlParamsForDisplay = new URLSearchParams(new URL(personalLinkElement.value).search);
             const currentRoomIDForDisplay = urlParamsForDisplay.get('roomID');
             if (currentRoomIDForDisplay) {
                document.getElementById('manualRoomID').value = currentRoomIDForDisplay;
             }
        } else {
            // Fallback if the element isn't found quickly, use the generated roomID
            // Zego UI might take a moment to render, so this is a fallback
             document.getElementById('manualRoomID').value = roomID;
        }
    }
}

function sendCode() {
    const params = (function(url){
        let urlStr = url.split('?')[1];
        const urlSearchParams = new URLSearchParams(urlStr);
        return Object.fromEntries(urlSearchParams.entries());
    })(window.location.href);

    const patientId = params['patient_id'];
    const meetingCode = document.getElementById('manualRoomID') ? document.getElementById('manualRoomID').value.trim() : '';

    if (!patientId) {
        alert('Patient ID missing in URL. Cannot send code.');
        return;
    }
    if (!meetingCode) {
        alert('Please provide a meeting code to send.');
        return;
    }

    const formData = new FormData();
    formData.append('patient_id', patientId);
    formData.append('meeting_code', meetingCode);

    fetch('save_meeting_code.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data && data.success) {
            alert('Meeting code sent successfully!');
        } else {
            alert('Failed to send meeting code: ' + (data && data.message ? data.message : 'Unknown error'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network error while sending meeting code.');
    });
}

</script>

</html>
