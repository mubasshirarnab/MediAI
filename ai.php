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

// --- BEGIN: Enhanced Logic for saving and fetching chat conversations ---
$current_user_id = $_SESSION['user_id']; // Already set from initial check

// Function to generate a title from the first prompt
function generate_chat_title($prompt)
{
  $words = explode(' ', $prompt);
  $title = implode(' ', array_slice($words, 0, 7));
  if (count($words) > 7) {
    $title .= '...';
  }
  return $title ?: "New Chat"; // Fallback title
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  header('Content-Type: application/json'); // Ensure JSON response for all AJAX
  $action = $_POST['action'];

  switch ($action) {
    case 'start_new_chat':
      $default_title = "New Chat"; // Will be updated upon first message
      $stmt = $conn->prepare("INSERT INTO ai_conversations (user_id, title) VALUES (?, ?)");
      if ($stmt) {
        $stmt->bind_param("is", $current_user_id, $default_title);
        if ($stmt->execute()) {
          $new_conversation_id = $stmt->insert_id;
          $_SESSION['active_conversation_id'] = $new_conversation_id;
          echo json_encode(['status' => 'success', 'conversation_id' => $new_conversation_id, 'title' => $default_title]);
        } else {
          echo json_encode(['status' => 'error', 'message' => 'Failed to start new chat: ' . $stmt->error]);
        }
        $stmt->close();
      } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement for new chat: ' . $conn->error]);
      }
      break;

    case 'clear_active_conversation':
      unset($_SESSION['active_conversation_id']);
      echo json_encode(['status' => 'success', 'message' => 'Active conversation cleared from session.']);
      break;

    case 'load_conversation':
      if (isset($_POST['conversation_id'])) {
        $conversation_id_to_load = intval($_POST['conversation_id']);
        // Verify user owns this conversation
        $verify_stmt = $conn->prepare("SELECT id FROM ai_conversations WHERE id = ? AND user_id = ?");
        if ($verify_stmt) {
          $verify_stmt->bind_param("ii", $conversation_id_to_load, $current_user_id);
          $verify_stmt->execute();
          $verify_result = $verify_stmt->get_result();
          if ($verify_result->num_rows > 0) {
            $_SESSION['active_conversation_id'] = $conversation_id_to_load;
            $msg_stmt = $conn->prepare("SELECT message, response, created_at FROM chatbot_queries WHERE conversation_id = ? ORDER BY created_at ASC");
            if ($msg_stmt) {
              $msg_stmt->bind_param("i", $conversation_id_to_load);
              $msg_stmt->execute();
              $messages_result = $msg_stmt->get_result();
              $messages = [];
              while ($row = $messages_result->fetch_assoc()) {
                $messages[] = ['type' => 'user', 'content' => $row['message'], 'timestamp' => $row['created_at']];
                if ($row['response']) { // Ensure response exists
                  $messages[] = ['type' => 'ai', 'content' => $row['response'], 'timestamp' => $row['created_at']]; // AI response shares user message timestamp for simplicity here
                }
              }
              echo json_encode(['status' => 'success', 'messages' => $messages]);
              $msg_stmt->close();
            } else {
              echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement for loading messages.']);
            }
          } else {
            echo json_encode(['status' => 'error', 'message' => 'Conversation not found or access denied.']);
          }
          $verify_stmt->close();
        } else {
          echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement for verifying conversation.']);
        }
      } else {
        echo json_encode(['status' => 'error', 'message' => 'Conversation ID missing.']);
      }
      break;

    case 'save_chat':
      if (isset($_POST['prompt']) && isset($_POST['response'])) {
        $prompt_text = $_POST['prompt'];
        $response_text = $_POST['response'];
        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : (isset($_SESSION['active_conversation_id']) ? $_SESSION['active_conversation_id'] : null);
        $new_title_generated = null;

        if (!$conversation_id) { // No active or provided conversation ID, so create a new one
          $new_chat_title = generate_chat_title($prompt_text);
          $new_title_generated = $new_chat_title;
          $stmt_conv = $conn->prepare("INSERT INTO ai_conversations (user_id, title) VALUES (?, ?)");
          if ($stmt_conv) {
            $stmt_conv->bind_param("is", $current_user_id, $new_chat_title);
            if ($stmt_conv->execute()) {
              $conversation_id = $stmt_conv->insert_id;
              $_SESSION['active_conversation_id'] = $conversation_id;
            } else {
              echo json_encode(['status' => 'error', 'message' => 'Failed to create new conversation: ' . $stmt_conv->error]);
              $stmt_conv->close();
              exit();
            }
            $stmt_conv->close();
          } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement for new conversation: ' . $conn->error]);
            exit();
          }
        } else {
          // If it's the first message to an existing "New Chat" conversation, update its title
          $title_check_stmt = $conn->prepare("SELECT title FROM ai_conversations WHERE id = ? AND user_id = ?");
          if ($title_check_stmt) {
            $title_check_stmt->bind_param("ii", $conversation_id, $current_user_id);
            $title_check_stmt->execute();
            $title_res = $title_check_stmt->get_result();
            if ($current_title_row = $title_res->fetch_assoc()) {
              if ($current_title_row['title'] === 'New Chat' || $current_title_row['title'] === '') {
                // Check if this is the first message in this conversation
                $msg_count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM chatbot_queries WHERE conversation_id = ?");
                $msg_count_stmt->bind_param("i", $conversation_id);
                $msg_count_stmt->execute();
                $count_res = $msg_count_stmt->get_result()->fetch_assoc();
                $msg_count_stmt->close();

                if ($count_res['count'] == 0) { // It's the first message, so update title
                  $new_chat_title = generate_chat_title($prompt_text);
                  $new_title_generated = $new_chat_title;
                  $update_title_stmt = $conn->prepare("UPDATE ai_conversations SET title = ? WHERE id = ?");
                  if ($update_title_stmt) {
                    $update_title_stmt->bind_param("si", $new_chat_title, $conversation_id);
                    $update_title_stmt->execute();
                    $update_title_stmt->close();
                  }
                }
              }
            }
            $title_check_stmt->close();
          }
        }

        // Save the message
        $stmt_msg = $conn->prepare("INSERT INTO chatbot_queries (user_id, conversation_id, message, response) VALUES (?, ?, ?, ?)");
        if ($stmt_msg) {
          $stmt_msg->bind_param("iiss", $current_user_id, $conversation_id, $prompt_text, $response_text);
          if ($stmt_msg->execute()) {
            // Update conversation's updated_at timestamp
            $update_conv_stmt = $conn->prepare("UPDATE ai_conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            if ($update_conv_stmt) {
              $update_conv_stmt->bind_param("i", $conversation_id);
              $update_conv_stmt->execute();
              $update_conv_stmt->close();
            }
            echo json_encode(['status' => 'success', 'message' => 'Chat saved.', 'conversation_id' => $conversation_id, 'new_title' => $new_title_generated]);
          } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save chat message: ' . $stmt_msg->error]);
          }
          $stmt_msg->close();
        } else {
          echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement for saving message: ' . $conn->error]);
        }
      } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing prompt or response for saving chat.']);
      }
      break;

    default:
      echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
      break;
  }
  exit(); // Terminate script after AJAX handling
}

// Fetch conversations for the sidebar
$conversations_by_time = [
  'Today' => [],
  'Yesterday' => [],
  'Last 7 Days' => [],
  'Last 1 Month' => [],
  'Older' => []
];

$sql_conv_list = "SELECT id, title, updated_at FROM ai_conversations WHERE user_id = ? ORDER BY updated_at DESC";
$stmt_conv_list = $conn->prepare($sql_conv_list);
if ($stmt_conv_list) {
  $stmt_conv_list->bind_param("i", $current_user_id);
  $stmt_conv_list->execute();
  $result_conv_list = $stmt_conv_list->get_result();

  $now = new DateTime();
  $today_dt = $now->format('Y-m-d');
  $yesterday_dt = (new DateTime())->modify('-1 day')->format('Y-m-d');
  $seven_days_ago_dt = (new DateTime())->modify('-7 days')->format('Y-m-d');
  $one_month_ago_dt = (new DateTime())->modify('-1 month')->format('Y-m-d');

  while ($conv_row = $result_conv_list->fetch_assoc()) {
    $item_date_obj = new DateTime($conv_row['updated_at']);
    $item_date_formatted = $item_date_obj->format('Y-m-d');
    $conv_data = ['id' => $conv_row['id'], 'title' => $conv_row['title']];

    if ($item_date_formatted === $today_dt) {
      $conversations_by_time['Today'][] = $conv_data;
    } elseif ($item_date_formatted === $yesterday_dt) {
      $conversations_by_time['Yesterday'][] = $conv_data;
    } elseif ($item_date_obj->format('Y-m-d H:i:s') > $seven_days_ago_dt) {
      $conversations_by_time['Last 7 Days'][] = $conv_data;
    } elseif ($item_date_obj->format('Y-m-d H:i:s') > $one_month_ago_dt) {
      $conversations_by_time['Last 1 Month'][] = $conv_data;
    } else {
      $conversations_by_time['Older'][] = $conv_data;
    }
  }
  $stmt_conv_list->close();
}
// --- END: Enhanced Logic for saving and fetching chat conversations ---
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ask AI</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="css\ai.css" />
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
</head>

<body>
  <iframe
    src="Navbar/navbar.html"
    frameborder="0"
    style="width: 100%; height: 80px"></iframe>

  <?php require_once 'navbar.php'; ?>

  <div class="container">
    <aside class="sidebar">
      <div class="sidebar-scroll">
        <?php
        $displayed_main_categories = 0;
        $category_order = ['Today', 'Yesterday', 'Last 7 Days', 'Last 1 Month', 'Older'];

        foreach ($category_order as $category_title) {
          if (!empty($conversations_by_time[$category_title])) {
            echo '<div class="time-section">';
            echo '<h2>' . htmlspecialchars($category_title) . '</h2>';
            foreach ($conversations_by_time[$category_title] as $conversation) {
              // Ensure title is not too long for display
              $display_conv_title = strlen($conversation['title']) > 40 ? substr($conversation['title'], 0, 37) . '...' : $conversation['title'];
              echo '<div class="search-item" data-conversation-id="' . htmlspecialchars($conversation['id']) . '">' . htmlspecialchars($display_conv_title) . '</div>';
            }
            echo '</div>';
            $displayed_main_categories++;
          }
        }
        if ($displayed_main_categories === 0) {
          // echo '<div class="time-section"><p>No conversations yet. Start a new one!</p></div>'; // Optional
        }
        ?>
      </div>
    </aside>

    <main class="main-content">
      <div class="chat-section">
        <!-- Initial view -->
        <div class="initial-view">
          <h1>HOW CAN I HELP YOU?</h1>
        </div>

        <!-- Chat conversation area -->
        <div class="chat-container" style="display: none">
          <div class="chat-messages" id="chat-messages">
            <!-- Messages will be added here dynamically -->
          </div>
          <div
            class="loading-message"
            id="loadingMessage"
            style="display: none">
            <div class="thinking-dots">
              <span>Thinking</span>
              <span class="dot">.</span>
              <span class="dot">.</span>
              <span class="dot">.</span>
            </div>
          </div>
        </div>

        <!-- Input area - will be fixed at bottom after first message -->
        <div class="input-area">
          <div class="search-container">
            <input
              type="text"
              class="search-input"
              id="userInput"
              placeholder="Ask MediAI" />
            <button class="search-button" onclick="sendMessage()">
              <span>Ask</span>
            </button>
          </div>
          <div class="quick-actions">
            <button class="add-button">+</button>
            <button class="action-button" onclick="goToRiskPrediction()">🔮 Predict</button>
              <script>
                function goToRiskPrediction() {
                  // Use web-safe forward slashes for URLs so it works across platforms
                  window.location.href = 'risk_predict_model/risk_prediction_react.html';
                }
              </script>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script>
    let currentConversationId = null; // To be managed by JS - Moved to global scope

    // Function to clear chat messages display - Moved to global scope
    function clearChatMessages() {
      const chatMessagesDiv = document.getElementById("chat-messages");
      const initialView = document.querySelector(".initial-view");
      if (chatMessagesDiv) chatMessagesDiv.innerHTML = '';
      if (initialView) {
        initialView.style.opacity = "1";
        initialView.style.transform = "translateY(0)";
        initialView.style.display = "block";
      }
      const chatContainer = document.querySelector(".chat-container");
      if (chatContainer) chatContainer.style.display = "none";
      const chatSection = document.querySelector(".chat-section");
      if (chatSection) chatSection.classList.remove("chat-active");
    }

    // Function to add a message to the chat display - Moved to global scope
    function addMessageToDisplay(content, type, rawMarkdown = false) {
      const chatMessagesDiv = document.getElementById("chat-messages");
      if (!chatMessagesDiv) {
        console.error("chatMessagesDiv not found in addMessageToDisplay");
        return;
      }
      const messageDiv = document.createElement("div");
      messageDiv.className = `message ${type}`; // type is 'user' or 'ai'
      const messageContentDiv = document.createElement("div");
      messageContentDiv.className = "message-content";
      if (type === 'ai' && rawMarkdown) {
        messageContentDiv.innerHTML = marked.parse(content);
      } else {
        messageContentDiv.textContent = content;
      }
      messageDiv.appendChild(messageContentDiv);
      chatMessagesDiv.appendChild(messageDiv);
      chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
    }

    // Moved to global scope
    async function startNewChatSession() {
      const searchInput = document.getElementById("userInput");
      const initialView = document.querySelector(".initial-view");
      const chatContainer = document.querySelector(".chat-container");
      const chatSection = document.querySelector(".chat-section");

      clearChatMessages();
      if (searchInput) searchInput.value = '';
      currentConversationId = null; // Reset client-side ID initially

      console.log("Starting new chat session. Requesting new conversation_id from server.");

      try {
        const formData = new FormData();
        formData.append('action', 'start_new_chat'); // Changed to start_new_chat
        const response = await fetch('ai.php', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();
        if (result.status === 'success' && result.conversation_id) {
          currentConversationId = result.conversation_id; // Set client-side ID to the new one
          console.log("Server created new chat. New conversation_id:", currentConversationId, "Title:", result.title);
          // Sidebar will update on next full load or if explicitly refreshed.
          // The title of this new chat will be updated on the first message sent.
        } else {
          console.error("Failed to start new chat session on server:", result.message || "Unknown error. Full result:", result);
          currentConversationId = null; // Ensure it's null on failure to prevent using stale ID
        }
      } catch (error) {
        console.error("Error calling start_new_chat:", error);
        currentConversationId = null; // Ensure it's null on error
      }

      if (initialView) {
        initialView.style.opacity = "1";
        initialView.style.transform = "translateY(0)";
        initialView.style.display = "block";
      }
      if (chatContainer) chatContainer.style.display = "none";
      if (chatSection) chatSection.classList.remove("chat-active");
      if (searchInput) searchInput.focus();
    }

    // Moved to global scope
    async function loadConversation(conversationId) {
      const searchInput = document.getElementById("userInput");
      const loadingMessage = document.getElementById("loadingMessage");
      const initialView = document.querySelector(".initial-view");
      const chatContainer = document.querySelector(".chat-container");
      const chatSection = document.querySelector(".chat-section");

      if (!conversationId) return;
      console.log(`Loading conversation ${conversationId}`);
      currentConversationId = conversationId;
      clearChatMessages();

      if (loadingMessage) loadingMessage.style.display = "flex";

      try {
        const formData = new FormData();
        formData.append('action', 'load_conversation');
        formData.append('conversation_id', conversationId);

        const response = await fetch('ai.php', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();

        if (loadingMessage) loadingMessage.style.display = "none";

        if (result.status === 'success' && result.messages) {
          if (chatSection) chatSection.classList.add("chat-active");
          if (initialView) {
            initialView.style.opacity = "0";
            initialView.style.transform = "translateY(-20px)";
          }
          setTimeout(() => {
            if (initialView) initialView.style.display = "none";
            if (chatContainer) chatContainer.style.display = "block";
          }, 300);

          result.messages.forEach(msg => {
            addMessageToDisplay(msg.content, msg.type, msg.type === 'ai');
          });
        } else {
          console.error("Failed to load conversation:", result.message);
          addMessageToDisplay(`Error: ${result.message || 'Could not load conversation.'}`, 'ai');
        }
      } catch (error) {
        if (loadingMessage) loadingMessage.style.display = "none";
        console.error("Error loading conversation:", error);
        addMessageToDisplay(`Error: ${error.message}`, 'ai');
      }
      if (searchInput) searchInput.focus();
    }

    // Moved to global scope
    async function sendMessage() {
      console.log("sendMessage called");
      const inputElement = document.getElementById("userInput");
      const messagesDiv = document.getElementById("chat-messages");
      const loadingMessage = document.getElementById("loadingMessage");
      const chatSection = document.querySelector(".chat-section");
      const initialView = document.querySelector(".initial-view");
      const chatContainer = document.querySelector(".chat-container");

      if (!inputElement || !messagesDiv || !loadingMessage || !chatSection || !initialView || !chatContainer) {
        console.error("One or more essential DOM elements not found in sendMessage.");
        return;
      }

      let userOriginalMessage = inputElement.value.trim();
      console.log("User original message:", userOriginalMessage);
      if (!userOriginalMessage) {
        console.log("Empty message, returning.");
        return;
      }

      let messageForAI = userOriginalMessage + ". This is the prompt. If the prompt is related to 'Medical Assistance', 'Mental Consultancy', or 'Greetings', then just respond to the prompt. Otherwise, show this message: 'Hi there! 👋 I'm here to help with Mental health support, and Physical health-related questions only. If you have other queries, please consult a medical expert or explore other features of MediAI. Thanks for understanding! 💙'";

      console.log("Message for AI:", messageForAI);

      // Hide initial view and show chat container
      chatSection.classList.add("chat-active");
      initialView.style.opacity = "0";
      initialView.style.transform = "translateY(-20px)";
      setTimeout(() => {
        initialView.style.display = "none";
        chatContainer.style.display = "block";
      }, 300);

      // Add user message to display
      addMessageToDisplay(userOriginalMessage, 'user');
      console.log("User message added to display.");
      inputElement.value = "";

      // Show loading message
      loadingMessage.style.display = "flex";
      console.log("Loading message shown.");

      try {
        console.log("Attempting to fetch from OpenRouter API...");
        const response = await fetch(
          "https://openrouter.ai/api/v1/chat/completions", {
            method: "POST",
            headers: {
              Authorization: "Bearer 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",
              "Content-Type": "application/json",
              "X-Title": "MediAI_Chat",
            },
            body: JSON.stringify({
              model: "deepseek/deepseek-r1:free",
              messages: [{
                role: "user",
                content: messageForAI
              }],
            }),
          }
        );
        console.log("OpenRouter API response status:", response.status);

        if (!response.ok) {
          const errorText = await response.text();
          console.error("OpenRouter API Error Text:", errorText);
          throw new Error(`API request failed with status ${response.status}: ${errorText}`);
        }

        const data = await response.json();
        console.log("OpenRouter API response data:", data);
        const aiRawResponse =
          data.choices?.[0]?.message?.content || "No response received.";
        console.log("AI raw response:", aiRawResponse);

        loadingMessage.style.display = "none";
        console.log("Loading message hidden.");

        // Add AI message to display using the helper function
        addMessageToDisplay(aiRawResponse, 'ai', true);
        console.log("AI message added to display.");

        // Save chat to DB
        try {
          console.log("Attempting to save chat to DB. Conversation ID:", currentConversationId);
          const saveFormData = new FormData();
          saveFormData.append('action', 'save_chat');
          saveFormData.append('prompt', userOriginalMessage);
          saveFormData.append('response', aiRawResponse);
          if (currentConversationId) {
            saveFormData.append('conversation_id', currentConversationId);
          }

          const saveResponse = await fetch('ai.php', {
            method: 'POST',
            body: saveFormData,
          });
          console.log("Save chat response status:", saveResponse.status);
          const saveResult = await saveResponse.json();
          console.log("Save chat result:", saveResult);

          if (saveResult.status === 'success') {
            if (saveResult.conversation_id) {
              currentConversationId = saveResult.conversation_id;
              console.log("Updated/Set currentConversationId to:", currentConversationId);
            }
            if (saveResult.new_title) {
              console.log("Chat saved. New title generated:", saveResult.new_title);
            } else {
              console.log("Chat saved to existing conversation.");
            }
          } else {
            console.error("Failed to save chat:", saveResult.message);
          }
        } catch (saveError) {
          console.error("Error saving chat (exception):", saveError);
        }

      } catch (error) {
        console.error("Error in sendMessage (AI fetch or processing):", error);
        if (loadingMessage) loadingMessage.style.display = "none";
        addMessageToDisplay(`AI Error: ${error.message}`, 'ai');
      }

      if (messagesDiv) {
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
      } else {
        console.error("messagesDiv is null, cannot scroll.");
      }
    }

    document.addEventListener("DOMContentLoaded", function() {
      const searchInput = document.getElementById("userInput");
      const newChatButton = document.querySelector(".add-button");

      if (searchInput) {
        searchInput.focus();
        searchInput.addEventListener("keypress", function(e) {
          if (e.key === "Enter") {
            sendMessage();
          }
        });
      }

      if (newChatButton) {
        newChatButton.addEventListener("click", startNewChatSession);
      }

      document.querySelectorAll(".sidebar .search-item").forEach(item => {
        item.addEventListener("click", function() {
          const convId = this.dataset.conversationId;
          if (convId) {
            loadConversation(convId);
          }
        });
      });
    });
  </script>
</body>

</html>