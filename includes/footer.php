    <footer class="bg-[#2C1810] text-[#F4A460] mt-auto">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Brand Section -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-[#8B4513] to-[#DAA520] flex items-center justify-center">
                            <span class="text-white text-xl font-bold">TG</span>
                        </div>
                        <span class="text-xl font-bold safari-heading text-[#F4A460]"><?= SITE_NAME ?></span>
                    </div>
                    <p class="text-[#F4A460]/80 max-w-md">
                        Discover the heart of Africa with our expert local guides. Experience authentic safaris, cultural encounters, and unforgettable adventures.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-[#F4A460] font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="<?= BASE_URL ?>/destinations" class="text-[#F4A460]/80 hover:text-[#DAA520] transition-colors duration-200">
                                Destinations
                            </a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>/guides" class="text-[#F4A460]/80 hover:text-[#DAA520] transition-colors duration-200">
                                Find a Guide
                            </a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>/about" class="text-[#F4A460]/80 hover:text-[#DAA520] transition-colors duration-200">
                                About Us
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h3 class="text-[#F4A460] font-semibold mb-4">Contact Us</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-[#DAA520]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span class="text-[#F4A460]/80">contact@touristguidehub.com</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-[#DAA520]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <span class="text-[#F4A460]/80">+254 123 456 789</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="mt-12 pt-8 border-t border-[#F4A460]/20">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-[#F4A460]/60 text-sm">
                        &copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.
                    </p>
                    <div class="flex space-x-6 mt-4 md:mt-0">
                        <a href="<?= BASE_URL ?>/privacy" class="text-[#F4A460]/60 hover:text-[#DAA520] text-sm">
                            Privacy Policy
                        </a>
                        <a href="<?= BASE_URL ?>/terms" class="text-[#F4A460]/60 hover:text-[#DAA520] text-sm">
                            Terms of Service
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</body>
</html> 