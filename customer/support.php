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

    <article class="card" id="agent">
        <h2>Direct Contact Info</h2>
        <div class="contact-methods">
            <div style="margin-bottom: 1.5rem;">
                <h3><?= iconMarkup('group'); ?> Your Assigned Agent</h3>
                <p><strong>Maria Santos</strong></p>
                <p>Senior Insurance Consultant</p>
                <p>Email: <a href="mailto:m.santos@risksecure.com">m.santos@risksecure.com</a></p>
                <div style="margin-top: 0.5rem;">
                    <button class="btn-action" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Book a Meeting</button>
                </div>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <h3><?= iconMarkup('notifications'); ?> Support Hotline</h3>
                <p>Call us at <strong>+63 (2) 8888-SECURE</strong></p>
                <p>Available Mon-Fri, 8:00 AM - 6:00 PM</p>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <h3><?= iconMarkup('person'); ?> Email Support</h3>
                <p>Send an email to <a href="mailto:support@risksecure.com">support@risksecure.com</a></p>
            </div>
            <div>
                <h3><?= iconMarkup('event'); ?> Visit Our Office</h3>
                <p>123 RiskSecure Plaza, Makati City, Philippines</p>
            </div>
        </div>
    </article>
</section>

<section class="card" id="faq" style="margin-top: 2rem;">
    <h2><?= iconMarkup('notifications'); ?> Frequently Asked Questions</h2>
    <div class="grid cols-2">
        <div class="faq-item">
            <p><strong>How do I renew my policy?</strong></p>
            <p class="text-muted">You can renew your policy through the dashboard 30 days before expiration.</p>
        </div>
        <div class="faq-item">
            <p><strong>When will my claim be processed?</strong></p>
            <p class="text-muted">Standard claims are processed within 5-7 business days after all documents are received.</p>
        </div>
        <div class="faq-item">
            <p><strong>Can I change my coverage mid-term?</strong></p>
            <p class="text-muted">Yes, please contact your agent to discuss coverage adjustments.</p>
        </div>
        <div class="faq-item">
            <p><strong>What happens if I miss a payment?</strong></p>
            <p class="text-muted">There is a 15-day grace period for most policies before coverage is suspended.</p>
        </div>
    </div>
</section>

<?php
renderFooter();
