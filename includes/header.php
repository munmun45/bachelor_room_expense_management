<?php
/**
 * Common Header Component
 * Provides consistent header with back button across all pages
 */

// Get user profile image if logged in
$profile_image = 'uploads/profile/default_avatar.png';
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if ($user['profile_image']) {
            $profile_image = 'uploads/profile/' . $user['profile_image'];
        }
    }
}

// Default values
$back_url = $back_url ?? 'index.php';
$page_title = $page_title ?? 'Bachelor Room';
$page_subtitle = $page_subtitle ?? 'Expense Management';
$page_icon = $page_icon ?? 'fas fa-wallet';
$icon_color = $icon_color ?? 'text-blue-600';
$show_profile = $show_profile ?? true;
?>

<!-- Header -->
<header class="fixed top-0 left-0 right-0 bg-white shadow-md z-10">
    <div class="flex justify-between items-center px-4 py-3">
        <div class="flex items-center">
            <a href="<?php echo htmlspecialchars($back_url); ?>" class="mr-3 back-button" title="Go Back">
                <i class="fas fa-arrow-left text-blue-600 text-xl hover:text-blue-800 transition-colors"></i>
            </a>
            <div class="mr-3">
                <i class="<?php echo htmlspecialchars($page_icon); ?> <?php echo htmlspecialchars($icon_color); ?> text-2xl"></i>
            </div>
            <div>
                <h1 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($page_title); ?></h1>
                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($page_subtitle); ?></p>
            </div>
        </div>
        <div class="flex items-center">
            <?php if ($show_profile && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                <div class="mr-4 relative">
                    <i class="fas fa-bell text-gray-600 text-xl"></i>
                    <span class="absolute top-0 right-0 h-2 w-2 bg-red-500 rounded-full"></span>
                </div>
                <a href="profile.php">
                    <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="h-8 w-8 rounded-full border-2 border-blue-500 object-cover">
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>
