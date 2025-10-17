<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/dbConnect.php';

header('X-Feature: Medicine-Suggestion');

// AJAX: GET /medicine_suggestion.php?q=disease
if (isset($_GET['q'])) {
  $term = trim($_GET['q']);
  $like = '%' . $term . '%';

  $sql = "SELECT id, disease_name, medicine_name, description, effectiveness_score, company_name, verified_by, created_at
          FROM medicine_suggestions
          WHERE disease_name LIKE ?
          ORDER BY effectiveness_score DESC, medicine_name ASC
          LIMIT 50";
  $stmt = $conn->prepare($sql);
  if ($stmt === false) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Failed to prepare statement']);
    exit;
  }
  $stmt->bind_param('s', $like);
  $stmt->execute();
  $result = $stmt->get_result();
  $rows = [];
  while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
  }
  header('Content-Type: application/json');
  echo json_encode(['ok' => true, 'count' => count($rows), 'data' => $rows]);
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Medicine Suggestion</title>
    <link rel="stylesheet" href="css/globals.css" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/medicine_suggestion.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  </head>
  <body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="ms-container">
      <header class="ms-header">
        <h1 class="ms-title">Medicine Suggestion</h1>
        <p class="ms-subtitle">Evidence-oriented suggestions ranked by effectiveness</p>
      </header>

      <section class="ms-search">
        <input id="ms-input" class="ms-input" type="text" placeholder="Search disease (e.g., Fever, Headache)" aria-label="Search disease name" />
        <button id="ms-btn" class="ms-button" type="button"><i class="fa-solid fa-magnifying-glass" style="margin-right:8px"></i> Search</button>
      </section>

      <div id="ms-empty" class="ms-empty" style="display:none">Type a disease name and press Search</div>
      <section id="ms-grid" class="ms-grid"></section>
      <div id="ms-footer-note" class="ms-footer-note" style="display:none">Results are sorted by effectiveness score.</div>
    </main>

    <script>
      (function(){
        const input = document.getElementById('ms-input');
        const button = document.getElementById('ms-btn');
        const grid = document.getElementById('ms-grid');
        const empty = document.getElementById('ms-empty');
        const note = document.getElementById('ms-footer-note');

        function toStars(score){
          const five = Math.round((Number(score) || 0) / 20);
          const full = '★'.repeat(Math.min(5, five));
          const emptyStars = '☆'.repeat(5 - Math.min(5, five));
          return full + emptyStars;
        }

        function card(item){
          const score = Number(item.effectiveness_score || 0);
          const width = Math.max(0, Math.min(100, score));
          const verified = (item.verified_by && String(item.verified_by).trim().length > 0);
          const company = (item.company_name && String(item.company_name).trim().length > 0) ? item.company_name : '';
          return `
            <article class="ms-card" role="article">
              <div class="ms-ribbon">${escapeHtml(item.disease_name)}</div>
              <div class="ms-card-header">
                <div class="ms-icon"><i class="fa-solid fa-capsules"></i></div>
                <div class="ms-card-title">
                  <div class="ms-med-name">${escapeHtml(item.medicine_name)}</div>
                  <div class="ms-badges">
                    ${verified ? `<span class=\"ms-badge\"><i class=\"fa-solid fa-shield-check\" style=\"margin-right:6px;color:#7f5fff\"></i>Verified</span>` : ''}
                    ${company ? `<span class=\"ms-badge\">${escapeHtml(company)}</span>` : ''}
                  </div>
                </div>
              </div>
              <div class="ms-desc">${escapeHtml(item.description || 'No description')}</div>
              <div class="ms-score-row" aria-label="Effectiveness score">
                <span class="ms-score-label">${score.toFixed(0)}%</span>
                <div class="ms-progress" aria-hidden="true"><div class="ms-progress-bar" style="width:${width}%"></div></div>
                <span class="ms-stars" aria-hidden="true">${toStars(score)}</span>
              </div>
            </article>
          `;
        }

        function render(list){
          grid.innerHTML = '';
          if (!list || list.length === 0){
            empty.style.display = 'block';
            empty.textContent = 'No results found';
            note.style.display = 'none';
            return;
          }
          empty.style.display = 'none';
          note.style.display = 'block';
          grid.innerHTML = list.map(card).join('');
        }

        async function search(){
          const q = input.value.trim();
          if (!q){ render([]); empty.textContent = 'Type a disease name and press Search'; return; }
          try {
            button.disabled = true;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="margin-right:8px"></i> Searching';
            const res = await fetch('medicine_suggestion.php?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } });
            if (!res.ok){ throw new Error('Network error'); }
            const data = await res.json();
            if (data && data.ok){ render(data.data || []); }
            else { render([]); }
          } catch (e){
            render([]);
          } finally {
            button.disabled = false;
            button.innerHTML = '<i class="fa-solid fa-magnifying-glass" style="margin-right:8px"></i> Search';
          }
        }

        function escapeHtml(s){
          return String(s || '')
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/\"/g,'&quot;')
            .replace(/'/g,'&#039;');
        }

        // Interactions
        button.addEventListener('click', search);
        input.addEventListener('keydown', function(e){ if (e.key === 'Enter') search(); });

        // Prefill from URL (?disease=...)
        try {
          const url = new URL(window.location.href);
          const d = url.searchParams.get('disease');
          if (d){ input.value = d; search(); }
        } catch (_) {}
      })();
    </script>
  </body>
</html>


