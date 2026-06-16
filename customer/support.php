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
                <textarea name="message" id="message" style="min-height: 80px;" placeholder=" " required></textarea>
                <label for="message">Message</label>
            </div>
            <div>
                <button type="submit" class="btn-full">Send Message</button>
            </div>
        </form>
    </article>

    <article class="card" id="agent">
        <h2>Support & Agent Info</h2>
        <div class="contact-methods grid cols-2" style="gap: 1rem;">
            <div>
                <h3><?= iconMarkup('group'); ?> Your Agent</h3>
                <p><strong>Maria Santos</strong></p>
                <p><small>Senior Consultant</small></p>
                <p><small><a href="mailto:m.santos@risksecure.com">m.santos@risksecure.com</a></small></p>
            </div>
            <div>
                <h3><?= iconMarkup('notifications'); ?> Hotline</h3>
                <p><strong>+63 (2) 8888-SECURE</strong></p>
                <p><small>Mon-Fri, 8AM - 6PM</small></p>
            </div>
            <div>
                <h3><?= iconMarkup('person'); ?> Email</h3>
                <p><small><a href="mailto:support@risksecure.com">support@risksecure.com</a></small></p>
            </div>
            <div>
                <h3><?= iconMarkup('event'); ?> Office</h3>
                <p><small>Makati City, Philippines</small></p>
            </div>
        </div>
        <div style="margin-top: 1rem;">
            <button class="btn-action" style="width: 100%; padding: 0.5rem;">Book a Meeting</button>
        </div>
    </article>
</section>

<section class="card" id="faq" style="margin-top: 1.5rem;">
    <h2><?= iconMarkup('notifications'); ?> Quick FAQ</h2>
    <div class="grid cols-2" style="gap: 1.5rem;">
        <div class="faq-item">
            <p><strong>How do I renew?</strong></p>
            <p class="text-muted"><small>Renew via dashboard 30 days before expiration.</small></p>
        </div>
        <div class="faq-item">
            <p><strong>Claim time?</strong></p>
            <p class="text-muted"><small>Standard claims take 5-7 business days.</small></p>
        </div>
        <div class="faq-item">
            <p><strong>Coverage changes?</strong></p>
            <p class="text-muted"><small>Contact your agent to adjust coverage.</small></p>
        </div>
        <div class="faq-item">
            <p><strong>Missed payment?</strong></p>
            <p class="text-muted"><small>15-day grace period before suspension.</small></p>
        </div>
    </div>
</section>

<?php
renderFooter();
