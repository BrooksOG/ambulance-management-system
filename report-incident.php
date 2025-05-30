<?php
// Including the database connection file
include 'includes/db_connect.php';

// Handling form submission to create a new incident
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emergency_type = mysqli_real_escape_string($conn, $_POST['emergency_type']);
    // Simplify severity for storage
    $severity = mysqli_real_escape_string($conn, $_POST['severity']);
    switch ($severity) {
        case 'Critical - Life Threatening':
            $severity = 'CRITICAL';
            break;
        case 'High - Severe':
            $severity = 'HIGH';
            break;
        case 'Medium - Urgent but Stable':
            $severity = 'MEDIUM';
            break;
        case 'Low - Non Life Threatening':
            $severity = 'LOW';
            break;
        default:
            $severity = 'UNKNOWN';
    }
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $landmark = mysqli_real_escape_string($conn, $_POST['landmark']);
    $incident_details = mysqli_real_escape_string($conn, $_POST['incident_details']);
    $reporter_name = !empty($_POST['reporter_name']) ? mysqli_real_escape_string($conn, $_POST['reporter_name']) : NULL;
    $contact_phone = !empty($_POST['contact_phone']) ? mysqli_real_escape_string($conn, $_POST['contact_phone']) : NULL;
    $status = 'UNVERIFIED'; // Explicitly set default status

    // Combine location and landmark
    $full_location = $location;
    if (!empty($landmark)) {
        $full_location .= ", " . $landmark;
    }

    // Basic validation for required fields
    if (empty($emergency_type) || empty($severity) || empty($location) || empty($incident_details)) {
        $error = "All required fields must be filled.";
    } else {
        $query = "INSERT INTO incidents (narrative, location, severity, status, reporter_name, emergency_type, contact_phone, submitted_at) 
                  VALUES ('$incident_details', '$full_location', '$severity', '$status', " . 
                  ($reporter_name ? "'$reporter_name'" : "NULL") . ", '$emergency_type', " . 
                  ($contact_phone ? "'$contact_phone'" : "NULL") . ", NOW())";
        if (mysqli_query($conn, $query)) {
            $message = "Emergency reported successfully. Help is on the way.";
        } else {
            $error = "Error reporting emergency: " . mysqli_error($conn);
        }
    }
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<section class="info-section">
  <h2>Report an Emergency</h2>
  <?php 
  if (isset($message)) {
      echo '<p class="message">' . $message . '</p>';
  }
  if (isset($error)) {
      echo '<p class="error">' . $error . '</p>';
  }
  ?>
  <form method="POST" action="" class="emergency-form">
    <div class="form-group">
      <label for="emergency_type">Emergency Type <span class="required">*</span></label>
      <select id="emergency_type" name="emergency_type" class="form-control" required>
        <option value="">Select Emergency Type</option>
        <option value="Traffic accident">Traffic Accident</option>
        <option value="Cardiac emergency">Cardiac Emergency</option>
        <option value="Respiratory">Respiratory</option>
        <option value="Other">Other</option>
        <option value="I dont know">I Don't Know</option>
      </select>
    </div>

    <div class="form-group">
      <label for="severity">Severity <span class="required">*</span></label>
      <select id="severity" name="severity" class="form-control" required>
        <option value="">Select Severity</option>
        <option value="Critical - Life Threatening">Critical - Life Threatening</option>
        <option value="High - Severe">High - Severe</option>
        <option value="Medium - Urgent but Stable">Medium - Urgent but Stable</option>
        <option value="Low - Non Life Threatening">Low - Non Life Threatening</option>
      </select>
    </div>

    <div class="form-group">
      <label for="location">Location <span class="required">*</span></label>
      <select id="location" name="location" class="form-control" required>
        <option value="">Select Location</option>
        <option value="Huruma">Huruma</option>
        <option value="Kiamaiko">Kiamaiko</option>
        <option value="Mlango Kubwa">Mlango Kubwa</option>
        <option value="Pangani">Pangani</option>
        <option value="Kariobangi">Kariobangi</option>
        <option value="Other">Other</option>
      </select>
    </div>

    <div class="form-group">
      <label for="landmark">Landmark (e.g., near a specific place)</label>
      <input type="text" id="landmark" name="landmark" class="form-control">
    </div>

    <div class="form-group">
      <label for="incident_details">Incident Details (max 500 characters) <span class="required">*</span></label>
      <textarea id="incident_details" name="incident_details" class="form-control" maxlength="500" placeholder="Provide a detailed description of the incident (max 500 characters)." required></textarea>
    </div>

    <div class="form-group">
      <label for="reporter_name">Your Name (optional)</label>
      <input type="text" id="reporter_name" name="reporter_name" class="form-control">
    </div>

    <div class="form-group">
      <label for="contact_phone">Contact Phone</label>
      <input type="tel" id="contact_phone" name="contact_phone" class="form-control">
    </div>

    <button type="submit" class="btn btn-primary">Submit Emergency</button>
  </form>
  <p><a href="index.php" class="btn btn-secondary">Back to Home</a></p>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<style>
  .info-section {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  }

  .emergency-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }

  .form-control {
    padding: 10px;
    font-size: 16px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 100%;
    box-sizing: border-box;
  }

  .form-control:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
  }

  label {
    font-weight: bold;
    color: #333;
  }

  .required {
    color: #dc3545;
  }

  .btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 16px;
    transition: background-color 0.3s;
  }

  .btn-primary {
    background-color: #dc3545;
    color: white;
  }

  .btn-primary:hover {
    background-color: #c82333;
  }

  .btn-secondary {
    background-color: #6c757d;
    color: white;
  }

  .btn-secondary:hover {
    background-color: #5a6268;
  }

  .message {
    padding: 10px;
    border-radius: 4px;
    background-color: #d4edda;
    color: #155724;
    margin-bottom: 15px;
  }

  .error {
    padding: 10px;
    border-radius: 4px;
    background-color: #f8d7da;
    color: #721c24;
    margin-bottom: 15px;
  }

  @media (max-width: 600px) {
    .info-section {
      padding: 15px;
    }

    .form-control {
      font-size: 14px;
    }

    .btn {
      font-size: 14px;
      padding: 8px 16px;
    }
  }
</style>