<?php include __DIR__ . '/includes/header.php'; ?>

<style>
  /* Page-specific: Services Cards */
  .services-section {
    padding: 3rem 1rem;
    text-align: center;
  }
  .services-section h1 {
    font-size: 2.25rem;
    margin-bottom: 2rem;
    font-weight: 600;
  }
  .services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 1.5rem;
    justify-items: center;
  }
  .service-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    padding: 1.5rem;
    max-width: 300px;
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .service-card h3 {
    font-size: 1.25rem;
    color: #d32f2f;
    margin-bottom: 0.75rem;
  }
  .service-card p {
    font-size: 1rem;
    color: #444;
    line-height: 1.4;
  }
  .service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 24px rgba(0,0,0,0.15);
  }
  @media (max-width: 600px) {
    .services-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<section class="services-section">
  <h1>Our Services</h1>
  <div class="services-grid">
    <div class="service-card">
      <h3>Emergency Response</h3>
      <p>Rapid dispatch of Advanced Life Support (ALS) and Basic Life Support (BLS) ambulances to critical incidents, 24/7.</p>
    </div>
    <div class="service-card">
      <h3>Patient Transport</h3>
      <p>Non-emergency transport services for hospitals, clinics, and home care, ensuring safe, comfortable journeys.</p>
    </div>
    <div class="service-card">
      <h3>Event Standby</h3>
      <p>On-site medical coverage for concerts, sporting events, corporate gatherings, and public festivals.</p>
    </div>
    <div class="service-card">
      <h3>First Aid Training</h3>
      <p>CPR, Basic Life Support, and AED usage courses for organizations, schools, and community groups.</p>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
