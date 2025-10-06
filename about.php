<?php include 'includes/session.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - ShoeARizz</title>
    
    <!-- Preload critical fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --text-color: #333;
            --light-bg: #f8f9fa;
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            overflow-x: hidden;
        }
        
        /* Hero Section */
        .hero-section {
            background: #590016;
            color: white;
            padding: 120px 0 40px;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: white;
            font-family: 'Poppins', sans-serif;
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            font-weight: 300;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
            color: white;
            font-family: 'Poppins', sans-serif;
        }
        
        /* Team Carousel Section */
        .team-section {
            padding: 50px 0;
            background: var(--light-bg);
            position: relative;
        }

        .carousel-container {
            position: relative;
            height: 600px;
            margin: 20px 0;
            overflow: hidden;
            background: var(--light-bg);
            width: 100%;
            max-width: 1800px; /* Width to show exactly 3 images (500px + 60px gap) * 3 */
            margin-left: auto;
            margin-right: auto;
        }

        .carousel-track {
            display: flex;
            position: absolute;
            width: calc(800% + 2400px); /* Width for 40 items - enough for smooth 3-image flow */
            height: 100%;
            animation: infiniteCarousel 50s linear infinite;
            gap: 60px;
            left: -560px; /* Start position to show first 3 images properly */
        }

        .team-member {
            width: 500px;
            height: 550px;
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            transition: transform 0.3s ease;
            flex-shrink: 0;
        }

        .team-member:hover {
            transform: translateY(-10px) scale(1.05);
        }

        .team-member:hover .member-image {
            filter: blur(3px);
            transform: scale(1.1);
        }
        
        .team-member::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shine 4s infinite;
            z-index: 2;
        }
        
        .member-content {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            padding: 0 0 50px 0;
            background: linear-gradient(transparent 0%, transparent 60%, rgba(0, 0, 0, 0.8) 100%);
            z-index: 1;
        }

        .member-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            z-index: -1;
            transition: filter 0.3s ease, transform 0.3s ease;
            border-radius: 20px;
        }

        .member-name {
            font-size: 2.2rem;
            font-weight: 700;
            color: white;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.9);
            margin: 0 0 10px 0;
            z-index: 3;
            text-align: center;
        }

        .member-role {
            font-size: 1.2rem;
            font-weight: 400;
            color: #f0f0f0;
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.8);
            margin: 0;
            z-index: 3;
            text-align: center;
            opacity: 0.9;
        }
        
        @keyframes infiniteCarousel {
            0% { transform: translateX(0); }
            100% { transform: translateX(-12.5%); }
        }
        
        @keyframes shine {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        /* Story Section */
        .story-section {
            padding: 100px 0;
            background: white;
            position: relative;
        }
        
        .story-content {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        
        .story-title {
            font-size: 2.8rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 2rem;
            position: relative;
        }
        
        .story-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--accent-color);
            border-radius: 2px;
        }
        
        .story-text {
            font-size: 1.2rem;
            line-height: 2;
            color: #666;
            background: var(--light-bg);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .story-text::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient);
        }
        
        /* Floating Animation Elements */
        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
        }
        
        .floating-shoe {
            position: absolute;
            font-size: 2rem;
            color: rgba(52, 152, 219, 0.1);
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-shoe:nth-child(1) {
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .floating-shoe:nth-child(2) {
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }
        
        .floating-shoe:nth-child(3) {
            bottom: 30%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
                padding: 0 20px;
            }
            
            .carousel-container {
                height: 450px;
                margin: 20px 0;
            }

            .team-member {
                width: 380px;
                height: 420px;
            }

            .member-name {
                font-size: 1.8rem;
            }

            .member-role {
                font-size: 1rem;
            }
            
            .story-title {
                font-size: 2.2rem;
            }
            
            .story-text {
                font-size: 1rem;
                padding: 30px 20px;
            }
            
            .team-section, .story-section {
                padding: 60px 0;
            }
        }
        
        @media (max-width: 480px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .carousel-container {
                height: 320px;
            }

            .team-member {
                width: 280px;
                height: 300px;
            }

            .member-name {
                font-size: 1.4rem;
            }

            .member-role {
                font-size: 0.9rem;
            }
            
            .story-text {
                padding: 25px 15px;
            }
        }
        
        /* Performance Optimizations */
        .carousel-track {
            will-change: transform;
        }
        
        .member-image {
            will-change: transform;
        }
        
        /* Fade edges effect */
        .carousel-container::before,
        .carousel-container::after {
            content: '';
            position: absolute;
            top: 0;
            width: 120px;
            height: 100%;
            z-index: 10;
            pointer-events: none;
        }

        .carousel-container::before {
            left: 0;
            background: linear-gradient(90deg, var(--light-bg), transparent);
        }

        .carousel-container::after {
            right: 0;
            background: linear-gradient(270deg, var(--light-bg), transparent);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="floating-elements">
            <i class="fas fa-shoe-prints floating-shoe"></i>
            <i class="fas fa-shoe-prints floating-shoe"></i>
            <i class="fas fa-shoe-prints floating-shoe"></i>
        </div>
        <div class="container">
            <div class="hero-content" data-aos="fade-up" data-aos-duration="1000">
                <h1 class="hero-title">Meet the Team</h1>
                <p class="hero-subtitle">The team behind the enterprise of ShoeARizz Company</p>
            </div>
        </div>
    </section>
    
    <!-- Team Section -->
    <section class="team-section">
        <div class="container">
            <div class="carousel-container" data-aos="fade-up" data-aos-duration="1200">
                <div class="carousel-track">
                    <!-- First set: sophia, james, von, jessie, seth (right to left order) -->
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/sophia.webp" alt="Sophia" class="member-image" loading="lazy">
                            <h3 class="member-name">Sophia</h3>
                            <p class="member-role">Frontend Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/james.webp" alt="James" class="member-image" loading="lazy">
                            <h3 class="member-name">James</h3>
                            <p class="member-role">Backend Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/von.webp" alt="Von" class="member-image" loading="lazy">
                            <h3 class="member-name">Von</h3>
                            <p class="member-role">Leader & FullStack Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/jessie.webp" alt="Jessie" class="member-image" loading="lazy">
                            <h3 class="member-name">Jessie</h3>
                            <p class="member-role">UI/UX Designer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/seth.webp" alt="Seth" class="member-image" loading="lazy">
                            <h3 class="member-name">Seth</h3>
                            <p class="member-role">Backend Developer</p>
                        </div>
                    </div>
                    <!-- Duplicate set for seamless infinite loop -->
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/sophia.webp" alt="Sophia" class="member-image" loading="lazy">
                            <h3 class="member-name">Sophia</h3>
                            <p class="member-role">Frontend Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/james.webp" alt="James" class="member-image" loading="lazy">
                            <h3 class="member-name">James</h3>
                            <p class="member-role">Backend Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/von.webp" alt="Von" class="member-image" loading="lazy">
                            <h3 class="member-name">Von</h3>
                            <p class="member-role">Leader & FullStack Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/jessie.webp" alt="Jessie" class="member-image" loading="lazy">
                            <h3 class="member-name">Jessie</h3>
                            <p class="member-role">UI/UX Designer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/seth.webp" alt="Seth" class="member-image" loading="lazy">
                            <h3 class="member-name">Seth</h3>
                            <p class="member-role">Backend Developer</p>
                        </div>
                    </div>
                    <!-- Second set for seamless infinite loop -->
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/sophia.webp" alt="Sophia" class="member-image" loading="lazy">
                            <h3 class="member-name">Sophia</h3>
                            <p class="member-role">Frontend Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/james.webp" alt="James" class="member-image" loading="lazy">
                            <h3 class="member-name">James</h3>
                            <p class="member-role">Backend Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/von.webp" alt="Von" class="member-image" loading="lazy">
                            <h3 class="member-name">Von</h3>
                            <p class="member-role">Leader & FullStack Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/jessie.webp" alt="Jessie" class="member-image" loading="lazy">
                            <h3 class="member-name">Jessie</h3>
                            <p class="member-role">UI/UX Designer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/seth.webp" alt="Seth" class="member-image" loading="lazy">
                            <h3 class="member-name">Seth</h3>
                            <p class="member-role">Backend Developer</p>
                        </div>
                    </div>
                    <!-- Additional sets for ultra-smooth 3-image flow -->
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/sophia.webp" alt="Sophia" class="member-image" loading="lazy">
                            <h3 class="member-name">Sophia</h3>
                            <p class="member-role">Frontend Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/james.webp" alt="James" class="member-image" loading="lazy">
                            <h3 class="member-name">James</h3>
                            <p class="member-role">Backend Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/von.webp" alt="Von" class="member-image" loading="lazy">
                            <h3 class="member-name">Von</h3>
                            <p class="member-role">Leader & FullStack Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/jessie.webp" alt="Jessie" class="member-image" loading="lazy">
                            <h3 class="member-name">Jessie</h3>
                            <p class="member-role">UI/UX Designer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/seth.webp" alt="Seth" class="member-image" loading="lazy">
                            <h3 class="member-name">Seth</h3>
                            <p class="member-role">Backend Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/sophia.webp" alt="Sophia" class="member-image" loading="lazy">
                            <h3 class="member-name">Sophia</h3>
                            <p class="member-role">Frontend Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/james.webp" alt="James" class="member-image" loading="lazy">
                            <h3 class="member-name">James</h3>
                            <p class="member-role">Backend Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/von.webp" alt="Von" class="member-image" loading="lazy">
                            <h3 class="member-name">Von</h3>
                            <p class="member-role">Leader & FullStack Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/jessie.webp" alt="Jessie" class="member-image" loading="lazy">
                            <h3 class="member-name">Jessie</h3>
                            <p class="member-role">UI/UX Designer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/seth.webp" alt="Seth" class="member-image" loading="lazy">
                            <h3 class="member-name">Seth</h3>
                            <p class="member-role">Backend Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/sophia.webp" alt="Sophia" class="member-image" loading="lazy">
                            <h3 class="member-name">Sophia</h3>
                            <p class="member-role">Frontend Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/james.webp" alt="James" class="member-image" loading="lazy">
                            <h3 class="member-name">James</h3>
                            <p class="member-role">Backend Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/von.webp" alt="Von" class="member-image" loading="lazy">
                            <h3 class="member-name">Von</h3>
                            <p class="member-role">Leader & FullStack Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/jessie.webp" alt="Jessie" class="member-image" loading="lazy">
                            <h3 class="member-name">Jessie</h3>
                            <p class="member-role">UI/UX Designer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/seth.webp" alt="Seth" class="member-image" loading="lazy">
                            <h3 class="member-name">Seth</h3>
                            <p class="member-role">Backend Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/sophia.webp" alt="Sophia" class="member-image" loading="lazy">
                            <h3 class="member-name">Sophia</h3>
                            <p class="member-role">Frontend Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/james.webp" alt="James" class="member-image" loading="lazy">
                            <h3 class="member-name">James</h3>
                            <p class="member-role">Backend Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/von.webp" alt="Von" class="member-image" loading="lazy">
                            <h3 class="member-name">Von</h3>
                            <p class="member-role">Leader & FullStack Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/jessie.webp" alt="Jessie" class="member-image" loading="lazy">
                            <h3 class="member-name">Jessie</h3>
                            <p class="member-role">UI/UX Designer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/seth.webp" alt="Seth" class="member-image" loading="lazy">
                            <h3 class="member-name">Seth</h3>
                            <p class="member-role">Backend Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/sophia.webp" alt="Sophia" class="member-image" loading="lazy">
                            <h3 class="member-name">Sophia</h3>
                            <p class="member-role">Frontend Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/james.webp" alt="James" class="member-image" loading="lazy">
                            <h3 class="member-name">James</h3>
                            <p class="member-role">Backend Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/von.webp" alt="Von" class="member-image" loading="lazy">
                            <h3 class="member-name">Von</h3>
                            <p class="member-role">Leader & FullStack Developer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/jessie.webp" alt="Jessie" class="member-image" loading="lazy">
                            <h3 class="member-name">Jessie</h3>
                            <p class="member-role">UI/UX Designer</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-content">
                            <img src="assets/img/team/seth.webp" alt="Seth" class="member-image" loading="lazy">
                            <h3 class="member-name">Seth</h3>
                            <p class="member-role">Backend Developer</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Story Section -->
    <section class="story-section">
        <div class="container">
            <div class="story-content" data-aos="fade-up" data-aos-duration="1000">
                <h2 class="story-title">How ShoeARizz Was Born</h2>
                <div class="story-text" data-aos="fade-up" data-aos-duration="1200" data-aos-delay="200">
                    Shoe-A-Rizz is a footwear company established in 2023 by BSIT students of the Technological Institute of the Philippines. It began in 2017 when the founders crafted and sold stylish yet reasonably priced rubber shoes, which gained popularity for their durability and strong value-for-money appeal. As demand grew, Shoe-A-Rizz expanded its product line to include running shoes, sports shoes, and sneakers.
                </div>
            </div>
        </div>
    </section>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="assets/js/global-cart.js"></script>
    <script src="assets/js/global-favorites.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });
        
        // Performance optimization: Reduce animations on low-end devices
        if (navigator.hardwareConcurrency <= 2) {
            document.querySelectorAll('.floating-shoe').forEach(element => {
                element.style.animation = 'none';
            });
        }
        
        // Intersection Observer for performance
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                }
            });
        }, observerOptions);
        
        // Observe elements for animation
        document.querySelectorAll('.carousel-container, .story-text').forEach(el => {
            observer.observe(el);
        });
        
        // Lazy load optimization
        if ('loading' in HTMLImageElement.prototype) {
            const images = document.querySelectorAll('img[loading="lazy"]');
            images.forEach(img => {
                img.src = img.src;
            });
        }
    </script>
</body>
</html>
