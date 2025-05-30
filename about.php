<?php include __DIR__ . '/includes/header.php'; ?>

<style>
  /* Page-specific: Core Values Cards */
  .values-section {
    padding: 2rem 1rem;
    text-align: center;
  }
  .values-section h2 {
    margin-bottom: 1.5rem;
    font-size: 2rem;
    font-weight: 600;
  }
  .values-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    justify-content: center;
  }
  .value-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    padding: 1.5rem;
    max-width: 300px;
    flex: 1 1 250px;
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .value-card h3 {
    margin-bottom: 0.5rem;
    font-size: 1.25rem;
    color: #d32f2f;
  }
  .value-card p {
    font-size: 1rem;
    color: #444;
    line-height: 1.4;
  }
  .value-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 24px rgba(0,0,0,0.15);
  }
</style>

<section class="info-section">
  <h1>About Our Ambulance Management System</h1>
  <p>We are dedicated to providing efficient emergency medical services through our state-of-the-art ambulance management system.</p>

  <h2>Our Mission</h2>
  <p>Our mission is to save lives by providing rapid emergency medical response through efficient ambulance dispatch and management. We strive to minimize response times and maximize the quality of pre-hospital care.</p>

  <h2>Our Vision</h2>
  <p>We envision a future where no emergency goes unattended and where every individual has access to prompt, high-quality emergency medical services regardless of their location.</p>
</section>

<section class="values-section">
  <h2>Our Core Values</h2>
  <div class="values-grid">
    <div class="value-card">
      <h3>Rapid Response</h3>
      <p>We prioritize quick response times to ensure patients receive care when they need it most.</p>
    </div>
    <div class="value-card">
      <h3>Quality Care</h3>
      <p>Our teams are highly trained to provide the best possible pre-hospital care.</p>
    </div>
    <div class="value-card">
      <h3>Compassion</h3>
      <p>We treat every patient with dignity, respect, and compassion during their most vulnerable moments.</p>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
