<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Antigo UI/UX Advisory — Homepage Mockup</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css?v=2.0">
</head>
<body>

<header>
  <nav>
    <a href="#home" class="logo">
      <img src="images/antigo-mark.png?v=2.0" alt="Antigo Logo" class="logo-mark">
      <div class="logo-text">
        <div class="word">ANTIGO</div>
        <div class="sub">UI/UX ADVISORY</div>
      </div>
    </a>
    <ul class="nav-links">
      <!-- Original functional links commented out so clicking does not navigate, preserving hover effects -->

      <li><a href="#home">Home</a></li>
      <li><a href="#about">About</a></li>
      <li><a href="#services">Services</a></li>
      <li><a href="#skills">Skills</a></li>
      <li><a href="#contact">Contact</a></li>
    </ul>
    <div class="nav-right">
      <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">
        <svg class="moon" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1111.2 3 7 7 0 0021 12.8z"/></svg>
        <svg class="sun" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
      </button>
      <a href="03-inquiry.html" class="btn btn-primary btn-sm">Contact Me</a>
      <button class="burger" aria-label="Menu"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
    </div>
  </nav>
</header>

<!-- HERO -->
<section class="hero" id="home">
  <div class="hero-bg"><div class="blob b1"></div><div class="blob b2"></div></div>
  <div class="wrap hero-grid">
    <div class="hero-copy">
      <span class="eyebrow">UI / UX Advisory · Portfolio</span>
      <h1>Designing digital<br>experiences that <span class="accent">people love.</span></h1>
      <p class="lead">Helping brands create intuitive, engaging, and user-centered interfaces through thoughtful design and modern digital solutions.</p>
      <div class="hero-actions">
        <a href="#portfolio" class="btn btn-primary">View Services</a>
        <a href="02-book-consultation.html" class="btn btn-outline">Book a Consultation</a>
      </div>
      <div class="trust-row">
        <div class="stat"><b>20+</b><span>Projects designed</span></div>
        <div class="stat"><b>95%</b><span>Client satisfaction</span></div>
        <div class="stat"><b>4</b><span>Years learning &amp; building</span></div>
      </div>
    </div>
    <div class="hero-visual">
      <div class="laptop">
        <div class="laptop-bar"><span></span><span></span><span></span></div>
        <div class="laptop-screen">
          <div class="dash-top">
            <span class="dash-title">Product Dashboard</span>
            <span class="dash-pill">Live</span>
          </div>
          <div class="dash-cards">
            <div class="dash-card"><div class="n">98%</div><div class="l">Usability</div></div>
            <div class="dash-card"><div class="n">4.9</div><div class="l">Avg. rating</div></div>
            <div class="dash-card"><div class="n">1.2s</div><div class="l">Load time</div></div>
          </div>
          <div class="dash-chart">
            <i style="height:40%"></i><i style="height:65%"></i><i style="height:50%"></i>
            <i style="height:80%"></i><i style="height:60%"></i><i style="height:90%"></i><i style="height:70%"></i>
          </div>
        </div>
      </div>
      <div class="float-card fc1">
        <div class="fc-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20V10M18 20V4M6 20v-4"/></svg></div>
        <div class="fc-text"><b>User satisfaction</b><span>↑ 12% this quarter</span></div>
      </div>
      <div class="float-card fc2">
        <div class="fc-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg></div>
        <div class="fc-text"><b>Design System</b><span>v2.0 shipped</span></div>
      </div>
    </div>
  </div>
</section>

<div class="personality">
  <div class="marquee">
    <span class="chip">Professional</span><span class="chip">Modern</span><span class="chip">Clean</span>
    <span class="chip">Premium</span><span class="chip">Minimal</span><span class="chip">Creative</span>
    <span class="chip">Confident</span><span class="chip">Innovative</span><span class="chip">User-Centered</span>
    <span class="chip">Trustworthy</span>
    <span class="chip">Professional</span><span class="chip">Modern</span><span class="chip">Clean</span>
    <span class="chip">Premium</span><span class="chip">Minimal</span><span class="chip">Creative</span>
    <span class="chip">Confident</span><span class="chip">Innovative</span><span class="chip">User-Centered</span>
    <span class="chip">Trustworthy</span>
  </div>
</div>

<!-- ABOUT -->
<section class="pad" id="about">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">About Me</span>
      <h2>The person behind Antigo Advisory</h2>
    </div>
    <div class="about-grid">
      <div class="profile-card">
        <div class="avatar-wrap">
          <img src="images/profile.png" alt="Kimberly Jayne Antigo" class="avatar">
        </div>
        <h3>Kimberly Jayne Antigo</h3>
        <div class="role">Founder &amp; UI/UX Consultant</div>
        <div class="profile-meta">
          <div class="row"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 1.5 3 3 6 3s6-1.5 6-3v-5"/></svg>BS Information Technology Student</div>
          <div class="row"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 6-9 12-9 12s-9-6-9-12a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>Dumaguete City, Philippines</div>
          <div class="row"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 6l-10 7L2 6"/></svg>antigokimberlyjayne@gmail.com</div>
        </div>
        <a href="#contact" class="btn btn-primary" style="width:100%;justify-content:center;">Let's Talk</a>
      </div>
      <div class="about-copy">
        <p>I'm a UI/UX designer and IT student based in Dumaguete City, focused on turning fuzzy problems into interfaces that feel obvious to use. My work sits at the intersection of research, visual design, and front-end craft — I like knowing not just how a screen should look, but why.</p>
        <p>Antigo UI/UX Advisory grew out of that curiosity: a small practice built on research-driven design and thoughtful problem solving, helping brands create digital products that are simple, functional, and memorable. Whether it's a mobile app, a dashboard, or a full design system, I approach every project the same way — understand the user first, then design the solution.</p>
        <div class="skill-chip-row">
          <span class="skill-chip">UI Design</span>
          <span class="skill-chip">UX Research</span>
          <span class="skill-chip">Wireframing</span>
          <span class="skill-chip">Prototyping</span>
          <span class="skill-chip">Design Systems</span>
          <span class="skill-chip">Web Design</span>
          <span class="skill-chip">Graphic Design</span>
          <span class="skill-chip">Illustrator</span>
          <span class="skill-chip">Photoshop</span>
          <span class="skill-chip">HTML / CSS</span>
          <span class="skill-chip">PHP / MySQL</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- SERVICES -->
<section class="pad" id="services" style="background:var(--bg-alt);">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">What I Offer</span>
      <h2>Services built around your product</h2>
      <p>From early research to shippable UI, here's how I help teams design with confidence.</p>
    </div>
    <div class="services-grid">
      <div class="service-card">
        <div class="service-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg></div>
        <h3>UI Design</h3>
        <p>Clean, on-brand interfaces designed pixel by pixel, built to hold up across every screen size.</p>
      </div>
      <div class="service-card">
        <div class="service-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg></div>
        <h3>UX Research</h3>
        <p>Interviews, surveys, and usability testing that ground every design decision in real evidence.</p>
      </div>
      <div class="service-card">
        <div class="service-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h10M4 18h16"/></svg></div>
        <h3>Wireframing</h3>
        <p>Low-fidelity structure and flows that map out a product's logic before a single pixel is styled.</p>
      </div>
      <div class="service-card">
        <div class="service-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z"/></svg></div>
        <h3>Interactive Prototyping</h3>
        <p>Click-through prototypes that let stakeholders and users feel a product before it's built.</p>
      </div>
      <div class="service-card">
        <div class="service-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="14" rx="2"/><path d="M8 21h8M12 18v3"/></svg></div>
        <h3>Responsive Web Design</h3>
        <p>Layouts that adapt gracefully from a 27" monitor down to a phone in one hand.</p>
      </div>
      <div class="service-card">
        <div class="service-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M3 12h18"/></svg></div>
        <h3>Design Systems</h3>
        <p>Reusable components and clear documentation that keep teams shipping consistent product.</p>
      </div>
    </div>
  </div>
</section>

<!-- PORTFOLIO -->
<!-- <section class="pad" id="portfolio">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">Selected Work</span>
      <h2>Featured projects</h2>
      <p>A mix of product, mobile, and web design work — case studies available on request.</p>
    </div>
    <div class="portfolio-tabs">
      <button class="tab active">All</button>
      <button class="tab">UI Design</button>
      <button class="tab">UX Research</button>
      <button class="tab">Web Design</button>
    </div>
    <div class="portfolio-grid">
      <article class="project-card">
        <div class="project-thumb"><div class="g" style="background:linear-gradient(135deg,#13224B,#6C5BB5)"></div><div class="device"><div class="device-frame"></div></div><div class="overlay"><a href="#" class="view-case">View Case Study →</a></div></div>
        <div class="project-body"><span class="project-cat">Fintech · Mobile App</span><h3>Pesolink Banking Redesign</h3></div>
      </article>
      <article class="project-card">
        <div class="project-thumb"><div class="g" style="background:linear-gradient(135deg,#4C6CCB,#6C5BB5)"></div><div class="device"><div class="device-frame"></div></div><div class="overlay"><a href="#" class="view-case">View Case Study →</a></div></div>
        <div class="project-body"><span class="project-cat">Healthcare · Web Platform</span><h3>Clinic Booking System</h3></div>
      </article>
      <article class="project-card">
        <div class="project-thumb"><div class="g" style="background:linear-gradient(135deg,#13224B,#4C6CCB)"></div><div class="device"><div class="device-frame"></div></div><div class="overlay"><a href="#" class="view-case">View Case Study →</a></div></div>
        <div class="project-body"><span class="project-cat">Retail · Design System</span><h3>Kape Roasters Storefront</h3></div>
      </article>
      <article class="project-card">
        <div class="project-thumb"><div class="g" style="background:linear-gradient(135deg,#6C5BB5,#13224B)"></div><div class="device"><div class="device-frame"></div></div><div class="overlay"><a href="#" class="view-case">View Case Study →</a></div></div>
        <div class="project-body"><span class="project-cat">Education · UX Research</span><h3>Campus Advising Portal</h3></div>
      </article>
      <article class="project-card">
        <div class="project-thumb"><div class="g" style="background:linear-gradient(135deg,#4C6CCB,#13224B)"></div><div class="device"><div class="device-frame"></div></div><div class="overlay"><a href="#" class="view-case">View Case Study →</a></div></div>
        <div class="project-body"><span class="project-cat">Travel · Mobile App</span><h3>Isla Island-Hopper App</h3></div>
      </article>
      <article class="project-card">
        <div class="project-thumb"><div class="g" style="background:linear-gradient(135deg,#13224B,#6C5BB5,#4C6CCB)"></div><div class="device"><div class="device-frame"></div></div><div class="overlay"><a href="#" class="view-case">View Case Study →</a></div></div>
        <div class="project-body"><span class="project-cat">SaaS · Design System</span><h3>Northline Analytics Suite</h3></div>
      </article>
    </div>
  </div>
</section> -->

<!-- SKILLS -->
<section class="pad" id="skills" style="background:var(--bg-alt);">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">Skills &amp; Tools</span>
      <h2>What I work with day to day</h2>
    </div>
    <div class="skills-grid">
      <div>
        <div class="skill-col-title">Design</div>
        <div class="skill-bar" style="--w:95%"><div class="top"><span>UI Design</span><span class="pct">95%</span></div><div class="track"><div class="fill"></div></div></div>
        <div class="skill-bar" style="--w:90%"><div class="top"><span>Adobe Illustrator</span><span class="pct">90%</span></div><div class="track"><div class="fill"></div></div></div>
        <div class="skill-bar" style="--w:88%"><div class="top"><span>Adobe Photoshop</span><span class="pct">88%</span></div><div class="track"><div class="fill"></div></div></div>
      </div>
      <div>
        <div class="skill-col-title">Development</div>
        <div class="skill-bar" style="--w:85%"><div class="top"><span>HTML</span><span class="pct">85%</span></div><div class="track"><div class="fill"></div></div></div>
        <div class="skill-bar" style="--w:82%"><div class="top"><span>CSS</span><span class="pct">82%</span></div><div class="track"><div class="fill"></div></div></div>
        <div class="skill-bar" style="--w:80%"><div class="top"><span>PHP</span><span class="pct">80%</span></div><div class="track"><div class="fill"></div></div></div>
        <div class="skill-bar" style="--w:78%"><div class="top"><span>MySQL</span><span class="pct">78%</span></div><div class="track"><div class="fill"></div></div></div>
      </div>
    </div>
  </div>
</section>

<!-- TESTIMONIALS
<section class="pad">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">Testimonials</span>
      <h2>What clients say</h2>
    </div>
    <div class="testi-grid">
      <div class="testi-card">
        <div class="stars">★★★★★</div>
        <p class="quote">Kimberly took a messy internal tool and turned it into something our whole team actually enjoys using. Communication was clear the entire way through.</p>
        <div class="testi-who"><div class="testi-avatar">MS</div><div><b>Maria Santos</b><span>Product Lead, Pesolink</span></div></div>
      </div>
      <div class="testi-card">
        <div class="stars">★★★★★</div>
        <p class="quote">Thoughtful, detail-oriented, and genuinely curious about our users. The research phase alone gave us insights we'd been missing for years.</p>
        <div class="testi-who"><div class="testi-avatar">JD</div><div><b>Jared Dela Cruz</b><span>Founder, Kape Roasters</span></div></div>
      </div>
      <div class="testi-card">
        <div class="stars">★★★★★</div>
        <p class="quote">Our booking flow finally makes sense. Patients stopped calling to ask "how do I reschedule" — that alone paid for the project.</p>
        <div class="testi-who"><div class="testi-avatar">AR</div><div><b>Anna Reyes</b><span>Clinic Administrator</span></div></div>
      </div>
    </div>
  </div>
</section> -->

<!-- CONTACT -->
<section class="pad" id="contact" style="background:var(--bg-alt);">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">Get In Touch</span>
      <h2>Let's build something intuitive</h2>
      <p>Have a project in mind? Send a few details and I'll follow up within one business day.</p>
    </div>
    <div class="contact-grid">
      <form class="contact-form" onsubmit="event.preventDefault();">
        <div class="form-row">
          <div class="field"><label for="name">Name</label><input id="name" type="text" placeholder="Your full name"></div>
          <div class="field"><label for="email">Email</label><input id="email" type="email" placeholder="you@example.com"></div>
        </div>
        <div class="field full" style="margin-bottom:18px;"><label for="subject">Subject</label><input id="subject" type="text" placeholder="What's this about?"></div>
        <div class="field full" style="margin-bottom:24px;"><label for="message">Message</label><textarea id="message" placeholder="Tell me a bit about your project..."></textarea></div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Send Message</button>
      </form>
      <div class="contact-side">
        <div class="info-card">
          <div class="info-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 6-9 12-9 12s-9-6-9-12a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
          <div><b>Location</b><span>Dumaguete City, Philippines</span></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 6l-10 7L2 6"/></svg></div>
          <div><b>Email</b><span>antigokimberlyjayne@gmail.com</span></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1.9.3 1.8.6 2.7a2 2 0 01-.4 2.1L8 9.9a16 16 0 006 6l1.4-1.4a2 2 0 012.1-.4c.9.3 1.8.5 2.7.6a2 2 0 011.8 2z"/></svg></div>
          <div><b>Phone</b><span>+63 968 329 5856</span></div>
        </div>
        <div class="info-card" style="flex-direction:column;align-items:flex-start;">
          <b style="margin-bottom:4px;">Follow along</b>
          <div class="social-row">
            <a href="#" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg></a>
            <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/></svg></a>
            <a href="#" aria-label="GitHub"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-4.3 1.4-4.3-2.5-6-3m12 5v-3.5c0-1 .1-1.4-.5-2 2.8-.3 5.5-1.4 5.5-6a4.6 4.6 0 00-1.3-3.2 4.2 4.2 0 00-.1-3.2s-1.1-.3-3.5 1.3a12.3 12.3 0 00-6.2 0C6.5 2.8 5.4 3.1 5.4 3.1a4.2 4.2 0 00-.1 3.2A4.6 4.6 0 004 9.5c0 4.6 2.7 5.7 5.5 6-.6.6-.6 1.2-.5 2V21"/></svg></a>
            <a href="#" aria-label="LinkedIn"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/><path d="M10 9h4v2c1-1.5 2.5-2.3 4.5-2.3 3 0 5.5 2 5.5 6.3V21h-4v-5.5c0-2-1-3-2.5-3S13 13.5 13 15.5V21h-3z"/></svg></a>
            <a href="#" aria-label="Behance"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h6a3 3 0 010 6H3zM3 12h6.5a3.2 3.2 0 010 6.4H3z"/><path d="M15 15a4 4 0 008 0c0-.3 0-.6-.1-.9H15a4 4 0 004 4.9M15.5 10.5h6.9"/></svg></a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
          
<footer>
  <div class="wrap">
    <div class="footer-grid">
      <div class="footer-brand">
        <a href="#home" class="logo">
          <div class="footer-logo-badge">
            <img src="images/antigo-mark.png?v=2.0" alt="Antigo Logo">
          </div>
          <div class="logo-text">
            <div class="word" style="color:#fff;">ANTIGO</div>
            <div class="sub" style="color:#fff;">UI/UX ADVISORY</div>
          </div>
        </a>
        <p>Designing Experiences. Driving Impact. A UI/UX advisory practice creating intuitive digital experiences that bridge user needs with business goals.</p>
      </div>
      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul>
      <li><a href="#home">Home</a></li>
      <li><a href="#about">About</a></li>
      <li><a href="#services">Services</a></li>
      <li><a href="#skills">Skills</a></li>
      <li><a href="#contact">Contact</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Contact</h4>
        <ul>
          <li><a href="#">antigokimberlyjayne@gmail.com</a></li>
          <li><a href="#">+63 968 329 5856</a></li>
          <li><a href="#">Dumaguete City, Philippines</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2026 Antigo UI/UX Advisory. All rights reserved.</p>
      <div class="footer-socials">
        <a href="#" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg></a>
        <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/></svg></a>
        <a href="#" aria-label="GitHub"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-4.3 1.4-4.3-2.5-6-3m12 5v-3.5c0-1 .1-1.4-.5-2 2.8-.3 5.5-1.4 5.5-6a4.6 4.6 0 00-1.3-3.2 4.2 4.2 0 00-.1-3.2s-1.1-.3-3.5 1.3a12.3 12.3 0 00-6.2 0C6.5 2.8 5.4 3.1 5.4 3.1a4.2 4.2 0 00-.1 3.2A4.6 4.6 0 004 9.5c0 4.6 2.7 5.7 5.5 6-.6.6-.6 1.2-.5 2V21"/></svg></a>
        <a href="#" aria-label="LinkedIn"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/><path d="M10 9h4v2c1-1.5 2.5-2.3 4.5-2.3 3 0 5.5 2 5.5 6.3V21h-4v-5.5c0-2-1-3-2.5-3S13 13.5 13 15.5V21h-3z"/></svg></a>
        <a href="#" aria-label="Behance"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h6a3 3 0 010 6H3zM3 12h6.5a3.2 3.2 0 010 6.4H3z"/><path d="M15 15a4 4 0 008 0c0-.3 0-.6-.1-.9H15a4 4 0 004 4.9M15.5 10.5h6.9"/></svg></a>
      </div>
    </div>
  </div>
</footer>

<script>
  // theme toggle (in-memory only, no storage APIs)
  const toggle = document.getElementById('themeToggle');
  toggle.addEventListener('click', () => {
    const root = document.documentElement;
    root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
  });

  // reveal skill bars when scrolled into view
  const bars = document.querySelectorAll('.skill-bar');
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('in-view'); });
  }, { threshold: .4 });
  bars.forEach(b => io.observe(b));

  // portfolio tab visual state (static demo, no real filtering logic needed for mockup)
  document.querySelectorAll('.tab').forEach(t => {
    t.addEventListener('click', () => {
      document.querySelectorAll('.tab').forEach(x => x.classList.remove('active'));
      t.classList.add('active');
    });
  });
</script>

</body>
</html>