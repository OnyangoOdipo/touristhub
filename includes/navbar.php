<nav class="bg-white safari-shadow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <!-- Logo and Brand -->
            <div class="flex items-center">
                <a href="<?= BASE_URL ?>/public/index.php" class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-[#8B4513] to-[#DAA520] flex items-center justify-center">
                        <span class="text-white text-xl font-bold">TG</span>
                    </div>
                    <span class="text-xl font-bold safari-heading text-[#8B4513]"><?= SITE_NAME ?></span>
                </a>
            </div>

            <!-- Navigation Links -->
            <div class="hidden md:flex items-center space-x-8">
                <a href="<?= BASE_URL ?>/destinations" class="text-[#8B4513] hover:text-[#DAA520] transition-colors duration-200">
                    Destinations
                </a>
                <a href="<?= BASE_URL ?>/guides" class="text-[#8B4513] hover:text-[#DAA520] transition-colors duration-200">
                    Guides
                </a>
                <a href="<?= BASE_URL ?>/about" class="text-[#8B4513] hover:text-[#DAA520] transition-colors duration-200">
                    About
                </a>
            </div>

            <!-- Auth Buttons -->
            <div class="flex items-center space-x-4">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="relative group">
                        <button class="flex items-center space-x-2 text-[#8B4513] hover:text-[#DAA520]">
                            <span><?= $_SESSION['name'] ?? 'Account' ?></span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div class="absolute right-0 w-48 mt-2 py-2 bg-white rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                            <a href="<?= BASE_URL ?>/public/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-[#FDF8F3]">
                                Dashboard
                            </a>
                            <a href="<?= BASE_URL ?>/auth/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/auth/login.php" 
                       class="px-4 py-2 text-[#8B4513] hover:text-[#DAA520] transition-colors duration-200">
                        Login
                    </a>
                    <a href="<?= BASE_URL ?>/auth/register.php" 
                       class="px-6 py-2 bg-gradient-to-r from-[#8B4513] to-[#DAA520] text-white rounded-full hover:shadow-lg transition-shadow duration-200">
                        Get Started
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav> 