<?php

declare(strict_types=1);

require_once __DIR__ . '/../shared/includes/layout.php';
require_once __DIR__ . '/../shared/includes/auth.php';
require_once __DIR__ . '/security/gatekeeper.php';

requireCustomerLogin();

$customerName = customerName();
$customerEmail = customerEmail();

renderHeader('Contact Support');
?>

<div class="welcome-hero">
    <div class="hero-content">
        <h1>Contact Support</h1>
        <p>We're here to help. Reach out to our dedicated support team for any inquiries or assistance.</p>
    </div>
</div>

<section class="grid cols-2">
    <article class="card">
        <h2>Send us a Message</h2>
        <form action="#" method="post" class="grid" data-validate="true">
            <div class="form-field">
                <select name="subject" id="subject" required>
                    <option value="policy">Policy Inquiry</option>
                    <option value="claim">Claim Assistance</option>
                    <option value="billing">Billing & Payments</option>
                    <option value="technical">Technical Support</option>
                    <option value="other">Other</option>
                </select>
                <label for="subject">Subject</label>
            </div>
            <div class="form-field">
                <textarea name="message" id="message" placeholder=" " required></textarea>
                <label for="message">Message</label>
            </div>
            <div>
                <button type="submit">Send Message</button>
            </div>
        </form>
    </article>

    <article class="card">
        <h2>Direct Contact Info</h2>
        <div class="contact-methods">
            <div style="margin-bottom: 1.5rem;">
                <h3><?= iconMarkup('notifications'); ?> Support Hotline</h3>
                <p>Call us at <strong>+63 (2) 8888-SECURE</strong></p>
                <p>Available Mon-Fri, 8:00 AM - 6:00 PM</p>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <h3><?= iconMarkup('person'); ?> Email Support</h3>
                <p>Send an email to <a href="mailto:support@risksecure.com">support@risksecure.com</a></p>
                <p>Typical response time is within 24 hours.</p>
            </div>
            <div>
                <h3><?= iconMarkup('event'); ?> Visit Our Office</h3>
                <p>123 RiskSecure Plaza, Makati City, Philippines</p>
            </div>
        </div>
    </article>
</section>

<?php
renderFooter();
