<?php
// index.php

session_start();

// 1) Require login
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// 2) Connect to the database
require_once 'dbConnect.php';
if ($conn->connect_error) {
  die("Database connection failed: " . $conn->connect_error);
}

// Get all authorized doctors
$query = "SELECT u.id, u.name, d.specialization, d.photo
          FROM users u
          JOIN doctors d ON u.id = d.user_id
          WHERE u.role_id = (SELECT id FROM roles WHERE role_name = 'doctor')
          AND u.status = 'authorized'";
$result = $conn->query($query);
$doctors = [];

if ($result) {
  while ($row = $result->fetch_assoc()) {
    // Get expertise for each doctor
    $exp_query = "SELECT expertise_name FROM expertise WHERE user_id = ?";
    $stmt = $conn->prepare($exp_query);
    if ($stmt) {
      $stmt->bind_param("i", $row['id']);
      $stmt->execute();
      $exp_result = $stmt->get_result();

      $expertise = [];
      while ($exp_row = $exp_result->fetch_assoc()) {
        $expertise[] = $exp_row['expertise_name'];
      }

      // Add expertise to doctor data
      $row['expertise'] = $expertise;
      $doctors[] = $row;

      $stmt->close();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MediAi - Your 24/7 Medical Partner</title>
  <link rel="stylesheet" href="css/globals.css" />
  <link rel="stylesheet" href="css\style.css" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    integrity="sha512-..."
    crossorigin="anonymous"
    referrerpolicy="no-referrer" />
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
  <iframe
    src="<?php echo (isset($_SESSION['role']) && $_SESSION['role'] == 'hospital') ? 'hospitalnav.php' : 'navbar.php'; ?>"
    frameborder="0"
    style="width: 100%; height: 80px"></iframe>
  <?php
  require_once (isset($_SESSION['role']) && $_SESSION['role'] == 'hospital') ? 'hospitalnav.php'  : 'navbar.php';
  ?>
  <div class="main-layout">

    <h1 class=" mainTitle">YOUR 24/7 MEDICAL</h1>
    <h2 class="mainDiscription">PARTNER <img src=" img/mingcute-ai-fill.svg" alt="">
    </h2>
    <p>A cutting-edge website that scans your medical reports using AI to
      predict potential diseases and provide health insights. If concerns
      arise, it connects you with certified doctors for real-time consultation
      and booking, making <br>healthcare smarter and more accessible.</p>
  </div>



  <div class="ask-ai">
    <span class="ask-ai-text">Start your journey here
      <?php
      $ask_ai_link = 'ai.php'; // Default link
      if (isset($_SESSION['role']) && $_SESSION['role'] == 'hospital') {
        $ask_ai_link = 'hospital_ai.php';
      }
      ?>
      <a href="<?php echo $ask_ai_link; ?>" class="ask-ai-btn" aria-label="Ask Ai" style=" padding-left: 10px;">Ask Ai</a>
    </span>
  </div>

  <section class="services">
    <h2 class="services-title">OUR SERVICES</h2>
    <p class="services-desc">
      Explore Our Smart Healthcare Services – Here's How We Can Help You
    </p>

    <div class="scroll">
      <div class="marquee-track">
        <!-- ORIGINAL SET -->
        <article class="service-card">
          <h3 class="service-title">CNN-Based Disease detection</h3>
          <p class="service-desc">
            Analyzes images, predicts risks, and offers instant health
            guidance via chatbot.
          </p>
          <img
            class="service-image"
            src="img/Image_Based_Disease_Detection.png"
            alt="AI Diagnosis Hub" />
        </article>
        <article class="service-card">
          <h3 class="service-title">Text-Based Disease Risk Prediction</h3>
          <p class="service-desc">
            Real-time health tracking and alerts delivered directly to your
            device.
          </p>
          <img
            class="service-image"
            src="img/Predict Future Disease Risk.png"
            alt="Remote Monitoring" />
        </article>
        <article class="service-card">
          <h3 class="service-title">AI Chatbot for Initial Consultation</h3>
          <p class="service-desc">
            Connect with doctors through secure video
          </p>
          <img
            class="service-image"
            src="img/AI Chatbot.png"
            alt="Virtual Consultation" />
        </article>
        <article class="service-card">
          <h3 class="service-title">Community System for Discussions</h3>
          <p class="service-desc">
            Personalized tips and programs for mental and physical well-being.
          </p>
          <img
            class="service-image"
            src="img/Health Communities.png"
            alt="Wellness Insights" />
        </article>

        <!-- DUPLICATE SET -->
        <article class="service-card">
          <h3 class="service-title">Online Appointment Booking</h3>
          <p class="service-desc">
            Analyzes images, predicts risks, and offers instant health
            guidance via chatbot.
          </p>
          <img
            class="service-image"
            src="img/Book Appointments.png"
            alt="AI Diagnosis Hub" />
        </article>
        <article class="service-card">
          <h3 class="service-title">Online Video Counseling</h3>
          <p class="service-desc">
            Real-time health tracking and alerts delivered directly to your
            device.
          </p>
          <img
            class="service-image"
            src="img/Video Consultations.png"
            alt="Remote Monitoring" />
        </article>
        <article class="service-card">
          <h3 class="service-title">Automatice Medication Reminder</h3>
          <p class="service-desc">
            Connect with doctors through secure video calls anytime, anywhere.
          </p>
          <img
            class="service-image"
            src="img/Medication Reminders.png"
            alt="Virtual Consultation" />
        </article>
        <article class="service-card">
          <h3 class="service-title">Inventory Management For Hospital & Pharmacies</h3>
          <p class="service-desc">
            Personalized tips and programs for mental and physical well-being.
          </p>
          <img
            class="service-image"
            src="img/asset1.png"
            alt="Wellness Insights" />
        </article>
      </div>
    </div>
  </section>

  <section class="doctors">
    <h2 class="doctors-title">
      <span class="number"><?php echo count($doctors); ?></span> EXPERT DOCTORS
    </h2>
    <p class="doctors-desc">are currently available in MediAi</p>

    <div class="doctors-slider">
      <div class="doctors-list">
        <?php foreach ($doctors as $doc): ?>
          <div class="dr-card">
            <img
              class="dr-image"
              src="img/<?php echo htmlspecialchars($doc['photo']); ?>"
              alt="<?php echo htmlspecialchars($doc['name']); ?>" />
            <div class="dr-info">
              <h3><?php echo htmlspecialchars($doc['name']); ?></h3>
              <p class="dr-role"><?php echo htmlspecialchars($doc['specialization']); ?></p>
              <p class="dr-qual">
                <?php
                // Show up to two expertise areas
                $slice = array_slice($doc['expertise'], 0, 2);
                echo htmlspecialchars(implode(' | ', $slice));
                ?>
              </p>
              <p class="dr-special">
                Specialist in <?php echo htmlspecialchars($doc['specialization']); ?>
              </p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="slider-arrows" style="margin-top: 70px;">
      <button class="prev" style="background:none;border:none;">
        <img src="icons/l.png" alt="" class="arrow-icon">
      </button>
      <button class="next" style="background:none;border:none;">
        <img src="icons/r.png" alt="" class="arrow-icon">
      </button>
    </div>
  </section>


  <section class="count">
  </section>
  <footer class="custom-footer" style="
  min-height: 260px;
  background: #03032a;
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  justify-content: center;
  gap: 150px;  
  padding: 100px 100px;
  
    box-sizing: border-box;">

    <div class="footer-col footer-logo">
      <span class="footer-logo-text" style="color: white;
  font-size: 68px;
  font-family: Montserrat;
  font-weight: 800;
  line-height: 84.08px;
  word-wrap: break-word;">MEDIAi</span>
    </div>
    <div class="footer-col footer-about">
      <h3 class="footer-about-title"
        style=" color: white;
              font-size: 24px;
              font-family: Montserrat;
              font-weight: 600;
              line-height: 34px;
              word-wrap: break-word;">About Us</h3>

      <p class="footer-about-desc"
        style="color:rgb(167, 163, 163);
              font-size: 16px;
              font-family: Montserrat;
              font-weight: 400;
              line-height: 24px;
              word-wrap: break-word;">
        A cutting-edge website that scans your medical reports using AI to predict potential diseases and provide health insights. If concerns arise, it connects you with certified doctors for real-time consultation and booking, making healthcare smarter and more accessible.
      </p>
    </div>
    <div class="footer-col footer-social-contact">


      <div class="footer-social" ">
        <span class=" footer-social-title" style="line-height: 34px;">Follow us on</span>
        <div class="footer-social-icons" style="line-height: 14px;">
          <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
          <a href="#" aria-label="X"><i class="fab fa-x-twitter"></i></a>
          <a href="#" aria-label="Threads"><i class="fa-brands fa-threads"></i></a>
        </div>
      </div>


      <div class="footer-contact" style="line-height: 24px;">
        <span class="footer-contact-title">Contact us</span>
        <span class="footer-contact-email">mediai@officials.com</span>
      </div>


    </div>
  </footer>

  </div>


  <script>
    const list = document.querySelector(" .doctors-list");
    const prev = document.querySelector(".prev");
    const next = document.querySelector(".next");
    let scrollX = 0;
    const cardWidth = 410 + 48; // card width + gap

    prev.addEventListener("click", () => {
      scrollX = Math.min(scrollX + cardWidth, 0);
      list.style.transform = `translateX(${scrollX}px)`;
    });

    next.addEventListener("click", () => {
      const maxScroll = -(
        list.children.length * cardWidth -
        window.innerWidth +
        20
      );
      scrollX = Math.max(scrollX - cardWidth, maxScroll);
      list.style.transform = `translateX(${scrollX}px)`;
    });
  </script>

</body>

</html>