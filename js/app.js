/**
 * AgriVisionPro Main Application
 * A comprehensive agricultural guidance system for Sri Lanka
 */

class AgriVisionPro {
    constructor() {
        this.currentSection = 'home';
        this.currentCrop = 'rice';
        this.currentLivestock = 'cattle';
        this.chartUtils = new ChartUtils();
        this.cropData = AgriData.getCropData();
        this.livestockData = AgriData.getLivestockData();
        this.marketData = AgriData.getMarketData();
        this.resourceData = AgriData.getResourceData();
        this.init();
    }

    init() {
        this.loadExternalModules().then(() => {
            this.createHTML();
            this.setupEventListeners();
            this.initializeCharts();
            this.setupCustomCursor();
            this.setupSmoothScrolling();
        });
    }

    async loadExternalModules() {
        // Load data and chart modules if not already loaded
        if (typeof AgriData === 'undefined') {
            await this.loadScript('js/data.js');
        }
        if (typeof ChartUtils === 'undefined') {
            await this.loadScript('js/charts.js');
        }
    }

    loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    createHTML() {
        document.body.innerHTML = `
            ${this.createStyles()}
            ${this.createNavigation()}
            ${this.createHeroSection()}
            ${this.createStatsSection()}
            ${this.createCropsSection()}
            ${this.createLivestockSection()}
            ${this.createMarketSection()}
            ${this.createResourcesSection()}
            ${this.createFooter()}
        `;
    }

    createStyles() {
        return `
            <style>
                :root {
                    --primary: #3854a1;
                    --primary-light: #68aad3;
                    --primary-dark: #273067;
                    --secondary: #dd6b20;
                    --secondary-light: #ed8936;
                    --accent: #4299e1;
                    --gray: #4a5568;
                    --light-gray: #edf2f7;
                }

                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                html {
                    scroll-padding-top: 2rem;
                    scroll-behavior: smooth;
                }

                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: var(--gray);
                    background-color: var(--light-gray);
                    cursor: none;
                }

                .main-cursor {
                    position: fixed;
                    width: 8px;
                    height: 8px;
                    background-color: #10c8f6;
                    border-radius: 50%;
                    pointer-events: none;
                    z-index: 10000;
                    transform: translate(-50%, -50%);
                }

                .mouse-trail {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 18px;
                    height: 18px;
                    background-color: #1074f6;
                    border: 4px solid #4570fc;
                    border-radius: 50%;
                    pointer-events: none;
                    z-index: 9999;
                    animation: fadeOutTrail 0.5s linear forwards;
                    transform-origin: center;
                }

                @keyframes fadeOutTrail {
                    0% { opacity: 1; transform: scale(1); }
                    100% { opacity: 0; transform: scale(0.2); }
                }

                .click-effect {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 0;
                    height: 0;
                    background-color: #dcfaea;
                    border: 6px solid #0a9cfd;
                    border-radius: 50%;
                    pointer-events: none;
                    z-index: 9999;
                    animation: expandRipple 1s ease-out forwards;
                    transform: translate(-50%, -50%);
                }

                @keyframes expandRipple {
                    0% { width: 0; height: 0; opacity: 0.8; }
                    100% { width: 100px; height: 100px; opacity: 0; }
                }

                .nav {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    background: linear-gradient(to bottom, #bfdbfe, #dbeafe);
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    z-index: 1000;
                    padding: 1rem 0;
                }

                .nav-container {
                    max-width: 1200px;
                    margin: 0 auto;
                    padding: 0 2rem;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .nav-brand {
                    display: flex;
                    align-items: center;
                    font-size: 1.5rem;
                    font-weight: bold;
                    color: #1e40af;
                    text-decoration: none;
                }

                .nav-brand img {
                    width: 48px;
                    height: 48px;
                    margin-right: 0.5rem;
                }

                .nav-menu {
                    display: flex;
                    list-style: none;
                    gap: 2rem;
                }

                .nav-link {
                    color: #1e40af;
                    text-decoration: none;
                    font-weight: 500;
                    padding: 0.5rem 1rem;
                    border-radius: 0.5rem;
                    transition: all 0.3s ease;
                    position: relative;
                }

                .nav-link:hover {
                    background-color: rgba(59, 130, 246, 0.1);
                }

                .nav-link.active {
                    background-color: var(--primary);
                    color: white;
                }

                .mobile-menu-btn {
                    display: none;
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    color: #1e40af;
                    cursor: pointer;
                }

                .hero {
                    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
                    color: white;
                    padding: 8rem 2rem 4rem;
                    text-align: center;
                }

                .hero-content {
                    max-width: 1200px;
                    margin: 0 auto;
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 3rem;
                    align-items: center;
                }

                .hero-text h1 {
                    font-size: 3rem;
                    margin-bottom: 1rem;
                    line-height: 1.2;
                }

                .hero-text p {
                    font-size: 1.2rem;
                    margin-bottom: 2rem;
                    opacity: 0.9;
                }

                .hero-buttons {
                    display: flex;
                    gap: 1rem;
                    flex-wrap: wrap;
                    justify-content: center;
                }

                .btn {
                    padding: 0.75rem 2rem;
                    border-radius: 0.5rem;
                    font-weight: bold;
                    text-decoration: none;
                    transition: all 0.3s ease;
                    display: inline-flex;
                    align-items: center;
                    gap: 0.5rem;
                    cursor: pointer;
                    border: none;
                }

                .btn-primary {
                    background-color: white;
                    color: #1e40af;
                }

                .btn-primary:hover {
                    background-color: #f3f4f6;
                    transform: translateY(-2px);
                }

                .btn-secondary {
                    background-color: #1e3a8a;
                    color: white;
                }

                .btn-secondary:hover {
                    background-color: #1e40af;
                    transform: translateY(-2px);
                }

                .farm-visual {
                    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
                    border-radius: 1rem;
                    padding: 3rem;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    text-align: center;
                    min-height: 300px;
                }

                .farm-visual i {
                    font-size: 4rem;
                    margin-bottom: 1rem;
                    opacity: 0.8;
                }

                .section {
                    padding: 4rem 2rem;
                }

                .section-title {
                    text-align: center;
                    margin-bottom: 3rem;
                }

                .section-title h2 {
                    font-size: 2.5rem;
                    color: var(--gray);
                    margin-bottom: 1rem;
                }

                .section-title p {
                    font-size: 1.2rem;
                    color: #6b7280;
                }

                .container {
                    max-width: 1200px;
                    margin: 0 auto;
                }

                .card {
                    background: white;
                    border-radius: 1rem;
                    padding: 2rem;
                    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                    border-top: 4px solid var(--primary);
                    margin-bottom: 2rem;
                }

                .tabs {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 0.5rem;
                    margin-bottom: 2rem;
                    overflow-x: auto;
                    padding-bottom: 0.5rem;
                }

                .tab-btn {
                    padding: 0.75rem 1.5rem;
                    background: white;
                    border: 2px solid #e5e7eb;
                    border-radius: 0.5rem;
                    font-weight: 600;
                    color: var(--gray);
                    cursor: pointer;
                    transition: all 0.3s ease;
                    white-space: nowrap;
                }

                .tab-btn:hover {
                    border-color: var(--primary);
                    color: var(--primary);
                }

                .tab-btn.active {
                    background: var(--primary);
                    color: white;
                    border-color: var(--primary);
                }

                .tab-content {
                    display: none;
                    animation: fadeIn 0.5s ease;
                }

                .tab-content.active {
                    display: block;
                }

                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }

                .accordion {
                    border: 1px solid #e5e7eb;
                    border-radius: 0.5rem;
                    margin-bottom: 0.5rem;
                    overflow: hidden;
                }

                .accordion-btn {
                    width: 100%;
                    padding: 1rem;
                    background: #f9fafb;
                    border: none;
                    text-align: left;
                    font-weight: 600;
                    color: var(--gray);
                    cursor: pointer;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    transition: all 0.3s ease;
                }

                .accordion-btn:hover {
                    background: #f3f4f6;
                    color: var(--primary);
                }

                .accordion-content {
                    max-height: 0;
                    overflow: hidden;
                    transition: max-height 0.3s ease;
                    background: white;
                    padding: 0 1rem;
                }

                .accordion-content.show {
                    max-height: 500px;
                    padding: 1rem;
                }

                .chart-container {
                    height: 300px;
                    background: white;
                    border-radius: 0.5rem;
                    padding: 1rem;
                    margin-top: 1rem;
                }

                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 2rem;
                    margin-top: 2rem;
                }

                .stat-card {
                    background: white;
                    padding: 2rem;
                    border-radius: 0.75rem;
                    text-align: center;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                }

                .stat-icon {
                    font-size: 2.5rem;
                    margin-bottom: 1rem;
                }

                .stat-number {
                    font-size: 2rem;
                    font-weight: bold;
                    margin-bottom: 0.5rem;
                }

                .crop-info {
                    display: grid;
                    grid-template-columns: 1fr 2fr;
                    gap: 2rem;
                    margin-top: 2rem;
                }

                .requirements-list {
                    list-style: none;
                    margin-bottom: 2rem;
                }

                .requirements-list li {
                    display: flex;
                    align-items: center;
                    margin-bottom: 0.5rem;
                    color: var(--gray);
                }

                .requirements-list i {
                    margin-right: 0.5rem;
                    color: var(--primary);
                    width: 20px;
                }

                @media (max-width: 768px) {
                    .nav-menu {
                        display: none;
                    }
                    
                    .mobile-menu-btn {
                        display: block;
                    }
                    
                    .hero-content {
                        grid-template-columns: 1fr;
                        text-align: center;
                    }
                    
                    .hero-text h1 {
                        font-size: 2rem;
                    }
                    
                    .crop-info {
                        grid-template-columns: 1fr;
                    }
                    
                    .hero-buttons {
                        flex-direction: column;
                        align-items: center;
                    }
                }
            </style>
        `;
    }

    createNavigation() {
        return `
            <nav class="nav">
                <div class="nav-container">
                    <a href="#home" class="nav-brand">
                        <img src="images/logo1.png" alt="AgriVisionPro">
                        AgriVisionPro
                    </a>
                    <ul class="nav-menu">
                        <li><a href="#home" class="nav-link active" data-section="home">Home</a></li>
                        <li><a href="#crops" class="nav-link" data-section="crops">Crops</a></li>
                        <li><a href="#livestock" class="nav-link" data-section="livestock">Livestock</a></li>
                        <li><a href="#market" class="nav-link" data-section="market">Market</a></li>
                        <li><a href="#resources" class="nav-link" data-section="resources">Resources</a></li>
                    </ul>
                    <button class="mobile-menu-btn">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </nav>
            <div class="main-cursor"></div>
        `;
    }

    createHeroSection() {
        return `
            <section id="home" class="hero">
                <div class="hero-content">
                    <div class="hero-text">
                        <h1>Smart Farming Solutions for Sri Lanka</h1>
                        <p>Comprehensive guides for crop cultivation and livestock management, tailored for local conditions.</p>
                        <div class="hero-buttons">
                            <a href="#crops" class="btn btn-primary">
                                Explore Crops <i class="fas fa-arrow-right"></i>
                            </a>
                            <a href="#livestock" class="btn btn-secondary">
                                Explore Livestock <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="farm-visual">
                        <i class="fas fa-tractor"></i>
                        <h3>Integrated Farm Management</h3>
                        <p>Visualize your agricultural success</p>
                    </div>
                </div>
            </section>
        `;
    }

    createStatsSection() {
        return `
            <section class="section" style="background: white;">
                <div class="container">
                    <div class="stats-grid">
                        <div class="stat-card" style="background: #dbeafe;">
                            <div class="stat-icon" style="color: #1e40af;">
                                <i class="fas fa-seedling"></i>
                            </div>
                            <div class="stat-number" style="color: #1e40af;">10+</div>
                            <div style="color: var(--gray); font-weight: 600;">Crop Guides</div>
                        </div>
                        <div class="stat-card" style="background: #fef3c7;">
                            <div class="stat-icon" style="color: #d97706;">
                                <i class="fas fa-paw"></i>
                            </div>
                            <div class="stat-number" style="color: #d97706;">7</div>
                            <div style="color: var(--gray); font-weight: 600;">Livestock Types</div>
                        </div>
                        <div class="stat-card" style="background: #dbeafe;">
                            <div class="stat-icon" style="color: #1e40af;">
                                <i class="fas fa-language"></i>
                            </div>
                            <div class="stat-number" style="color: #1e40af;">2</div>
                            <div style="color: var(--gray); font-weight: 600;">Languages</div>
                        </div>
                        <div class="stat-card" style="background: #e9d5ff;">
                            <div class="stat-icon" style="color: #7c3aed;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-number" style="color: #7c3aed;">24/7</div>
                            <div style="color: var(--gray); font-weight: 600;">Access</div>
                        </div>
                    </div>
                </div>
            </section>
        `;
    }

    createCropsSection() {
        return `
            <section id="crops" class="section">
                <div class="container">
                    <div class="section-title">
                        <h2>Crop Cultivation Guides</h2>
                        <p>Detailed guidance for successful crop production in Sri Lanka</p>
                    </div>
                    
                    <div class="tabs">
                        <button class="tab-btn crop-tab active" data-crop="rice">Rice</button>
                        <button class="tab-btn crop-tab" data-crop="maize">Maize</button>
                        <button class="tab-btn crop-tab" data-crop="green-gram">Green Gram</button>
                        <button class="tab-btn crop-tab" data-crop="chili">Chili</button>
                        <button class="tab-btn crop-tab" data-crop="onion">Onion</button>
                        <button class="tab-btn crop-tab" data-crop="tomato">Tomato</button>
                        <button class="tab-btn crop-tab" data-crop="banana">Banana</button>
                        <button class="tab-btn crop-tab" data-crop="mango">Mango</button>
                        <button class="tab-btn crop-tab" data-crop="cabbage">Cabbage</button>
                    </div>
                    
                    <div class="card">
                        <div id="crop-content">
                            ${this.createCropContent('rice')}
                        </div>
                    </div>
                </div>
            </section>
        `;
    }

    createCropContent(cropType) {
        const crop = this.cropData[cropType] || this.cropData.rice;

        return `
            <div class="crop-info">
                <div>
                    <h3 style="font-size: 1.5rem; font-weight: bold; color: var(--primary); margin-bottom: 1rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem;">${crop.title}</h3>
                    <ul class="requirements-list">
                        <li><i class="fas fa-tint"></i><strong>Water:</strong> ${crop.water}</li>
                        <li><i class="fas fa-flask"></i><strong>Fertilizer (N):</strong> ${crop.fertilizer}</li>
                    </ul>
                    
                    <h4 style="font-size: 1.1rem; font-weight: 600; color: var(--primary); margin-bottom: 1rem;">Climate Requirements</h4>
                    <div style="background: #f0f9ff; padding: 1rem; border-radius: 0.5rem; border: 1px solid #bae6fd; margin-bottom: 2rem;">
                        <p style="color: var(--gray);">${crop.climate}</p>
                    </div>
                    
                    <div class="chart-container">
                        <canvas id="${cropType}TimelineChart"></canvas>
                    </div>
                </div>
                
                <div>
                    <h4 style="font-size: 1.1rem; font-weight: 600; color: var(--primary); margin-bottom: 1rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem;">Cultivation Steps</h4>
                    <div class="steps-container">
                        ${crop.steps.map((step, index) => `
                            <div class="accordion">
                                <button class="accordion-btn">
                                    <span>${index + 1}. ${step.title}</span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="accordion-content">
                                    <p style="color: var(--gray);">${step.content}</p>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    
                    <div class="chart-container">
                        <canvas id="${cropType}WaterChart"></canvas>
                    </div>
                </div>
            </div>
        `;
    }

    createLivestockSection() {
        return `
            <section id="livestock" class="section" style="background: white;">
                <div class="container">
                    <div class="section-title">
                        <h2>Livestock Management</h2>
                        <p>Comprehensive guides for successful livestock farming in Sri Lanka</p>
                    </div>
                    
                    <div class="tabs">
                        <button class="tab-btn livestock-tab active" data-livestock="cattle">Cattle</button>
                        <button class="tab-btn livestock-tab" data-livestock="poultry">Poultry</button>
                        <button class="tab-btn livestock-tab" data-livestock="buffaloes">Buffaloes</button>
                        <button class="tab-btn livestock-tab" data-livestock="sheep">Sheep</button>
                        <button class="tab-btn livestock-tab" data-livestock="pigs">Pigs</button>
                        <button class="tab-btn livestock-tab" data-livestock="goats">Goats</button>
                        <button class="tab-btn livestock-tab" data-livestock="other">Other</button>
                    </div>
                    
                    <div class="card">
                        <div id="livestock-content">
                            ${this.createLivestockContent('cattle')}
                        </div>
                    </div>
                </div>
            </section>
        `;
    }

    createLivestockContent(livestockType) {
        const animal = this.livestockData[livestockType] || this.livestockData.cattle;

        return `
            <div class="crop-info">
                <div>
                    <h3 style="font-size: 1.5rem; font-weight: bold; color: var(--primary); margin-bottom: 1rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem;">
                        <i class="${animal.icon}" style="margin-right: 0.5rem; color: var(--secondary);"></i>
                        ${animal.title}
                    </h3>
                    <ul class="requirements-list">
                        ${animal.requirements.map(req => `
                            <li><i class="${req.icon}"></i>${req.text}</li>
                        `).join('')}
                    </ul>
                    
                    <h4 style="font-size: 1.1rem; font-weight: 600; color: var(--primary); margin-bottom: 1rem;">Management Requirements</h4>
                    <div style="background: #f0f9ff; padding: 1rem; border-radius: 0.5rem; border: 1px solid #bae6fd; margin-bottom: 2rem;">
                        <p style="color: var(--gray);">${animal.description}</p>
                    </div>
                    
                    <div class="chart-container">
                        <canvas id="livestock${livestockType}Chart"></canvas>
                    </div>
                </div>
                
                <div>
                    <h4 style="font-size: 1.1rem; font-weight: 600; color: var(--primary); margin-bottom: 1rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem;">Management Aspects</h4>
                    <div class="steps-container">
                        ${animal.steps.map((step, index) => `
                            <div class="accordion">
                                <button class="accordion-btn">
                                    <span>${index + 1}. ${step.title}</span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="accordion-content">
                                    <p style="color: var(--gray);">${step.content}</p>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    }

    createMarketSection() {
        return `
            <section id="market" class="section">
                <div class="container">
                    <div class="section-title">
                        <h2>Market Information</h2>
                        <p>Current prices and trends for agricultural products</p>
                    </div>
                    
                    <div class="card">
                        <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--primary); margin-bottom: 1rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem;">Today's Market Prices</h3>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f9fafb;">
                                        <th style="padding: 0.75rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: var(--gray); text-transform: uppercase;">Product</th>
                                        <th style="padding: 0.75rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: var(--gray); text-transform: uppercase;">Unit</th>
                                        <th style="padding: 0.75rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: var(--gray); text-transform: uppercase;">Min Price (LKR)</th>
                                        <th style="padding: 0.75rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: var(--gray); text-transform: uppercase;">Max Price (LKR)</th>
                                        <th style="padding: 0.75rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: var(--gray); text-transform: uppercase;">Trend (7d)</th>
                                    </tr>
                                </thead>
                                <tbody style="background: white;">
                                    <tr style="border-bottom: 1px solid #e5e7eb;">
                                        <td style="padding: 1rem; color: var(--gray);">Rice (Nadu)</td>
                                        <td style="padding: 1rem; color: var(--gray);">kg</td>
                                        <td style="padding: 1rem; color: var(--gray);">210.00</td>
                                        <td style="padding: 1rem; color: var(--gray);">230.00</td>
                                        <td style="padding: 1rem; color: #059669;"><i class="fas fa-arrow-up"></i> 2.5%</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #e5e7eb;">
                                        <td style="padding: 1rem; color: var(--gray);">Tomatoes</td>
                                        <td style="padding: 1rem; color: var(--gray);">kg</td>
                                        <td style="padding: 1rem; color: var(--gray);">237.29</td>
                                        <td style="padding: 1rem; color: var(--gray);">760.00</td>
                                        <td style="padding: 1rem; color: #dc2626;"><i class="fas fa-arrow-down"></i> 5.2%</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #e5e7eb;">
                                        <td style="padding: 1rem; color: var(--gray);">Chicken (Live)</td>
                                        <td style="padding: 1rem; color: var(--gray);">kg</td>
                                        <td style="padding: 1rem; color: var(--gray);">1215.00</td>
                                        <td style="padding: 1rem; color: var(--gray);">1490.00</td>
                                        <td style="padding: 1rem; color: var(--gray);"><i class="fas fa-minus"></i> 0.3%</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #e5e7eb;">
                                        <td style="padding: 1rem; color: var(--gray);">Eggs</td>
                                        <td style="padding: 1rem; color: var(--gray);">unit</td>
                                        <td style="padding: 1rem; color: var(--gray);">30.00</td>
                                        <td style="padding: 1rem; color: var(--gray);">100.00</td>
                                        <td style="padding: 1rem; color: #059669;"><i class="fas fa-arrow-up"></i> 1.8%</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 1rem; color: var(--gray);">Milk (Fresh)</td>
                                        <td style="padding: 1rem; color: var(--gray);">liter</td>
                                        <td style="padding: 1rem; color: var(--gray);">350.00</td>
                                        <td style="padding: 1rem; color: var(--gray);">600.00</td>
                                        <td style="padding: 1rem; color: #059669;"><i class="fas fa-arrow-up"></i> 1.2%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div style="margin-top: 2rem;">
                            <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--primary); margin-bottom: 1rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem;">Price Trends (Last 30 Days)</h3>
                            <div id="priceTrendChart" class="chart-container" style="height: 400px;"></div>
                        </div>
                    </div>
                </div>
            </section>
        `;
    }

    createResourcesSection() {
        return `
            <section id="resources" class="section" style="background: white;">
                <div class="container">
                    <div class="section-title">
                        <h2>Resources & Support</h2>
                        <p>Government services, financial support, and useful links</p>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                        <div class="card" style="background: #f0f9ff; border-top-color: #1e40af;">
                            <div style="color: #1e40af; margin-bottom: 1rem; font-size: 2.5rem;">
                                <i class="fas fa-landmark"></i>
                            </div>
                            <h3 style="font-size: 1.25rem; font-weight: 600; color: #1e40af; margin-bottom: 1rem;">Government Institutions</h3>
                            <ul style="list-style: none; margin: 0; padding: 0;">
                                <li style="margin-bottom: 0.75rem;">
                                    <a href="#" style="color: var(--gray); text-decoration: none; display: flex; align-items: center;">
                                        <i class="fas fa-chevron-right" style="color: #3b82f6; margin-right: 0.5rem; font-size: 0.75rem;"></i>
                                        Department of Agriculture
                                    </a>
                                </li>
                                <li style="margin-bottom: 0.75rem;">
                                    <a href="#" style="color: var(--gray); text-decoration: none; display: flex; align-items: center;">
                                        <i class="fas fa-chevron-right" style="color: #3b82f6; margin-right: 0.5rem; font-size: 0.75rem;"></i>
                                        Department of Animal Production & Health
                                    </a>
                                </li>
                                <li>
                                    <a href="#" style="color: var(--gray); text-decoration: none; display: flex; align-items: center;">
                                        <i class="fas fa-chevron-right" style="color: #3b82f6; margin-right: 0.5rem; font-size: 0.75rem;"></i>
                                        Agrarian Services
                                    </a>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="card" style="background: #f0f9ff; border-top-color: #1e40af;">
                            <div style="color: #1e40af; margin-bottom: 1rem; font-size: 2.5rem;">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                            <h3 style="font-size: 1.25rem; font-weight: 600; color: #1e40af; margin-bottom: 1rem;">Financial Support</h3>
                            <ul style="list-style: none; margin: 0; padding: 0;">
                                <li style="margin-bottom: 0.75rem;">
                                    <a href="#" style="color: var(--gray); text-decoration: none; display: flex; align-items: center;">
                                        <i class="fas fa-chevron-right" style="color: #3b82f6; margin-right: 0.5rem; font-size: 0.75rem;"></i>
                                        Agricultural Loans
                                    </a>
                                </li>
                                <li style="margin-bottom: 0.75rem;">
                                    <a href="#" style="color: var(--gray); text-decoration: none; display: flex; align-items: center;">
                                        <i class="fas fa-chevron-right" style="color: #3b82f6; margin-right: 0.5rem; font-size: 0.75rem;"></i>
                                        Subsidy Schemes
                                    </a>
                                </li>
                                <li>
                                    <a href="#" style="color: var(--gray); text-decoration: none; display: flex; align-items: center;">
                                        <i class="fas fa-chevron-right" style="color: #3b82f6; margin-right: 0.5rem; font-size: 0.75rem;"></i>
                                        Insurance Programs
                                    </a>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="card" style="background: #faf5ff; border-top-color: #7c3aed;">
                            <div style="color: #7c3aed; margin-bottom: 1rem; font-size: 2.5rem;">
                                <i class="fas fa-book"></i>
                            </div>
                            <h3 style="font-size: 1.25rem; font-weight: 600; color: #7c3aed; margin-bottom: 1rem;">Publications & Guides</h3>
                            <ul style="list-style: none; margin: 0; padding: 0;">
                                <li style="margin-bottom: 0.75rem;">
                                    <a href="#" style="color: var(--gray); text-decoration: none; display: flex; align-items: center;">
                                        <i class="fas fa-chevron-right" style="color: #8b5cf6; margin-right: 0.5rem; font-size: 0.75rem;"></i>
                                        Crop Cultivation Manuals
                                    </a>
                                </li>
                                <li style="margin-bottom: 0.75rem;">
                                    <a href="#" style="color: var(--gray); text-decoration: none; display: flex; align-items: center;">
                                        <i class="fas fa-chevron-right" style="color: #8b5cf6; margin-right: 0.5rem; font-size: 0.75rem;"></i>
                                        Livestock Management Guides
                                    </a>
                                </li>
                                <li>
                                    <a href="#" style="color: var(--gray); text-decoration: none; display: flex; align-items: center;">
                                        <i class="fas fa-chevron-right" style="color: #8b5cf6; margin-right: 0.5rem; font-size: 0.75rem;"></i>
                                        Research Publications
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>
        `;
    }

    createFooter() {
        return `
            <footer style="background: var(--gray); color: white; padding: 2rem; text-align: center;">
                <div class="container">
                    <p>&copy; 2025 AgriVisionPro. All rights reserved.</p>
                    <p style="margin-top: 0.5rem; opacity: 0.8;">Empowering Sri Lankan farmers with modern agricultural knowledge</p>
                </div>
            </footer>
        `;
    }

    setupEventListeners() {
        // Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = link.dataset.section;
                this.navigateToSection(section);
            });
        });

        // Crop tabs
        document.querySelectorAll('.crop-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const crop = tab.dataset.crop;
                this.switchCrop(crop);
            });
        });

        // Livestock tabs
        document.querySelectorAll('.livestock-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const livestock = tab.dataset.livestock;
                this.switchLivestock(livestock);
            });
        });

        // Accordion functionality
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('accordion-btn') || e.target.parentElement.classList.contains('accordion-btn')) {
                const btn = e.target.classList.contains('accordion-btn') ? e.target : e.target.parentElement;
                const content = btn.nextElementSibling;
                const icon = btn.querySelector('i');
                
                content.classList.toggle('show');
                icon.classList.toggle('fa-chevron-down');
                icon.classList.toggle('fa-chevron-up');
            }
        });

        // Smooth scrolling for hero buttons
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(anchor.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    }

    navigateToSection(section) {
        // Update active nav link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        document.querySelector(`[data-section="${section}"]`).classList.add('active');

        // Scroll to section
        const targetElement = document.getElementById(section);
        if (targetElement) {
            targetElement.scrollIntoView({ behavior: 'smooth' });
        }

        this.currentSection = section;
    }

    switchCrop(cropType) {
        // Update active tab
        document.querySelectorAll('.crop-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`[data-crop="${cropType}"]`).classList.add('active');

        // Update content
        document.getElementById('crop-content').innerHTML = this.createCropContent(cropType);
        this.currentCrop = cropType;

        // Reinitialize accordions and charts
        setTimeout(() => {
            this.initializeCropChart(cropType);
        }, 100);
    }

    switchLivestock(livestockType) {
        // Update active tab
        document.querySelectorAll('.livestock-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`[data-livestock="${livestockType}"]`).classList.add('active');

        // Update content
        document.getElementById('livestock-content').innerHTML = this.createLivestockContent(livestockType);
        this.currentLivestock = livestockType;

        // Reinitialize charts
        setTimeout(() => {
            this.initializeLivestockChart(livestockType);
        }, 100);
    }

    setupCustomCursor() {
        const cursor = document.createElement('div');
        cursor.className = 'main-cursor';
        document.body.appendChild(cursor);

        document.addEventListener('mousemove', (e) => {
            cursor.style.left = e.clientX + 'px';
            cursor.style.top = e.clientY + 'px';

            // Create mouse trail
            this.createMouseTrail(e.clientX, e.clientY);
        });

        document.addEventListener('click', (e) => {
            this.createClickEffect(e.clientX, e.clientY);
        });
    }

    createMouseTrail(x, y) {
        const trail = document.createElement('div');
        trail.className = 'mouse-trail';
        trail.style.left = x + 'px';
        trail.style.top = y + 'px';
        document.body.appendChild(trail);

        setTimeout(() => {
            trail.remove();
        }, 500);
    }

    createClickEffect(x, y) {
        const effect = document.createElement('div');
        effect.className = 'click-effect';
        effect.style.left = x + 'px';
        effect.style.top = y + 'px';
        document.body.appendChild(effect);

        setTimeout(() => {
            effect.remove();
        }, 1000);
    }

    setupSmoothScrolling() {
        // Add smooth scrolling behavior
        document.documentElement.style.scrollBehavior = 'smooth';
    }

    initializeCharts() {
        // Initialize default charts
        setTimeout(() => {
            this.initializeCropChart('rice');
            this.initializeLivestockChart('cattle');
            this.initializePriceTrendChart();
        }, 500);
    }

    initializeCropChart(cropType) {
        const cropData = this.cropData[cropType];
        if (!cropData) return;

        // Initialize timeline chart
        this.chartUtils.createCropTimelineChart(
            `${cropType}TimelineChart`, 
            cropData.timelineData
        );

        // Initialize water chart
        this.chartUtils.createCropWaterChart(
            `${cropType}WaterChart`, 
            cropData.waterData
        );
    }

    initializeLivestockChart(livestockType) {
        const livestockData = this.livestockData[livestockType];
        if (!livestockData) return;

        this.chartUtils.createLivestockChart(
            `livestock${livestockType}Chart`, 
            livestockData
        );
    }

    initializePriceTrendChart() {
        this.chartUtils.createPriceTrendChart('priceTrendChart', this.marketData);
    }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AgriVisionPro();
});