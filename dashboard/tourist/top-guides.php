<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Get guides with their stats
$stmt = $conn->prepare("
    SELECT 
        u.user_id,
        u.name,
        u.rating,
        p.bio,
        p.languages,
        COUNT(DISTINCT b.booking_id) as total_tours,
        COUNT(DISTINCT d.destination_id) as total_destinations,
        COUNT(DISTINCT r.review_id) as total_reviews,
        COALESCE(AVG(r.rating), 0) as average_rating,
        GROUP_CONCAT(DISTINCT d.region) as regions
    FROM users u
    LEFT JOIN profiles p ON u.user_id = p.user_id
    LEFT JOIN bookings b ON u.user_id = b.guide_id
    LEFT JOIN destinations d ON u.user_id = d.guide_id
    LEFT JOIN reviews r ON u.user_id = r.guide_id
    WHERE u.role = 'Guide' AND u.status = 'verified'
    GROUP BY u.user_id
    ORDER BY average_rating DESC, total_tours DESC
    LIMIT 10
");
$stmt->execute();
$top_guides = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl">
                Meet Our Top Guides
            </h1>
            <p class="mt-6 text-xl max-w-2xl mx-auto">
                Discover experienced guides who have earned exceptional ratings and created unforgettable experiences for our travelers.
            </p>
        </div>
    </div>

    <!-- Guides Grid -->
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($top_guides as $index => $guide): ?>
                <div class="bg-white rounded-lg shadow-lg overflow-hidden transform transition duration-300 hover:scale-105">
                    <!-- Guide Card Header -->
                    <div class="relative h-48 bg-gradient-to-r from-blue-500 to-purple-500">
                        <div class="absolute top-4 left-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white text-blue-600">
                                #<?= $index + 1 ?> Top Guide
                            </span>
                        </div>
                        <div class="absolute bottom-4 left-4 text-white">
                            <h3 class="text-2xl font-bold"><?= htmlspecialchars($guide['name']) ?></h3>
                            <div class="flex items-center mt-1">
                                <span class="text-yellow-300">
                                    <?= str_repeat('★', round($guide['average_rating'])) ?>
                                    <?= str_repeat('☆', 5 - round($guide['average_rating'])) ?>
                                </span>
                                <span class="ml-2"><?= number_format($guide['average_rating'], 1) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Guide Stats -->
                    <div class="p-6">
                        <!-- Quick Stats -->
                        <div class="grid grid-cols-3 gap-4 mb-6 text-center">
                            <div>
                                <p class="text-2xl font-bold text-blue-600"><?= $guide['total_tours'] ?></p>
                                <p class="text-sm text-gray-500">Tours</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-purple-600"><?= $guide['total_destinations'] ?></p>
                                <p class="text-sm text-gray-500">Destinations</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-green-600"><?= $guide['total_reviews'] ?></p>
                                <p class="text-sm text-gray-500">Reviews</p>
                            </div>
                        </div>

                        <!-- Bio -->
                        <?php if ($guide['bio']): ?>
                            <p class="text-gray-600 mb-4 line-clamp-3">
                                <?= htmlspecialchars($guide['bio']) ?>
                            </p>
                        <?php endif; ?>

                        <!-- Languages -->
                        <?php if ($guide['languages']): ?>
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Languages</h4>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach (explode(',', $guide['languages']) as $language): ?>
                                        <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded-full">
                                            <?= htmlspecialchars(trim($language)) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Regions -->
                        <?php if ($guide['regions']): ?>
                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Specializes in</h4>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach (array_unique(explode(',', $guide['regions'])) as $region): ?>
                                        <span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-700 rounded-full">
                                            <?= htmlspecialchars(trim($region)) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <div class="flex space-x-3">
                            <a href="guide-profile.php?id=<?= $guide['user_id'] ?>" 
                               class="flex-1 text-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                View Profile
                            </a>
                            <a href="messages.php?guide_id=<?= $guide['user_id'] ?>" 
                               class="flex-1 text-center px-4 py-2 border border-blue-600 text-sm font-medium rounded-md text-blue-600 hover:bg-blue-50">
                                Contact
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
