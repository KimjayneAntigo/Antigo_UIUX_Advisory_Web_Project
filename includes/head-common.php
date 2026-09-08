<?php

$pageTitle = $pageTitle ?? 'Antigo UI/UX Advisory';
?>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> — Antigo</title>

<!-- Google Fonts: Poppins -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link
  href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
  rel="stylesheet"
/>

<!-- Iconify -->
<script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js" defer></script>

<!-- Tailwind CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          navy:       '#13224B',
          violet:     '#6C5BB5',
          blue:       '#4C6CCB',
          'light-blue': '#DDEBFF',
          'light-gray': '#F4F6F8',
          'dark-gray':  '#4b4b4b',
        },
        fontFamily: {
          poppins: ['Poppins', 'sans-serif'],
        },
      },
    },
  };
</script>

<style>
  /* Brand CSS custom properties  */
  :root {
    --navy:       #13224B;
    --violet:     #6C5BB5;
    --blue:       #4C6CCB;
    --white:      #FFFFFF;
    --light-blue: #DDEBFF;
    --light-gray: #F4F6F8;
    --dark-gray:  #4b4b4b;
  }

  /* Base body */
  body {
    font-family: 'Poppins', sans-serif;
    background-color: var(--light-gray);
    color: var(--dark-gray);
    margin: 0;
  }

  /* Buttons */
  .btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.5rem 1.25rem;
    background-color: var(--navy);
    color: var(--white);
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.875rem;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.2s, opacity 0.2s;
  }
  .btn-primary:hover  { background-color: #1c3267; }
  .btn-primary:active { opacity: 0.85; }

  .btn-outline {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.5rem 1.25rem;
    background-color: transparent;
    color: var(--navy);
    border: 1.5px solid var(--navy);
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.2s, color 0.2s;
  }
  .btn-outline:hover { background-color: var(--light-blue); }

  /* Card */
  .card {
    background: var(--white);
    border-radius: 0.75rem;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
    padding: 1.5rem;
  }

  /* Input field */
  .input-field {
    width: 100%;
    padding: 0.5rem 0.875rem;
    border: 1.5px solid #d1d5db;
    border-radius: 0.5rem;
    font-family: 'Poppins', sans-serif;
    font-size: 0.875rem;
    color: var(--dark-gray);
    background: var(--white);
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .input-field:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px rgba(76, 108, 203, 0.15);
  }

  /* Sidebar */
  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 240px;
    min-height: 100vh;
    background-color: var(--navy);
    display: flex;
    flex-direction: column;
    z-index: 50;
    overflow-y: auto;
  }

  /*Sidebar links */
  .sidebar-link {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.625rem 1rem;
    border-radius: 0.5rem;
    color: rgba(255, 255, 255, 0.65);
    font-size: 0.875rem;
    text-decoration: none;
    transition: background 0.15s, color 0.15s;
  }
  .sidebar-link:hover  { background: rgba(255, 255, 255, 0.08); color: #fff; }
  .sidebar-link.active { background: rgba(255, 255, 255, 0.12); color: #fff; font-weight: 600; }

  /* Main content */
  .main-content {
    margin-left: 240px;
    min-height: 100vh;
    padding: 2rem;
  }

  /* Logo */
  .logo-mark {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, var(--violet), var(--blue));
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1rem;
    color: #fff;
    flex-shrink: 0;
  }
  .logo-text-word {
    font-size: 1.1rem;
    font-weight: 700;
    color: #fff;
    line-height: 1.1;
    letter-spacing: 0.01em;
  }
  .logo-text-sub {
    font-size: 0.65rem;
    font-weight: 400;
    color: rgba(255, 255, 255, 0.55);
    letter-spacing: 0.04em;
    text-transform: uppercase;
    line-height: 1;
  }

  /* Responsive: hide sidebar on mobile */
  @media (max-width: 768px) {
    .sidebar      { display: none; }
    .main-content { margin-left: 0; padding: 1rem; }
  }
</style>
