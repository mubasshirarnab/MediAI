<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediAI Detect</title>
    <link rel="stylesheet" href="css/navbar.css"> <!-- Styles for the navbar -->
    <link rel="stylesheet" href="css/hospital_ai.css"> <!-- Styles for this specific page -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for navbar icon, already in navbar.php but good to be aware -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'hospitalnav.php'; ?>

    <main class="hospital-ai-container">
        <section class="detect-section">
            <h2>DETECT</h2>
        </section>

        <section class="description-section" style="margin: 40px 0; padding: 0 20px; position: relative;">
            <!-- DNA Helix Background Animation -->
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; z-index: 1;">
                <div class="dna-helix" style="position: absolute; top: -50%; left: -20%; width: 140%; height: 200%; opacity: 0.1;"></div>
            </div>
            
            <div style="background: linear-gradient(45deg, #0c0c0c 0%, #1a1a2e 25%, #16213e 50%, #0f3460 75%, #533483 100%); padding: 50px; border-radius: 30px; box-shadow: 0 20px 60px rgba(0,0,0,0.4); position: relative; overflow: hidden; z-index: 2;">
                
                <!-- Floating Medical Crosses -->
                <div class="floating-cross" style="position: absolute; top: 10%; left: 10%; font-size: 24px; color: rgba(255,255,255,0.1); animation: floatCross 8s ease-in-out infinite;">✚</div>
                <div class="floating-cross" style="position: absolute; top: 20%; right: 15%; font-size: 18px; color: rgba(255,255,255,0.08); animation: floatCross 6s ease-in-out infinite 1s;">✚</div>
                <div class="floating-cross" style="position: absolute; bottom: 15%; left: 20%; font-size: 20px; color: rgba(255,255,255,0.12); animation: floatCross 10s ease-in-out infinite 2s;">✚</div>
                
                <!-- Main Content -->
                <div style="position: relative; z-index: 3;">
                    <!-- Animated Title -->
                    <div style="text-align: center; margin-bottom: 40px;">
                        <div class="morphing-title" style="font-size: 36px; font-weight: 900; color: transparent; background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #feca57); background-size: 400% 400%; -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; animation: morphingGradient 4s ease-in-out infinite;">
                            AI MEDICAL DETECTION
                        </div>
                        <div style="width: 100px; height: 4px; background: linear-gradient(90deg, #ff6b6b, #4ecdc4, #45b7d1); margin: 20px auto; border-radius: 2px; animation: expandWidth 2s ease-in-out infinite;"></div>
                    </div>

                    <!-- Hexagonal Feature Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 40px;">
                        <div class="hex-card" style="background: rgba(255,255,255,0.05); backdrop-filter: blur(20px); border-radius: 20px; padding: 30px; border: 1px solid rgba(255,255,255,0.1); position: relative; overflow: hidden; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
                            <div class="hex-bg" style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: conic-gradient(from 0deg, transparent, rgba(255,107,107,0.1), transparent); animation: rotate 4s linear infinite;"></div>
                            <div style="position: relative; z-index: 2; text-align: center;">
                                <div class="pulse-icon" style="width: 80px; height: 80px; background: radial-gradient(circle, #ff6b6b, #ff4757); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; animation: pulse 2s ease-in-out infinite;">
                                    <i class="fas fa-dna" style="font-size: 32px; color: white;"></i>
                                </div>
                                <h4 style="color: white; font-size: 20px; font-weight: 700; margin: 0 0 15px 0; text-transform: uppercase; letter-spacing: 1px;">Genetic Analysis</h4>
                                <p style="color: rgba(255,255,255,0.8); font-size: 14px; line-height: 1.7; margin: 0;">Advanced pattern recognition for medical imaging</p>
                            </div>
                        </div>
                        
                        <div class="hex-card" style="background: rgba(255,255,255,0.05); backdrop-filter: blur(20px); border-radius: 20px; padding: 30px; border: 1px solid rgba(255,255,255,0.1); position: relative; overflow: hidden; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
                            <div class="hex-bg" style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: conic-gradient(from 120deg, transparent, rgba(78,205,196,0.1), transparent); animation: rotate 4s linear infinite reverse;"></div>
                            <div style="position: relative; z-index: 2; text-align: center;">
                                <div class="pulse-icon" style="width: 80px; height: 80px; background: radial-gradient(circle, #4ecdc4, #44a08d); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; animation: pulse 2s ease-in-out infinite 0.5s;">
                                    <i class="fas fa-brain" style="font-size: 32px; color: white;"></i>
                                </div>
                                <h4 style="color: white; font-size: 20px; font-weight: 700; margin: 0 0 15px 0; text-transform: uppercase; letter-spacing: 1px;">Neural Networks</h4>
                                <p style="color: rgba(255,255,255,0.8); font-size: 14px; line-height: 1.7; margin: 0;">Deep learning algorithms for precise detection</p>
                            </div>
                        </div>
                        
                        <div class="hex-card" style="background: rgba(255,255,255,0.05); backdrop-filter: blur(20px); border-radius: 20px; padding: 30px; border: 1px solid rgba(255,255,255,0.1); position: relative; overflow: hidden; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
                            <div class="hex-bg" style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: conic-gradient(from 240deg, transparent, rgba(69,183,209,0.1), transparent); animation: rotate 4s linear infinite;"></div>
                            <div style="position: relative; z-index: 2; text-align: center;">
                                <div class="pulse-icon" style="width: 80px; height: 80px; background: radial-gradient(circle, #45b7d1, #2980b9); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; animation: pulse 2s ease-in-out infinite 1s;">
                                    <i class="fas fa-microscope" style="font-size: 32px; color: white;"></i>
                                </div>
                                <h4 style="color: white; font-size: 20px; font-weight: 700; margin: 0 0 15px 0; text-transform: uppercase; letter-spacing: 1px;">Precision Scanning</h4>
                                <p style="color: rgba(255,255,255,0.8); font-size: 14px; line-height: 1.7; margin: 0;">Microscopic-level accuracy in medical imaging</p>
                            </div>
                        </div>
                    </div>

                    <!-- Animated Description -->
                    <div style="text-align: center; background: rgba(255,255,255,0.03); backdrop-filter: blur(15px); border-radius: 20px; padding: 30px; border: 1px solid rgba(255,255,255,0.05); position: relative; overflow: hidden;">
                        <div class="scan-line" style="position: absolute; top: 0; left: -100%; width: 100%; height: 2px; background: linear-gradient(90deg, transparent, #4ecdc4, transparent); animation: scan 3s ease-in-out infinite;"></div>
                        <p style="color: white; font-size: 16px; line-height: 1.8; margin: 0; font-weight: 400; position: relative; z-index: 2;">
                            <span style="color: #ff6b6b; font-weight: 600;">Revolutionary</span> AI-powered medical imaging analysis that transforms <span style="color: #4ecdc4; font-weight: 600;">complex scans</span> into <span style="color: #45b7d1; font-weight: 600;">instant insights</span>, empowering healthcare professionals with unprecedented diagnostic capabilities.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <style>
            /* DNA Helix Animation */
            .dna-helix::before {
                content: '';
                position: absolute;
                width: 2px;
                height: 100%;
                background: linear-gradient(180deg, transparent 0%, #4ecdc4 50%, transparent 100%);
                left: 50%;
                animation: dnaMove 4s ease-in-out infinite;
            }
            
            .dna-helix::after {
                content: '';
                position: absolute;
                width: 2px;
                height: 100%;
                background: linear-gradient(180deg, transparent 0%, #ff6b6b 50%, transparent 100%);
                left: 50%;
                animation: dnaMove 4s ease-in-out infinite reverse;
            }
            
            @keyframes dnaMove {
                0%, 100% { transform: translateX(-20px) rotateY(0deg); }
                50% { transform: translateX(20px) rotateY(180deg); }
            }
            
            /* Floating Crosses */
            @keyframes floatCross {
                0%, 100% { transform: translateY(0px) rotate(0deg) scale(1); opacity: 0.1; }
                50% { transform: translateY(-20px) rotate(180deg) scale(1.2); opacity: 0.3; }
            }
            
            /* Morphing Gradient */
            @keyframes morphingGradient {
                0%, 100% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
            }
            
            /* Expanding Line */
            @keyframes expandWidth {
                0%, 100% { width: 100px; }
                50% { width: 200px; }
            }
            
            /* Rotating Background */
            @keyframes rotate {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            
            /* Pulse Animation */
            @keyframes pulse {
                0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255,255,255,0.3); }
                50% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(255,255,255,0); }
            }
            
            /* Scanning Line */
            @keyframes scan {
                0% { left: -100%; }
                100% { left: 100%; }
            }
            
            /* Hex Card Hover Effects */
            .hex-card:hover {
                transform: translateY(-10px) scale(1.02);
                box-shadow: 0 20px 40px rgba(0,0,0,0.3);
                border-color: rgba(255,255,255,0.3);
            }
            
            .hex-card:hover .pulse-icon {
                animation: pulse 1s ease-in-out infinite;
            }
            
            /* Neural Network Animations */
            @keyframes neuronPulse {
                0%, 100% { transform: scale(1); opacity: 0.3; }
                50% { transform: scale(1.5); opacity: 1; }
            }
            
            @keyframes connectionFlow {
                0% { transform: translateX(-100%); opacity: 0; }
                50% { opacity: 1; }
                100% { transform: translateX(100%); opacity: 0; }
            }
            
            /* Upload Animations */
            @keyframes uploadSpin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            
            /* Upload Area Hover Effects */
            .upload-area:hover {
                border-color: rgba(78,205,196,0.6);
                transform: translateY(-2px);
                box-shadow: 0 10px 30px rgba(78,205,196,0.2);
            }
            
            .upload-area:hover .upload-glow {
                opacity: 1;
            }
            
            .upload-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(78,205,196,0.4);
            }
            
            /* Detection Card Animations */
            @keyframes cardRotate {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            
            .detect-card:hover {
                transform: translateY(-10px) scale(1.05);
                box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            }
            
            .detect-card:hover .detect-icon {
                animation: pulse 1s ease-in-out infinite;
            }
            
            /* Neural Network Positioning */
            .neural-network .neuron:nth-child(1) { top: 20%; left: 10%; }
            .neural-network .neuron:nth-child(2) { top: 60%; right: 15%; }
            .neural-network .neuron:nth-child(3) { bottom: 30%; left: 50%; }
            .neural-network .connection:nth-child(4) { top: 25%; left: 15%; width: 30%; }
            .neural-network .connection:nth-child(5) { bottom: 35%; right: 20%; width: 25%; }
        </style>

        <form id="detection-form" enctype="multipart/form-data">
            <section class="upload-section" style="margin: 40px 0; padding: 0 20px;">
                <div style="background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%); padding: 40px; border-radius: 25px; box-shadow: 0 15px 40px rgba(0,0,0,0.3); position: relative; overflow: hidden;">
                    
                    <!-- Neural Network Background -->
                    <div class="neural-network" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0.1;">
                        <div class="neuron" style="position: absolute; width: 4px; height: 4px; background: #4ecdc4; border-radius: 50%; animation: neuronPulse 3s ease-in-out infinite;"></div>
                        <div class="neuron" style="position: absolute; width: 4px; height: 4px; background: #ff6b6b; border-radius: 50%; animation: neuronPulse 3s ease-in-out infinite 1s;"></div>
                        <div class="neuron" style="position: absolute; width: 4px; height: 4px; background: #45b7d1; border-radius: 50%; animation: neuronPulse 3s ease-in-out infinite 2s;"></div>
                        <div class="connection" style="position: absolute; height: 1px; background: linear-gradient(90deg, transparent, #4ecdc4, transparent); animation: connectionFlow 4s linear infinite;"></div>
                        <div class="connection" style="position: absolute; height: 1px; background: linear-gradient(90deg, transparent, #ff6b6b, transparent); animation: connectionFlow 4s linear infinite 2s;"></div>
                    </div>
                    
                    <div style="position: relative; z-index: 2;">
                        <div style="text-align: center; margin-bottom: 30px;">
                            <div class="upload-icon-container" style="width: 120px; height: 120px; background: radial-gradient(circle, rgba(78,205,196,0.2), rgba(255,107,107,0.1)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; position: relative; border: 2px solid rgba(78,205,196,0.3);">
                                <div class="upload-rings" style="position: absolute; width: 100%; height: 100%; border: 2px solid transparent; border-top: 2px solid #4ecdc4; border-radius: 50%; animation: uploadSpin 2s linear infinite;"></div>
                                <div class="upload-rings" style="position: absolute; width: 80%; height: 80%; border: 2px solid transparent; border-right: 2px solid #ff6b6b; border-radius: 50%; animation: uploadSpin 2s linear infinite reverse;"></div>
                                <div class="upload-rings" style="position: absolute; width: 60%; height: 60%; border: 2px solid transparent; border-bottom: 2px solid #45b7d1; border-radius: 50%; animation: uploadSpin 2s linear infinite;"></div>
                                <i class="fas fa-cloud-upload-alt" style="font-size: 40px; color: white; z-index: 2; position: relative;"></i>
                            </div>
                            <h3 style="color: white; font-size: 24px; font-weight: 700; margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 2px;">Upload Medical Image</h3>
                            <p style="color: rgba(255,255,255,0.7); font-size: 16px; margin: 0;">Select your medical scan for AI analysis</p>
                        </div>
                        
                        <div class="upload-area" style="background: rgba(255,255,255,0.05); backdrop-filter: blur(20px); border: 2px dashed rgba(78,205,196,0.3); border-radius: 20px; padding: 40px; text-align: center; position: relative; transition: all 0.3s ease; cursor: pointer;" onclick="document.getElementById('image-upload').click();">
                            <div class="upload-glow" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at center, rgba(78,205,196,0.1) 0%, transparent 70%); border-radius: 20px; opacity: 0; transition: opacity 0.3s ease;"></div>
                            <p style="color: white; font-size: 18px; margin: 0 0 20px 0; font-weight: 500;">Drop your image here or click to browse</p>
                            <button class="upload-btn" type="button" style="background: linear-gradient(45deg, #4ecdc4, #44a08d); border: none; color: white; padding: 15px 30px; border-radius: 25px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 5px 15px rgba(78,205,196,0.3);">
                                <i class="fas fa-folder-open" style="margin-right: 10px;"></i>Choose File
                            </button>
                            <input type="file" id="image-upload" name="image" accept="image/*" style="display: none;" required>
                            <span id="file-name" style="color: #4ecdc4; display: block; margin-top: 15px; font-weight: 500; font-size: 14px;"></span>
                            <div id="image-preview" style="margin-top: 20px; display: none;">
                                <img id="preview-img" style="max-width: 200px; max-height: 200px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); border: 3px solid rgba(78,205,196,0.3);">
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="patient-info-section" style="margin: 40px 0; padding: 0 20px;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h3 style="color: #2c3e50; font-size: 28px; font-weight: 700; margin: 0; text-transform: uppercase; letter-spacing: 2px;">Select Detection Type</h3>
                    <div style="width: 80px; height: 4px; background: linear-gradient(90deg, #4ecdc4, #ff6b6b); margin: 20px auto; border-radius: 2px;"></div>
                </div>
                
                <div class="detect-buttons" style="display: flex; justify-content: center; gap: 30px; flex-wrap: wrap;">
                    <div class="detect-card" onclick="detectDisease('breast')" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 20px; cursor: pointer; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative; overflow: hidden; min-width: 200px; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);">
                        <div class="card-bg" style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: conic-gradient(from 0deg, transparent, rgba(255,255,255,0.1), transparent); animation: cardRotate 6s linear infinite;"></div>
                        <div style="position: relative; z-index: 2; text-align: center;">
                            <div class="detect-icon" style="width: 80px; height: 80px; background: radial-gradient(circle, rgba(255,255,255,0.2), rgba(255,255,255,0.1)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; border: 2px solid rgba(255,255,255,0.3);">
                                <i class="fas fa-female" style="font-size: 32px; color: white;"></i>
                            </div>
                            <h4 style="color: white; font-size: 18px; font-weight: 700; margin: 0; text-transform: uppercase; letter-spacing: 1px;">Breast Cancer</h4>
                            <p style="color: rgba(255,255,255,0.8); font-size: 12px; margin: 10px 0 0 0;">AI-powered detection</p>
                        </div>
                    </div>
                    
                    <div class="detect-card" onclick="window.location.href='https://8aa9ee69e1ed3ac04d.gradio.live';" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); padding: 30px; border-radius: 20px; cursor: pointer; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative; overflow: hidden; min-width: 200px; box-shadow: 0 10px 30px rgba(255, 107, 107, 0.3);">
                        <div class="card-bg" style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: conic-gradient(from 180deg, transparent, rgba(255,255,255,0.1), transparent); animation: cardRotate 6s linear infinite reverse;"></div>
                        <div style="position: relative; z-index: 2; text-align: center;">
                            <div class="detect-icon" style="width: 80px; height: 80px; background: radial-gradient(circle, rgba(255,255,255,0.2), rgba(255,255,255,0.1)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; border: 2px solid rgba(255,255,255,0.3);">
                                <i class="fas fa-brain" style="font-size: 32px; color: white;"></i>
                            </div>
                            <h4 style="color: white; font-size: 18px; font-weight: 700; margin: 0; text-transform: uppercase; letter-spacing: 1px;">Brain Tumor</h4>
                            <p style="color: rgba(255,255,255,0.8); font-size: 12px; margin: 10px 0 0 0;">Neural network analysis</p>
                        </div>
                    </div>
                </div>
            </section>
        </form>

        <!-- Results Section -->
        <section id="results-section" style="display: none; margin-top: 30px;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); color: white;">
                <div style="text-align: center; margin-bottom: 25px;">
                    <i class="fas fa-microscope" style="font-size: 48px; margin-bottom: 15px; display: block; color: #ffd700;"></i>
                    <h3 style="margin: 0; font-size: 28px; font-weight: 600; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">Detection Results</h3>
                </div>
                <div id="results-content"></div>
            </div>
        </section>

        <!-- Loading Section -->
        <section id="loading-section" style="display: none; margin-top: 30px; text-align: center;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); color: white;">
                <i class="fas fa-spinner fa-spin" style="font-size: 48px; margin-bottom: 20px; display: block; color: #ffd700;"></i>
                <h3 style="margin: 0 0 10px 0; font-size: 24px; font-weight: 600;">Analyzing Image</h3>
                <p style="margin: 0; font-size: 16px; opacity: 0.9;">Please wait while our AI processes your image...</p>
            </div>
        </section>
    </main>

</body>
<script>
document.getElementById('image-upload').addEventListener('change', function(){
    var file = this.files[0];
    if (file) {
        var fileName = file.name;
        document.getElementById('file-name').textContent = fileName;
        
        // Show image preview
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('image-preview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('file-name').textContent = '';
        document.getElementById('image-preview').style.display = 'none';
    }
});

function detectDisease(type) {
    var fileInput = document.getElementById('image-upload');
    var file = fileInput.files[0];
    
    if (!file) {
        alert('Please upload an image first!');
        return;
    }
    
    // Show loading
    document.getElementById('loading-section').style.display = 'block';
    document.getElementById('results-section').style.display = 'none';
    
    var formData = new FormData();
    formData.append('image', file);
    formData.append('type', type);
    
    fetch('detect_disease.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('loading-section').style.display = 'none';
        document.getElementById('results-section').style.display = 'block';
        
        var resultsContent = document.getElementById('results-content');
        if (data.success) {
            // Determine icon and color based on result
            var resultIcon = '';
            var resultColor = '';
            
            if (data.result.toLowerCase().includes('malignant') || data.result.toLowerCase().includes('tumor detected')) {
                resultIcon = 'fas fa-exclamation-triangle';
                resultColor = '#ff6b6b';
            } else if (data.result.toLowerCase().includes('benign') || data.result.toLowerCase().includes('no tumor')) {
                resultIcon = 'fas fa-check-circle';
                resultColor = '#51cf66';
            } else {
                resultIcon = 'fas fa-info-circle';
                resultColor = '#339af0';
            }
            
            resultsContent.innerHTML = `
                <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 15px; padding: 25px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.2);">
                    <div style="display: flex; align-items: center; margin-bottom: 20px;">
                        <div style="background: ${resultColor}; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                            <i class="${resultIcon}" style="font-size: 24px; color: white;"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0 0 5px 0; font-size: 20px; font-weight: 600;">${data.detection_type}</h4>
                            <p style="margin: 0; opacity: 0.9; font-size: 14px;">Analysis completed successfully</p>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 12px; text-align: center; border: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px;">
                        <i class="fas fa-chart-line" style="font-size: 24px; color: #ffd700; margin-bottom: 10px; display: block;"></i>
                        <h5 style="margin: 0 0 8px 0; font-size: 16px; font-weight: 600;">Result</h5>
                        <p style="margin: 0; font-size: 18px; font-weight: 700; color: ${resultColor};">${data.result}</p>
                    </div>
                    
                    <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; border-left: 4px solid #ffd700;">
                        <div style="display: flex; align-items: center; margin-bottom: 10px;">
                            <i class="fas fa-clipboard-list" style="font-size: 18px; color: #ffd700; margin-right: 10px;"></i>
                            <h5 style="margin: 0; font-size: 16px; font-weight: 600;">Analysis Details</h5>
                        </div>
                        <p style="margin: 0; line-height: 1.6; opacity: 0.9;">${data.details || 'No additional details available.'}</p>
                    </div>
                </div>
            `;
        } else {
            resultsContent.innerHTML = `
                <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 15px; padding: 25px; border: 1px solid rgba(255,255,255,0.2);">
                    <div style="display: flex; align-items: center; margin-bottom: 20px;">
                        <div style="background: #ff6b6b; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                            <i class="fas fa-exclamation-circle" style="font-size: 24px; color: white;"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0 0 5px 0; font-size: 20px; font-weight: 600;">Detection Error</h4>
                            <p style="margin: 0; opacity: 0.9; font-size: 14px;">Something went wrong during analysis</p>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255,107,107,0.1); padding: 20px; border-radius: 12px; border-left: 4px solid #ff6b6b;">
                        <div style="display: flex; align-items: center; margin-bottom: 10px;">
                            <i class="fas fa-info-circle" style="font-size: 18px; color: #ff6b6b; margin-right: 10px;"></i>
                            <h5 style="margin: 0; font-size: 16px; font-weight: 600; color: #ff6b6b;">Error Message</h5>
                        </div>
                        <p style="margin: 0; line-height: 1.6; color: #ff6b6b;">${data.error}</p>
                    </div>
                </div>
            `;
        }
    })
    .catch(error => {
        document.getElementById('loading-section').style.display = 'none';
        document.getElementById('results-section').style.display = 'block';
        document.getElementById('results-content').innerHTML = `
            <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 15px; padding: 25px; border: 1px solid rgba(255,255,255,0.2);">
                <div style="display: flex; align-items: center; margin-bottom: 20px;">
                    <div style="background: #ff6b6b; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                        <i class="fas fa-wifi-slash" style="font-size: 24px; color: white;"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0 0 5px 0; font-size: 20px; font-weight: 600;">Connection Error</h4>
                        <p style="margin: 0; opacity: 0.9; font-size: 14px;">Unable to connect to detection service</p>
                    </div>
                </div>
                
                <div style="background: rgba(255,107,107,0.1); padding: 20px; border-radius: 12px; border-left: 4px solid #ff6b6b;">
                    <div style="display: flex; align-items: center; margin-bottom: 10px;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 18px; color: #ff6b6b; margin-right: 10px;"></i>
                        <h5 style="margin: 0; font-size: 16px; font-weight: 600; color: #ff6b6b;">Service Unavailable</h5>
                    </div>
                    <p style="margin: 0; line-height: 1.6; color: #ff6b6b;">Failed to connect to the detection service. Please try again later.</p>
                </div>
            </div>
        `;
        console.error('Error:', error);
    });
}
</script>
</html>
