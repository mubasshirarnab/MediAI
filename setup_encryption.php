<?php
/**
 * Encryption Key Generator and Setup
 * Run this script once to set up encryption for the system
 */

require_once 'encryption_helper.php';

echo "=== Medical Data Encryption Setup ===\n\n";

// Generate a new encryption key
echo "1. Generating encryption key...\n";
$encryption_key = EncryptionHelper::generateKey();
echo "   Key generated successfully!\n";
echo "   Key length: " . strlen(base64_decode($encryption_key)) . " bytes\n\n";

// Save key to environment file
echo "2. Setting up environment configuration...\n";
$env_content = "<?php\n";
$env_content .= "// Medical Data Encryption Key\n";
$env_content .= "putenv('MEDICAL_ENCRYPTION_KEY=" . base64_decode($encryption_key) . "');\n";
$env_content .= "\$_ENV['MEDICAL_ENCRYPTION_KEY'] = '" . base64_decode($encryption_key) . "';\n";

file_put_contents(__DIR__ . '/encryption_config.php', $env_content);
echo "   Environment configuration saved!\n\n";

// Test encryption/decryption
echo "3. Testing encryption/decryption...\n";
$test_data = "Patient John Doe has diabetes and requires insulin therapy. Blood sugar levels: 140-180 mg/dL.";

try {
    $encrypted = EncryptionHelper::encryptData($test_data);
    echo "   Encryption: SUCCESS\n";
    echo "   Encrypted length: " . strlen($encrypted['encrypted']) . " chars\n";
    echo "   IV length: " . strlen($encrypted['iv']) . " chars\n";
    
    $decrypted = EncryptionHelper::decryptData($encrypted['encrypted'], $encrypted['iv']);
    echo "   Decryption: SUCCESS\n";
    echo "   Data integrity: " . ($decrypted === $test_data ? "VERIFIED" : "FAILED") . "\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

echo "\n4. Creating database tables...\n";
require_once 'dbConnect.php';
require_once 'encrypted_data_handler.php';

try {
    $handler = new EncryptedDataHandler($conn);
    $handler->createEncryptedTables();
    echo "   Database tables created successfully!\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Setup Complete ===\n";
echo "Encryption key: " . $encryption_key . "\n";
echo "Store this key securely in your environment variables!\n\n";

// Test with medical data
echo "=== Testing Medical Data Encryption ===\n";

$medical_reports = [
    "Patient shows elevated cholesterol levels: LDL 180 mg/dL, HDL 35 mg/dL. Recommend statin therapy.",
    "Chest X-ray shows clear lungs, no signs of pneumonia or effusion. Cardiac silhouette normal.",
    "Blood work indicates anemia: Hemoglobin 9.8 g/dL, Hematocrit 29%. Recommend iron supplements."
];

$patient_notes = [
    "Patient complains of persistent headaches, occurring 3-4 times per week. No history of migraine.",
    "Diabetic patient reports difficulty maintaining blood sugar control despite medication adherence.",
    "Post-operative recovery progressing well. Wound healing normally, no signs of infection."
];

$meeting_codes = ["MED-2024-001", "CONSULT-456", "FOLLOWUP-789"];

foreach ($medical_reports as $i => $report) {
    try {
        $encrypted = EncryptionHelper::encryptData($report);
        $decrypted = EncryptionHelper::decryptData($encrypted['encrypted'], $encrypted['iv']);
        echo "Medical Report " . ($i + 1) . ": " . ($decrypted === $report ? "✓" : "✗") . "\n";
    } catch (Exception $e) {
        echo "Medical Report " . ($i + 1) . ": ERROR - " . $e->getMessage() . "\n";
    }
}

foreach ($patient_notes as $i => $note) {
    try {
        $encrypted = EncryptionHelper::encryptData($note);
        $decrypted = EncryptionHelper::decryptData($encrypted['encrypted'], $encrypted['iv']);
        echo "Patient Note " . ($i + 1) . ": " . ($decrypted === $note ? "✓" : "✗") . "\n";
    } catch (Exception $e) {
        echo "Patient Note " . ($i + 1) . ": ERROR - " . $e->getMessage() . "\n";
    }
}

foreach ($meeting_codes as $i => $code) {
    try {
        $encrypted = EncryptionHelper::encryptData($code);
        $decrypted = EncryptionHelper::decryptData($encrypted['encrypted'], $encrypted['iv']);
        echo "Meeting Code " . ($i + 1) . ": " . ($decrypted === $code ? "✓" : "✗") . "\n";
    } catch (Exception $e) {
        echo "Meeting Code " . ($i + 1) . ": ERROR - " . $e->getMessage() . "\n";
    }
}

echo "\n=== All Tests Complete ===\n";
?>
