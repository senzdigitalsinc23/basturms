
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            scroll-behavior: smooth;
        }
        .content-area {
            display: block;
            margin-top: 55px;
        }
        .hero {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), 
                        url('https://images.unsplash.com/photo-1529070538774-1843cb3265df') center/cover no-repeat;
            color: white;
            padding: 120px 0;
            text-align: center;
        }
        .feature-icon {
            font-size: 3rem;
            color: #0d6efd;
            transition: transform 0.3s ease;
        }
        .feature-icon:hover {
            transform: scale(1.2);
        }
        .cta {
            background: linear-gradient(135deg, #0d6efd, #007bff);
            color: white;
            padding: 50px 0;
        }
        
        .nav-link {
            position: relative;
        }
        .nav-link::after {
            content: "";
            position: absolute;
            width: 0;
            height: 2px;
            left: 0;
            bottom: -4px;
            background: #0d6efd;
            transition: width 0.3s ease;
        }
        .nav-link:hover::after {
            width: 100%;
        }
        .counter {
            font-size: 2rem;
            font-weight: bold;
            color: #0d6efd;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">SchoolMS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#stats">Stats</a></li>
                    <li class="nav-item"><a class="nav-link" href="#testimonials">Testimonials</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                    <li class="nav-item"><a class="btn btn-primary ms-2" href="/web/login">Login</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" data-aos="fade-up" data-aos-duration="1000">
        <div class="container">
            <h1 class="display-4 fw-bold" data-aos="fade-down" data-aos-delay="300">Effortless School Management</h1>
            <p class="lead" data-aos="fade-up" data-aos-delay="500">Your all-in-one platform to manage students, staff, classes, and more.</p>
            <a href="register.php" class="btn btn-primary btn-lg" data-aos="zoom-in" data-aos-delay="700">Get Started</a>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container text-center">
            <h2 class="fw-bold mb-4" data-aos="fade-up">Why Choose SchoolMS?</h2>
            <div class="row g-4">
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="200">
                    <div class="feature-icon mb-3">📚</div>
                    <h5>Student Management</h5>
                    <p>Track student data, attendance, and performance in one place.</p>
                </div>
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="400">
                    <div class="feature-icon mb-3">👩‍🏫</div>
                    <h5>Teacher Portal</h5>
                    <p>Empower teachers with tools for grading, scheduling, and communication.</p>
                </div>
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="600">
                    <div class="feature-icon mb-3">💳</div>
                    <h5>Fees & Payments</h5>
                    <p>Automate fee collection and payment tracking with ease.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6" data-aos="fade-right">
                    <img src="https://images.unsplash.com/photo-1596495577886-d920f1fb7238" class="img-fluid rounded shadow" alt="School">
                </div>
                <div class="col-md-6" data-aos="fade-left">
                    <h2 class="fw-bold">About SchoolMS</h2>
                    <p>SchoolMS is designed to simplify school operations, improve communication, and give administrators a complete view of the institution’s activities. From attendance to performance reports, everything is just a click away.</p>
                    <a href="#contact" class="btn btn-primary">Contact Us</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section id="stats" class="py-5 text-center bg-light">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="counter" data-count="120">0</div>
                    <p>Schools Served</p>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="400">
                    <div class="counter" data-count="15000">0</div>
                    <p>Students Enrolled</p>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="600">
                    <div class="counter" data-count="2500">0</div>
                    <p>Teachers Onboard</p>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="800">
                    <div class="counter" data-count="99">0</div>
                    <p>% Satisfaction</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="py-5">
        <div class="container text-center">
            <h2 class="fw-bold mb-4" data-aos="fade-up">What Our Clients Say</h2>
            <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active" data-aos="zoom-in">
                        <blockquote class="blockquote">
                            <p class="mb-4">"SchoolMS has completely transformed the way we manage our school. The automation saves us so much time!"</p>
                            <footer class="blockquote-footer">Mr. John Smith, Principal</footer>
                        </blockquote>
                    </div>
                    <div class="carousel-item" data-aos="zoom-in">
                        <blockquote class="blockquote">
                            <p class="mb-4">"The teacher portal is a game changer. I can manage grades and communicate with parents easily."</p>
                            <footer class="blockquote-footer">Mrs. Sarah Johnson, Teacher</footer>
                        </blockquote>
                    </div>
                    <div class="carousel-item" data-aos="zoom-in">
                        <blockquote class="blockquote">
                            <p class="mb-4">"Collecting and tracking fees has never been this smooth. Highly recommended!"</p>
                            <footer class="blockquote-footer">Mr. Michael Brown, Administrator</footer>
                        </blockquote>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta text-center" data-aos="fade-up">
        <div class="container">
            <h2 class="fw-bold">Ready to Transform Your School?</h2>
            <p>Join hundreds of institutions using SchoolMS for smarter management.</p>
            <a href="register.php" class="btn btn-light btn-lg">Get Started Now</a>
        </div>
    </section>

    <!-- Footer -->
    <!-- <footer>
        <div class="container">
            <p>© 2025 SchoolMS. All rights reserved.</p>
        </div>
    </footer> -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();

        // Animated Counter
        document.addEventListener("DOMContentLoaded", () => {
            const counters = document.querySelectorAll('.counter');
            counters.forEach(counter => {
                const updateCount = () => {
                    const target = +counter.getAttribute('data-count');
                    const count = +counter.innerText;
                    const increment = target / 200; // speed

                    if (count < target) {
                        counter.innerText = Math.ceil(count + increment);
                        setTimeout(updateCount, 10);
                    } else {
                        counter.innerText = target;
                    }
                };
                updateCount();
            });
        });
    </script>
