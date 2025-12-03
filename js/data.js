/**
 * Data Module - Contains all the agricultural data for crops and livestock
 */

class AgriData {
    static getCropData() {
        return {
            rice: {
                title: 'Rice Cultivation',
                water: '1200-1800 mm per season',
                fertilizer: '80-150 kg/ha',
                climate: 'Grown extensively across all climate zones (Wet, Intermediate, Dry). Two main cultivation seasons: Maha (Sept/Oct - Mar/Apr) and Yala (Apr/May - Aug/Sept).',
                timelineData: {
                    labels: ['Land Prep', 'Seed Prep', 'Planting', 'Vegetative', 'Flowering', 'Harvest'],
                    data: [7, 2, 3, 45, 30, 7]
                },
                waterData: {
                    labels: ['Land Prep', 'Early Growth', 'Tillering', 'Flowering', 'Maturity', 'Harvest'],
                    data: [5, 2, 5, 10, 5, 0]
                },
                steps: [
                    { title: 'Land Preparation', content: 'Clear weeds, level the field, bund repair. Plow/rotavate to loosen soil (wet or dry tillage).' },
                    { title: 'Seed Preparation', content: 'Select quality seeds, soak for 24 hrs, incubate for 24 hrs.' },
                    { title: 'Sowing/Planting', content: 'Broadcast pre-germinated seeds or transplant 2-3 seedlings per hill (20-25 cm spacing).' },
                    { title: 'Water Management', content: 'Maintain appropriate water levels (2-5 cm early, 5-10 cm flowering), drain before harvesting.' },
                    { title: 'Fertilizing', content: 'Apply basal fertilizers (TSP, MOP) before planting. Apply Urea in splits based on plant growth stages.' },
                    { title: 'Pest & Disease Control', content: 'Monitor for stem borer, leaf folder, blast, brown spot. Use IPM strategies.' },
                    { title: 'Harvesting', content: 'Harvest when 80-85% of grains are golden. Dry paddy to 14% moisture.' }
                ]
            },
            maize: {
                title: 'Maize Cultivation',
                water: '500-800 mm per season',
                fertilizer: '80-120 kg/ha',
                climate: 'Best suited for Dry and Intermediate Zones. Grown in both Maha and Yala seasons. Requires warm conditions, sensitive to waterlogging and frost.',
                timelineData: {
                    labels: ['Land Prep', 'Sowing', 'Vegetative', 'Flowering', 'Grain Filling', 'Harvest'],
                    data: [7, 3, 45, 15, 30, 7]
                },
                waterData: {
                    labels: ['Land Prep', 'Early Growth', 'Vegetative', 'Flowering', 'Grain Filling', 'Harvest'],
                    data: [50, 40, 80, 120, 150, 30]
                },
                steps: [
                    { title: 'Land Preparation', content: 'Deep plowing, disc harrowing, and leveling. Remove weeds and crop residues.' },
                    { title: 'Seed Selection', content: 'Choose high-yielding, disease-resistant varieties suitable for the region.' },
                    { title: 'Sowing', content: 'Plant at 60cm x 20cm spacing, 2-3 seeds per hill, 3-4cm depth.' },
                    { title: 'Water Management', content: 'Critical periods: germination, tasseling, and grain filling. Avoid waterlogging.' },
                    { title: 'Fertilizing', content: 'Apply NPK fertilizers in splits. Side dress with nitrogen during vegetative growth.' },
                    { title: 'Pest Control', content: 'Monitor for fall armyworm, stem borer, and aphids. Use integrated pest management.' },
                    { title: 'Harvesting', content: 'Harvest when moisture content is 18-20%. Proper drying and storage essential.' }
                ]
            },
            'green-gram': {
                title: 'Green Gram Cultivation',
                water: '300-500 mm per season',
                fertilizer: '20-30 kg/ha',
                climate: 'Suitable for dry and intermediate zones. Short duration crop (60-75 days). Drought tolerant.',
                timelineData: {
                    labels: ['Land Prep', 'Sowing', 'Vegetative', 'Flowering', 'Pod Filling', 'Harvest'],
                    data: [5, 2, 25, 10, 15, 3]
                },
                waterData: {
                    labels: ['Land Prep', 'Sowing', 'Vegetative', 'Flowering', 'Pod Filling', 'Harvest'],
                    data: [30, 20, 60, 80, 100, 10]
                },
                steps: [
                    { title: 'Land Preparation', content: 'Light tillage operations. Level the field properly for uniform water distribution.' },
                    { title: 'Seed Treatment', content: 'Treat seeds with fungicide and rhizobium bacteria for better nodulation.' },
                    { title: 'Sowing', content: 'Line sowing at 30cm row spacing, 3-4cm depth, 15-20 kg seeds per hectare.' },
                    { title: 'Water Management', content: 'Light irrigation at sowing, critical watering during flowering and pod formation.' },
                    { title: 'Fertilizing', content: 'Apply phosphorus and potassium at sowing. Minimal nitrogen due to nitrogen fixation.' },
                    { title: 'Weed Control', content: 'Hand weeding 2-3 times or use pre-emergence herbicides.' },
                    { title: 'Harvesting', content: 'Harvest when 80% pods turn brown. Dry properly before storage.' }
                ]
            },
            chili: {
                title: 'Chili Cultivation',
                water: '600-1000 mm per season',
                fertilizer: '100-150 kg/ha',
                climate: 'Grown in all agro-climatic zones. Requires warm weather and well-drained soils.',
                timelineData: {
                    labels: ['Nursery', 'Transplanting', 'Vegetative', 'Flowering', 'Fruiting', 'Harvest'],
                    data: [30, 3, 30, 15, 30, 15]
                },
                waterData: {
                    labels: ['Nursery', 'Transplanting', 'Vegetative', 'Flowering', 'Fruiting', 'Harvest'],
                    data: [50, 40, 80, 100, 120, 30]
                },
                steps: [
                    { title: 'Nursery Management', content: 'Prepare raised beds, sow seeds thinly, maintain moisture and shade.' },
                    { title: 'Land Preparation', content: 'Deep plowing, add organic matter, prepare raised beds for better drainage.' },
                    { title: 'Transplanting', content: 'Transplant 4-5 week old seedlings at 60cm x 45cm spacing.' },
                    { title: 'Water Management', content: 'Regular light irrigation, avoid water logging, drip irrigation preferred.' },
                    { title: 'Fertilizing', content: 'Balanced NPK fertilizers in splits, organic manure incorporation.' },
                    { title: 'Disease Management', content: 'Monitor for viral diseases, fungal infections, bacterial wilt.' },
                    { title: 'Harvesting', content: 'Multiple harvests every 10-15 days when fruits are mature.' }
                ]
            },
            onion: {
                title: 'Onion Cultivation',
                water: '400-600 mm per season',
                fertilizer: '80-120 kg/ha',
                climate: 'Cool, dry weather during bulb formation. Grown mainly in dry zone districts.',
                timelineData: {
                    labels: ['Land Prep', 'Planting', 'Vegetative', 'Bulb Formation', 'Maturity', 'Harvest'],
                    data: [7, 3, 60, 30, 15, 5]
                },
                waterData: {
                    labels: ['Land Prep', 'Planting', 'Vegetative', 'Bulb Formation', 'Maturity', 'Harvest'],
                    data: [40, 50, 80, 100, 60, 20]
                },
                steps: [
                    { title: 'Land Preparation', content: 'Deep plowing, fine tilth, raised beds, good drainage essential.' },
                    { title: 'Seed/Seedling', content: 'Direct seeding or transplanting of 6-8 week old seedlings.' },
                    { title: 'Planting', content: 'Plant at 15cm x 10cm spacing in rows on raised beds.' },
                    { title: 'Water Management', content: 'Regular irrigation, reduce watering during bulb maturation.' },
                    { title: 'Fertilizing', content: 'High potassium requirement, balanced NPK application in splits.' },
                    { title: 'Weed Management', content: 'Regular weeding, mechanical cultivation between rows.' },
                    { title: 'Harvesting', content: 'Harvest when tops fall and dry, proper curing essential.' }
                ]
            },
            tomato: {
                title: 'Tomato Cultivation',
                water: '600-800 mm per season',
                fertilizer: '120-160 kg/ha',
                climate: 'Grown in all seasons with proper variety selection. Requires support structures.',
                timelineData: {
                    labels: ['Nursery', 'Transplanting', 'Vegetative', 'Flowering', 'Fruiting', 'Harvest'],
                    data: [28, 3, 30, 15, 30, 15]
                },
                waterData: {
                    labels: ['Nursery', 'Transplanting', 'Vegetative', 'Flowering', 'Fruiting', 'Harvest'],
                    data: [50, 40, 80, 100, 120, 30]
                },
                steps: [
                    { title: 'Nursery Management', content: 'Sow in pro-trays or beds, maintain proper temperature and humidity.' },
                    { title: 'Land Preparation', content: 'Deep plowing, organic matter addition, raised bed preparation.' },
                    { title: 'Transplanting', content: 'Transplant 4-5 week seedlings at 75cm x 60cm spacing.' },
                    { title: 'Support System', content: 'Install stakes or trellises for plant support and training.' },
                    { title: 'Water Management', content: 'Drip irrigation preferred, consistent moisture levels important.' },
                    { title: 'Disease Control', content: 'Monitor for bacterial wilt, viral diseases, fungal infections.' },
                    { title: 'Harvesting', content: 'Harvest at breaker stage for distant markets, red ripe for local use.' }
                ]
            },
            banana: {
                title: 'Banana Cultivation',
                water: '1200-2000 mm per year',
                fertilizer: '200-300 kg/ha',
                climate: 'Tropical climate, high humidity, well-distributed rainfall. Perennial crop.',
                timelineData: {
                    labels: ['Planting', 'Vegetative (6m)', 'Flowering', 'Bunch Development', 'Harvest'],
                    data: [1, 6, 2, 3, 1]
                },
                waterData: {
                    labels: ['Month 1-3', 'Month 4-6', 'Month 7-9', 'Month 10-12'],
                    data: [100, 150, 180, 120]
                },
                steps: [
                    { title: 'Land Preparation', content: 'Deep pits (60cm x 60cm x 60cm), add organic matter and lime.' },
                    { title: 'Planting Material', content: 'Use tissue culture plants or healthy suckers, plant at onset of rains.' },
                    { title: 'Spacing', content: 'Plant at 2.5m x 2.5m or 3m x 2m spacing depending on variety.' },
                    { title: 'Water Management', content: 'Consistent moisture throughout year, drainage during rainy season.' },
                    { title: 'Fertilizing', content: 'High potassium requirement, regular fertilizer applications every 2 months.' },
                    { title: 'Sucker Management', content: 'Allow one follower sucker, remove excess suckers regularly.' },
                    { title: 'Harvesting', content: 'Harvest at 75-80% maturity, proper post-harvest handling.' }
                ]
            },
            mango: {
                title: 'Mango Cultivation',
                water: '1000-1500 mm per year',
                fertilizer: '100-200 kg/ha',
                climate: 'Tropical and subtropical climates. Dry period essential for flowering.',
                timelineData: {
                    labels: ['Planting', 'Vegetative (2-3y)', 'Flowering', 'Fruit Development', 'Harvest'],
                    data: [1, 24, 2, 4, 1]
                },
                waterData: {
                    labels: ['Dry Season', 'Pre-Monsoon', 'Monsoon', 'Post-Monsoon'],
                    data: [50, 100, 200, 80]
                },
                steps: [
                    { title: 'Site Selection', content: 'Well-drained soils, good air circulation, protection from strong winds.' },
                    { title: 'Planting', content: 'Plant grafted seedlings at 8m x 8m or 10m x 10m spacing.' },
                    { title: 'Training', content: 'Proper pruning and training for strong framework development.' },
                    { title: 'Water Management', content: 'Irrigation during dry periods, drainage during monsoons.' },
                    { title: 'Fertilizing', content: 'Balanced NPK with micronutrients, organic matter incorporation.' },
                    { title: 'Pest Management', content: 'Monitor for fruit flies, hoppers, anthracnose disease.' },
                    { title: 'Harvesting', content: 'Harvest at proper maturity, careful handling to prevent damage.' }
                ]
            },
            cabbage: {
                title: 'Cabbage Cultivation',
                water: '450-600 mm per season',
                fertilizer: '120-150 kg/ha',
                climate: 'Cool weather crop, grows best at 15-20°C. Mainly cultivated in hill country.',
                timelineData: {
                    labels: ['Nursery', 'Transplanting', 'Vegetative', 'Head Formation', 'Harvest'],
                    data: [25, 3, 45, 30, 5]
                },
                waterData: {
                    labels: ['Nursery', 'Transplanting', 'Vegetative', 'Head Formation', 'Harvest'],
                    data: [30, 50, 100, 120, 40]
                },
                steps: [
                    { title: 'Nursery Management', content: 'Sow in well-prepared beds, protect from heavy rain and pests.' },
                    { title: 'Land Preparation', content: 'Deep plowing, add compost or FYM, prepare raised beds.' },
                    { title: 'Transplanting', content: 'Transplant 3-4 week seedlings at 45cm x 45cm spacing.' },
                    { title: 'Water Management', content: 'Regular irrigation, consistent moisture for proper head development.' },
                    { title: 'Fertilizing', content: 'High nitrogen requirement during vegetative stage, balanced NPK.' },
                    { title: 'Disease Control', content: 'Monitor for clubroot, black rot, diamond back moth.' },
                    { title: 'Harvesting', content: 'Harvest when heads are compact and firm, morning hours preferred.' }
                ]
            }
        };
    }

    static getLivestockData() {
        return {
            cattle: {
                title: 'Cattle Management',
                icon: 'fas fa-cow',
                requirements: [
                    { icon: 'fas fa-thermometer-half', text: 'Temperature: 18-24°C optimal' },
                    { icon: 'fas fa-tint', text: 'Water: 30-50L per day' },
                    { icon: 'fas fa-wheat', text: 'Feed: 2-3% of body weight daily' }
                ],
                description: 'Cattle farming is suitable for all climate zones in Sri Lanka. Dairy cattle require specific nutrition and housing conditions for optimal milk production.',
                chartData: {
                    type: 'radar',
                    labels: ['Genetics', 'Nutrition', 'Health', 'Environment', 'Management'],
                    data: [85, 90, 80, 75, 70]
                },
                steps: [
                    { title: 'Housing', content: 'Provide adequate shelter with good ventilation (10-15 sq.ft per animal). Ensure proper drainage and cleanliness. Use concrete floors with rubber mats.' },
                    { title: 'Feeding', content: 'Balanced diet with 60% roughage and 40% concentrates. Provide quality grass, hay, and balanced concentrate feed. Ensure 24/7 clean water access.' },
                    { title: 'Health Management', content: 'Regular vaccination schedule (FMD, HS, BQ). Monthly deworming and health check-ups. Maintain detailed health records.' },
                    { title: 'Breeding', content: 'Artificial insemination with proven bulls. Heat detection and proper timing. Pregnancy monitoring and care.' },
                    { title: 'Milking', content: 'Maintain strict hygiene during milking. Use clean utensils and proper milking techniques. Immediate cooling and storage.' }
                ]
            },
            poultry: {
                title: 'Poultry Management',
                icon: 'fas fa-kiwi-bird',
                requirements: [
                    { icon: 'fas fa-thermometer-half', text: 'Temperature: 20-26°C' },
                    { icon: 'fas fa-tint', text: 'Water: Clean, continuous supply' },
                    { icon: 'fas fa-wheat', text: 'Feed: Layer/Broiler specific formulation' }
                ],
                description: 'Poultry farming includes layer and broiler production. Requires controlled environment, proper nutrition, and strict biosecurity measures.',
                chartData: {
                    type: 'line',
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                    data: [20, 50, 80, 95]
                },
                steps: [
                    { title: 'Housing Setup', content: 'Provide 1.5-2 sq.ft per bird for layers. Ensure proper ventilation (6-8 air changes per hour). Install feeders and drinkers.' },
                    { title: 'Feeding Program', content: 'Starter feed (0-6 weeks), grower feed (7-18 weeks), layer feed (19+ weeks). Maintain 16-18% protein for layers.' },
                    { title: 'Health Management', content: 'Vaccination against Newcastle, Fowl pox, IBD. Biosecurity measures and visitor restrictions. Daily monitoring for diseases.' },
                    { title: 'Egg Collection', content: 'Collect eggs 3-4 times daily. Proper cleaning and grading. Maintain cold storage at 4°C.' },
                    { title: 'Record Keeping', content: 'Daily egg production, feed consumption, mortality records. Financial analysis and performance monitoring.' }
                ]
            },
            buffaloes: {
                title: 'Buffalo Management',
                icon: 'fas fa-cow',
                requirements: [
                    { icon: 'fas fa-thermometer-half', text: 'Temperature: Tolerates heat well' },
                    { icon: 'fas fa-tint', text: 'Water: 80-100L per day, wallowing essential' },
                    { icon: 'fas fa-wheat', text: 'Feed: Prefers wet feed and aquatic plants' }
                ],
                description: 'Buffalo farming is well-suited for wet zone areas. Known for rich milk with high fat content and disease resistance.',
                chartData: {
                    type: 'pie',
                    labels: ['Fat Content', 'Protein', 'Water', 'Minerals'],
                    data: [7, 4, 87, 2]
                },
                steps: [
                    { title: 'Housing', content: 'Open housing with wallowing facilities. Provide shade and protection from sun. Concrete floors with good drainage.' },
                    { title: 'Feeding', content: 'Green fodder, paddy straw, rice bran. Aquatic plants like water hyacinth. Concentrates 1-2 kg per day.' },
                    { title: 'Health Care', content: 'Regular vaccination and deworming. Monitor for diseases like FMD and HS. Hoof trimming every 6 months.' },
                    { title: 'Breeding', content: 'Natural service or AI. Heat detection in early morning or evening. Gestation period 310 days.' },
                    { title: 'Milking', content: 'Hand milking preferred. Rich milk with 7-8% fat content. Proper hygiene essential.' }
                ]
            },
            sheep: {
                title: 'Sheep Management',
                icon: 'fas fa-sheep',
                requirements: [
                    { icon: 'fas fa-thermometer-half', text: 'Temperature: Adaptable to various climates' },
                    { icon: 'fas fa-tint', text: 'Water: 2-4L per day' },
                    { icon: 'fas fa-wheat', text: 'Feed: Grazing and supplementary feeding' }
                ],
                description: 'Sheep farming suitable for semi-arid areas. Provides meat, milk, and wool. Low maintenance with good grazing management.',
                chartData: {
                    type: 'radar',
                    labels: ['Fiber Length', 'Fiber Diameter', 'Crimp', 'Strength', 'Elasticity'],
                    data: [8, 6, 7, 5, 6]
                },
                steps: [
                    { title: 'Housing', content: 'Simple shed with 3-4 sq.ft per sheep. Good ventilation and dry bedding. Separate pens for breeding.' },
                    { title: 'Feeding', content: 'Grazing for 6-8 hours daily. Supplementary feeding with concentrates. Mineral supplements essential.' },
                    { title: 'Health Management', content: 'Vaccination against enterotoxemia and tetanus. Regular deworming every 3 months. Foot care and trimming.' },
                    { title: 'Breeding', content: 'Breeding season planning. Ram to ewe ratio 1:20-25. Pregnancy care and nutrition.' },
                    { title: 'Shearing', content: 'Annual shearing before summer. Proper techniques to avoid cuts. Wool grading and marketing.' }
                ]
            },
            pigs: {
                title: 'Pig Management',
                icon: 'fas fa-pig',
                requirements: [
                    { icon: 'fas fa-thermometer-half', text: 'Temperature: 18-24°C optimal' },
                    { icon: 'fas fa-tint', text: 'Water: 10-15L per day' },
                    { icon: 'fas fa-wheat', text: 'Feed: High energy and protein diet' }
                ],
                description: 'Pig farming for meat production. Fast growth rate and high feed conversion efficiency. Requires proper housing and feeding.',
                chartData: {
                    type: 'bar',
                    labels: ['Weaning', 'Growing', 'Finishing'],
                    data: [400, 600, 800]
                },
                steps: [
                    { title: 'Housing', content: 'Concrete floors with proper drainage. Separate areas for different age groups. Temperature control systems.' },
                    { title: 'Feeding', content: 'Balanced commercial feeds or farm-mixed rations. 3-4% of body weight daily. Clean water always available.' },
                    { title: 'Health Management', content: 'Vaccination against Classical Swine Fever and FMD. Biosecurity measures. Regular health monitoring.' },
                    { title: 'Breeding', content: 'Artificial insemination or natural breeding. Pregnancy monitoring and farrowing care. Weaning at 4-6 weeks.' },
                    { title: 'Marketing', content: 'Market at 90-100 kg live weight. Proper transport arrangements. Value addition opportunities.' }
                ]
            },
            goats: {
                title: 'Goat Management',
                icon: 'fas fa-goat',
                requirements: [
                    { icon: 'fas fa-thermometer-half', text: 'Temperature: Very adaptable' },
                    { icon: 'fas fa-tint', text: 'Water: 1-3L per day' },
                    { icon: 'fas fa-wheat', text: 'Feed: Browse, grass, and concentrates' }
                ],
                description: 'Goat farming for milk, meat, and fiber. Very adaptable animals requiring minimal housing and feeding costs.',
                chartData: {
                    type: 'doughnut',
                    labels: ['Milk Production', 'Meat Production', 'Fiber'],
                    data: [40, 50, 10]
                },
                steps: [
                    { title: 'Housing', content: 'Simple shelter with good ventilation. Raised floors preferred. Separate areas for kids and adults.' },
                    { title: 'Feeding', content: 'Browsing and grazing 6-8 hours daily. Supplementary feeding with concentrates. Mineral supplements important.' },
                    { title: 'Health Management', content: 'Vaccination against PPR and enterotoxemia. Regular deworming. Hoof trimming and parasite control.' },
                    { title: 'Breeding', content: 'Buck to doe ratio 1:20-30. Breeding twice a year possible. Kidding management and care.' },
                    { title: 'Production', content: 'Milk production 1-3 liters per day. Meat production for local markets. Proper marketing strategies.' }
                ]
            },
            other: {
                title: 'Other Livestock (Rabbits, Ducks, Turkeys)',
                icon: 'fas fa-paw',
                requirements: [
                    { icon: 'fas fa-thermometer-half', text: 'Temperature: Varies by species' },
                    { icon: 'fas fa-tint', text: 'Water: Species-specific requirements' },
                    { icon: 'fas fa-wheat', text: 'Feed: Balanced nutrition for each species' }
                ],
                description: 'Various small livestock species suitable for backyard farming. Each species has specific requirements for housing, feeding, and management.',
                chartData: {
                    type: 'bar',
                    labels: ['Rabbits', 'Ducks', 'Turkeys', 'Quails'],
                    data: [30, 25, 20, 35]
                },
                steps: [
                    { title: 'Species Selection', content: 'Choose species based on climate, available resources, and market demand. Consider local preferences and conditions.' },
                    { title: 'Housing Requirements', content: 'Species-appropriate housing with protection from predators and weather. Adequate space and ventilation for each species.' },
                    { title: 'Feeding Programs', content: 'Balanced nutrition specific to each species. Commercial feeds or farm-mixed rations. Clean water always available.' },
                    { title: 'Health Management', content: 'Species-specific vaccination schedules. Regular health monitoring and disease prevention measures.' },
                    { title: 'Breeding Management', content: 'Understand breeding cycles for each species. Proper mating ratios and breeding management practices.' }
                ]
            }
        };
    }

    static getMarketData() {
        return {
            currentPrices: [
                { product: 'Rice (Nadu)', unit: 'kg', minPrice: '210.00', maxPrice: '230.00', trend: '+2.5%', trendClass: 'text-green-600' },
                { product: 'Tomatoes', unit: 'kg', minPrice: '237.29', maxPrice: '760.00', trend: '-5.2%', trendClass: 'text-red-600' },
                { product: 'Chicken (Live)', unit: 'kg', minPrice: '1215.00', maxPrice: '1490.00', trend: '0.3%', trendClass: 'text-gray-600' },
                { product: 'Eggs', unit: 'unit', minPrice: '30.00', maxPrice: '100.00', trend: '+1.8%', trendClass: 'text-green-600' },
                { product: 'Milk (Fresh)', unit: 'liter', minPrice: '350.00', maxPrice: '600.00', trend: '+1.2%', trendClass: 'text-green-600' }
            ],
            priceHistory: {
                rice: [215, 218, 220, 219, 222, 225, 224, 220, 221, 223, 225, 228, 227, 229, 230, 228, 227, 226, 225, 224, 223, 222, 221, 220, 219, 217, 216, 215, 214, 210],
                tomatoes: [700, 680, 650, 600, 550, 500, 480, 450, 400, 380, 350, 300, 280, 250, 237.29, 250, 280, 300, 350, 400, 450, 500, 550, 600, 650, 700, 720, 740, 750, 760],
                chicken: [1220, 1235, 1250, 1245, 1260, 1275, 1270, 1285, 1300, 1310, 1325, 1340, 1335, 1350, 1365, 1370, 1385, 1400, 1395, 1410, 1425, 1430, 1445, 1460, 1455, 1470, 1485, 1480, 1490, 1485],
                eggs: [35, 38, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100, 98, 95, 90, 85, 80, 75, 70, 65, 60, 55, 50, 45, 40, 35, 30],
                milk: [400, 420, 450, 480, 500, 520, 550, 580, 600, 590, 570, 550, 530, 500, 480, 450, 430, 400, 380, 350, 360, 380, 400, 420, 450, 480, 500, 520, 550, 580]
            }
        };
    }

    static getResourceData() {
        return {
            governmentInstitutions: [
                { name: 'Department of Agriculture', url: '#', icon: 'fas fa-leaf' },
                { name: 'Department of Animal Production & Health', url: '#', icon: 'fas fa-paw' },
                { name: 'Agrarian Services Department', url: '#', icon: 'fas fa-tractor' },
                { name: 'Coconut Development Authority', url: '#', icon: 'fas fa-seedling' }
            ],
            financialSupport: [
                { name: 'Agricultural Loans', url: '#', icon: 'fas fa-coins' },
                { name: 'Subsidy Schemes', url: '#', icon: 'fas fa-hand-holding-usd' },
                { name: 'Insurance Programs', url: '#', icon: 'fas fa-shield-alt' },
                { name: 'Micro Credit Schemes', url: '#', icon: 'fas fa-credit-card' }
            ],
            publications: [
                { name: 'Crop Cultivation Manuals', url: '#', icon: 'fas fa-book' },
                { name: 'Livestock Management Guides', url: '#', icon: 'fas fa-book-open' },
                { name: 'Research Publications', url: '#', icon: 'fas fa-file-alt' },
                { name: 'Extension Materials', url: '#', icon: 'fas fa-newspaper' }
            ],
            faqs: [
                {
                    question: 'What is the best time to plant paddy in Sri Lanka?',
                    answer: 'The optimal time for paddy cultivation depends on the region and season. In the Maha season (October-February), planting is typically done in October-November, while in the Yala season (April-August), planting is done in April-May.'
                },
                {
                    question: 'How can I get subsidies for agricultural inputs?',
                    answer: 'Contact your local Agrarian Service Center or Divisional Secretary office. You need to register as a farmer and provide necessary documents including land ownership or cultivation rights.'
                },
                {
                    question: 'What are the common diseases affecting coconut trees?',
                    answer: 'Common diseases include Coconut Triangle Scale, Red Palm Weevil, and Lethal Yellowing Disease. Regular monitoring and integrated pest management practices are recommended.'
                },
                {
                    question: 'Where can I get quality planting materials?',
                    answer: 'Quality seeds and planting materials are available through Department of Agriculture, certified seed producers, and agricultural research stations. Always use certified materials for better yields.'
                }
            ]
        };
    }
}