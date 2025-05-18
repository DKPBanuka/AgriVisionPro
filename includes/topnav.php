<header class="bg-white shadow-sm z-10">
    <div class="flex items-center justify-between px-6 py-3">
        <div class="flex items-center">
            <button id="sidebar-toggle" class="mr-4 text-gray-500 hover:text-gray-600 focus:outline-none" title="Toggle Sidebar">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <div class="relative max-w-md w-full">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input id="search-input" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search crops, fields..." type="search">
                <div id="search-results" class="search-results hidden"></div>
            </div>
        </div>
        
        <div class="flex items-center space-x-4">
            <button class="p-1 text-gray-400 hover:text-gray-500 focus:outline-none" title="Notifications">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
            </button>
            
            <div class="relative">
                <button id="user-menu" class="flex items-center space-x-2 focus:outline-none">
                    <?php if (!empty($current_user['profile_picture'])): ?>
                        <img src="../../uploads/profiles/<?= htmlspecialchars($current_user['profile_picture']) ?>" 
                            alt="Profile Picture" 
                            class="h-8 w-8 rounded-full object-cover">
                    <?php else: ?>
                        <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium">
                            <?= $current_user['initials'] ?>
                        </div>
                    <?php endif; ?>
                    <svg class="h-5 w-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
                
                <div id="user-menu-dropdown" class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 py-1 z-50 user-profile-dropdown">
                    <div class="px-4 py-3 border-b">
                        <?php if (!empty($current_user['profile_picture'])): ?>
                            <img src="../../uploads/profiles/<?= htmlspecialchars($current_user['profile_picture']) ?>" 
                                alt="Profile Picture" 
                                class="h-10 w-10 rounded-full object-cover mb-2 mx-auto">
                        <?php endif; ?>
                        <div class="text-center">
                            <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($current_user['name']) ?></p>
                            <p class="text-xs text-gray-800 truncate"><?= htmlspecialchars($current_user['email']) ?></p>
                            <p class="text-xs text-gray-500 mt-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    <?= htmlspecialchars($current_user['role']) ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-user-circle mr-2"></i> Your Profile
                    </a>
                    <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-cog mr-2"></i> Settings
                    </a>
                    <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-sign-out-alt mr-2"></i> Sign out
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>