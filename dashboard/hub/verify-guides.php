<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in and is a hub admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Hub') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get pending guide verifications
$stmt = $conn->prepare("
    SELECT u.*, p.bio, p.languages
    FROM users u
    LEFT JOIN profiles p ON u.user_id = p.user_id
    WHERE u.role = 'Guide' AND u.status = 'pending'
    ORDER BY u.created_at DESC
");
$stmt->execute();
$pending_guides = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="px-4 py-5 sm:px-6">
                <h2 class="text-lg font-medium text-gray-900">Guide Verifications</h2>
                <p class="mt-1 text-sm text-gray-500">Review and verify guide applications</p>
            </div>
        </div>

        <!-- Pending Verifications -->
        <div class="mt-8">
            <?php if (empty($pending_guides)): ?>
                <div class="bg-white shadow rounded-lg p-6 text-center">
                    <p class="text-gray-500">No pending guide verifications</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($pending_guides as $guide): ?>
                        <div class="bg-white shadow rounded-lg overflow-hidden">
                            <div class="p-6">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900">
                                            <?= htmlspecialchars($guide['name']) ?>
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            <?= htmlspecialchars($guide['email']) ?>
                                        </p>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">
                                        Pending Verification
                                    </span>
                                </div>

                                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <!-- Guide Bio -->
                                    <?php if ($guide['bio']): ?>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-500">Bio</h4>
                                            <p class="mt-2 text-sm text-gray-900">
                                                <?= nl2br(htmlspecialchars($guide['bio'])) ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Languages -->
                                    <?php if ($guide['languages']): ?>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-500">Languages</h4>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                <?php foreach (explode(',', $guide['languages']) as $language): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                        <?= htmlspecialchars(trim($language)) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Action Buttons -->
                                <div class="mt-6 flex justify-end space-x-3">
                                    <button onclick="verifyGuide(<?= $guide['user_id'] ?>, 'reject')"
                                            class="px-4 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 hover:bg-red-50">
                                        Reject
                                    </button>
                                    <button onclick="verifyGuide(<?= $guide['user_id'] ?>, 'verify')"
                                            class="px-4 py-2 bg-green-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-green-700">
                                        Verify Guide
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function verifyGuide(guideId, action) {
    if (confirm(`Are you sure you want to ${action} this guide?`)) {
        window.location.href = `process-verification.php?id=${guideId}&action=${action}`;
    }
}
</script>

<?php include '../../includes/footer.php'; ?> 