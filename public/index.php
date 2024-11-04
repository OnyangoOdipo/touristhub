<?php
require_once '../config/config.php';
require_once '../includes/header.php';

// Dummy data for featured destinations
$featured_destinations = [
    [
        'name' => 'Serengeti National Park',
        'location' => 'Tanzania',
        'description' => 'Experience the great wildebeest migration and spot the Big Five in their natural habitat.',
        'image' => 'serengeti.jpg',
        'guide_name' => 'John Kimani',
        'guide_rating' => 4.8,
        'booking_count' => 156,
        'price' => 1200
    ],
    [
        'name' => 'Masai Mara',
        'location' => 'Kenya',
        'description' => 'Witness the incredible wildlife and immerse yourself in Maasai culture.',
        'image' => 'masai-mara.jpg',
        'guide_name' => 'Sarah Omondi',
        'guide_rating' => 4.9,
        'booking_count' => 142,
        'price' => 1100
    ],
    [
        'name' => 'Victoria Falls',
        'location' => 'Zimbabwe',
        'description' => 'See one of the world\'s largest waterfalls and enjoy adventure activities.',
        'image' => 'victoria-falls.jpg',
        'guide_name' => 'Michael Banda',
        'guide_rating' => 4.7,
        'booking_count' => 98,
        'price' => 900
    ]
];

// Dummy data for top guides
$top_guides = [
    [
        'name' => 'David Mutua',
        'bio' => 'Expert in wildlife photography and bird watching with 10 years of experience.',
        'rating' => 4.9,
        'total_tours' => 245,
        'languages' => ['English', 'Swahili', 'French'],
        'regions' => ['East Africa', 'Southern Africa']
    ],
    [
        'name' => 'Grace Akinyi',
        'bio' => 'Specialized in cultural tours and traditional experiences.',
        'rating' => 4.8,
        'total_tours' => 189,
        'languages' => ['English', 'Swahili', 'German'],
        'regions' => ['East Africa', 'Central Africa']
    ],
    [
        'name' => 'James Okoro',
        'bio' => 'Adventure specialist with expertise in mountain climbing and safaris.',
        'rating' => 4.7,
        'total_tours' => 167,
        'languages' => ['English', 'Swahili', 'Italian'],
        'regions' => ['East Africa', 'North Africa']
    ]
];

// Dummy data for recent reviews
$recent_reviews = [
    [
        'tourist_name' => 'Emma Thompson',
        'destination' => 'Serengeti National Park',
        'rating' => 5,
        'comment' => 'An incredible experience! Our guide was knowledgeable and we saw all the Big Five.',
        'date' => '2024-03-15'
    ],
    [
        'tourist_name' => 'Marco Rossi',
        'destination' => 'Masai Mara',
        'rating' => 5,
        'comment' => 'The cultural experience with the Maasai people was unforgettable.',
        'date' => '2024-03-10'
    ],
    [
        'tourist_name' => 'Sophie Chen',
        'destination' => 'Victoria Falls',
        'rating' => 4,
        'comment' => 'Breathtaking views and exciting activities. Well organized tour.',
        'date' => '2024-03-05'
    ]
];

// Dummy data for upcoming safaris
$upcoming_safaris = [
    [
        'name' => 'Great Migration Safari',
        'location' => 'Serengeti & Masai Mara',
        'date' => '2024-06-15',
        'duration' => '7 days',
        'spots_left' => 4,
        'price' => 2500
    ],
    [
        'name' => 'Gorilla Trekking Adventure',
        'location' => 'Uganda',
        'date' => '2024-07-01',
        'duration' => '5 days',
        'spots_left' => 6,
        'price' => 3000
    ],
    [
        'name' => 'Desert Safari Experience',
        'location' => 'Namibia',
        'date' => '2024-08-10',
        'duration' => '6 days',
        'spots_left' => 8,
        'price' => 2200
    ]
];
?>

<!-- Hero Section with Safari Video Background -->
<div class="relative h-screen">
    <!-- Video Background -->
    <div class="absolute inset-0 w-full h-full overflow-hidden">
        <video class="object-cover w-full h-full" autoplay loop muted playsinline>
            <source src="<?= BASE_URL ?>/assets/videos/safari-bg.mp4" type="video/mp4">
        </video>
        <div class="absolute inset-0 bg-black opacity-50"></div>
    </div>

    <!-- Hero Content -->
    <div class="relative h-full flex items-center">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-6xl font-bold text-white mb-6 font-safari">
                Discover The Heart of Africa
            </h1>
            <p class="text-xl md:text-2xl text-white mb-8 max-w-3xl mx-auto">
                Experience authentic African adventures with local guides who know every trail, every story, and every secret
            </p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="space-x-4">
                    <a href="<?= BASE_URL ?>/auth/register.php"
                        class="inline-block px-8 py-3 bg-[#DAA520] text-white font-semibold rounded-full hover:bg-[#B8860B] transition-colors duration-300 transform hover:scale-105">
                        Start Your Journey
                    </a>
                    <a href="<?= BASE_URL ?>/destinations"
                        class="inline-block px-8 py-3 border-2 border-white text-white font-semibold rounded-full hover:bg-white hover:text-[#8B4513] transition-colors duration-300">
                        Explore Destinations
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scroll Indicator -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
        </svg>
    </div>
</div>

<!-- Featured Experiences -->
<section class="py-16 bg-[#FDF8F3]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-[#8B4513] text-center mb-12 font-safari">Unforgettable African Experiences</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Safari Experience -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden transform hover:scale-105 transition-transform duration-300">
                <img src="<?= BASE_URL ?>/assets/images/safari-experience.jpg" alt="Safari Experience" class="w-full h-48 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-[#8B4513] mb-2">Wildlife Safaris</h3>
                    <p class="text-gray-600">Witness the majestic Big Five in their natural habitat with expert guides.</p>
                </div>
            </div>

            <!-- Cultural Experience -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden transform hover:scale-105 transition-transform duration-300">
                <img src="<?= BASE_URL ?>/assets/images/cultural.jpg" alt="Cultural Experience" class="w-full h-48 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-[#8B4513] mb-2">Cultural Immersion</h3>
                    <p class="text-gray-600">Experience authentic African traditions and connect with local communities.</p>
                </div>
            </div>

            <!-- Adventure Experience -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden transform hover:scale-105 transition-transform duration-300">
                <img src="<?= BASE_URL ?>/assets/images/adventure.jpg" alt="Adventure Experience" class="w-full h-48 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-[#8B4513] mb-2">Adventure Trails</h3>
                    <p class="text-gray-600">Trek through diverse landscapes from mountains to savannas.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="py-16 bg-[#8B4513] text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-12 font-safari">Why Choose Our Local Guides</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-[#DAA520] rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2">Local Expertise</h3>
                <p class="text-gray-300">Deep knowledge of terrain and wildlife</p>
            </div>
            <!-- Add more features -->
        </div>
    </div>
</section>

<!-- Featured Destinations -->
<section class="py-16 bg-[#FDF8F3]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-[#8B4513] text-center mb-12 font-safari">Popular Safari Destinations</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Add dynamic destinations from database -->
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-[#8B4513] text-center mb-12 font-safari">What Our Travelers Say</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Add dynamic testimonials -->
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-16 bg-[#8B4513] text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold mb-6 font-safari">Ready to Start Your African Adventure?</h2>
        <p class="text-xl mb-8 max-w-2xl mx-auto">
            Join our community of adventurers and discover the magic of Africa with expert local guides.
        </p>
        <a href="<?= BASE_URL ?>/auth/register.php"
            class="inline-block px-8 py-3 bg-[#DAA520] text-white font-semibold rounded-full hover:bg-[#B8860B] transition-colors duration-300 transform hover:scale-105">
            Begin Your Journey Today
        </a>
    </div>
</section>

<!-- Instagram Feed Section -->
<section class="py-16 bg-[#FDF8F3]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-[#8B4513] text-center mb-12 font-safari">Safari Moments</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <!-- Add Instagram-like feed of safari photos -->
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?> 