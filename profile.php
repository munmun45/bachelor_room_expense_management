<?php
session_start();
require_once 'config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: login.php');
    exit;
}

$user = $result->fetch_assoc();

// Initialize default values for user fields if not set
$user['email'] = $user['email'] ?? '';
$user['room_number'] = $user['room_number'] ?? '';
$user['occupation'] = $user['occupation'] ?? '';
$user['emergency_contact'] = $user['emergency_contact'] ?? '';

// Format joined date if exists
$joined_date = !empty($user['joined_date']) ? date('d M, Y', strtotime($user['joined_date'])) : 'Not set';

// Get KYC status
$kyc_status = $user['kyc_status'];
$kyc_badge_class = ($kyc_status == 'verified') ? 'kyc-verified' : 'kyc-badge';
$kyc_badge_text = ucfirst($kyc_status);

// Profile image path
$profile_image = !empty($user['profile_image']) ? 'uploads/profile/' . $user['profile_image'] : 'uploads/profile/default_avatar.png';

// Check if directory exists, if not create it
if (!file_exists('uploads/profile/')) {
    mkdir('uploads/profile/', 0777, true);
}

// Create default avatar if it doesn't exist
if (!file_exists('uploads/profile/default_avatar.png')) {
    // Create a simple text file as placeholder
    $default_avatar = 'uploads/profile/default_avatar.png';
    file_put_contents($default_avatar, ''); // Create empty file

    // Use a default image from the web if available
    $default_image_path = 'assets/default_avatar.png';
    if (file_exists($default_image_path)) {
        copy($default_image_path, $default_avatar);
    }
}

// Handle profile update
$update_message = '';
if (isset($_GET['status']) && isset($_GET['message'])) {
    $status = $_GET['status'];
    $message = $_GET['message'];

    if ($status === 'success') {
        $update_message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">' . htmlspecialchars($message) . '</div>';
    } else {
        $update_message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">' . htmlspecialchars($message) . '</div>';
    }
}

// Handle profile image upload
if (isset($_POST['update_profile']) && isset($_FILES['profile_image'])) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['profile_image']['name'];
    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (in_array($file_ext, $allowed)) {
        $new_filename = $user_id . '_' . time() . '.' . $file_ext;
        $target_file = 'uploads/profile/' . $new_filename;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            // Delete old profile image if it's not the default
            if ($user['profile_image'] != 'default_avatar.png' && file_exists('uploads/profile/' . $user['profile_image'])) {
                unlink('uploads/profile/' . $user['profile_image']);
            }

            // Update database with new image only
            $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $new_filename, $user_id);
            $stmt->execute();

            $profile_image = 'uploads/profile/' . $new_filename;

            // Refresh user data after image update
            $sql = "SELECT * FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            $update_message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">Profile image updated successfully!</div>';
        }
    }
}

// Update user data - only process this if the form submission is not for profile image
if (isset($_POST['update_profile']) && !isset($_FILES['profile_image'])) {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $room_number = $_POST['room_number'] ?? '';
    $occupation = $_POST['occupation'] ?? '';
    $emergency_contact = $_POST['emergency_contact'] ?? '';

    $sql = "UPDATE users SET full_name = ?, email = ?, room_number = ?, occupation = ?, emergency_contact = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $full_name, $email, $room_number, $occupation, $emergency_contact, $user_id);

    if ($stmt->execute()) {
        $update_message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">Profile updated successfully!</div>';

        // Refresh user data
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    } else {
        $update_message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Failed to update profile!</div>';
    }
}


// Handle KYC document upload
if (isset($_POST['upload_kyc'])) {
    $kyc_type = $_POST['kyc_type'];
    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

    if (isset($_FILES['kyc_document']) && $_FILES['kyc_document']['error'] === 0) {
        $filename = $_FILES['kyc_document']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed)) {
            // Create directory if not exists
            if (!file_exists('uploads/kyc/')) {
                mkdir('uploads/kyc/', 0777, true);
            }

            $new_filename = $user_id . '_' . $kyc_type . '_' . time() . '.' . $file_ext;
            $target_file = 'uploads/kyc/' . $new_filename;

            if (move_uploaded_file($_FILES['kyc_document']['tmp_name'], $target_file)) {
                // Update database based on KYC type
                $column_name = '';
                switch ($kyc_type) {
                    case 'id_proof':
                        $column_name = 'id_proof';
                        break;
                    case 'address_proof':
                        $column_name = 'address_proof';
                        break;
                    case 'selfie':
                        $column_name = 'selfie_verification';
                        break;
                }

                if (!empty($column_name)) {
                    $sql = "UPDATE users SET $column_name = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $new_filename, $user_id);

                    if ($stmt->execute()) {
                        // Check if all KYC documents are uploaded
                        $sql = "SELECT id_proof, address_proof, selfie_verification FROM users WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $kyc_data = $result->fetch_assoc();

                        if (!empty($kyc_data['id_proof']) && !empty($kyc_data['address_proof']) && !empty($kyc_data['selfie_verification'])) {
                            // Update KYC status to verified
                            $sql = "UPDATE users SET kyc_status = 'verified' WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();

                            $kyc_status = 'verified';
                            $kyc_badge_class = 'kyc-verified';
                            $kyc_badge_text = 'Verified';
                        }

                        $update_message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">Document uploaded successfully!</div>';

                        // Refresh user data
                        $sql = "SELECT * FROM users WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $user = $result->fetch_assoc();
                    } else {
                        $update_message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Failed to update document information!</div>';
                    }
                }
            } else {
                $update_message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Failed to upload document!</div>';
            }
        } else {
            $update_message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Invalid file format! Allowed formats: jpg, jpeg, png, pdf</div>';
        }
    }
}

// Form action now points to the process file
$form_action = 'process/profile_update_process.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Bachelor Room Expense Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/back-navigation.js"></script>

    <link rel="stylesheet" href="./css/style.css">

</head>

<body>


    <header class="app-header fixed top-0 left-0 right-0 z-10 py-2.5 px-3">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-2.5">
                <button onclick="goBack()" class="mr-3 back-button" title="Go Back" style="margin-left: 15px;">
                    <i class="fas fa-arrow-left text-white text-xl"></i>
                </button>

                <div>
                    <h1 class="text-base font-bold text-white tracking-tight">Profile Manager</h1>
                    <p class="text-[12px] text-white text-opacity-80 -mt-0.5">Manage Your Profile</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <div class="relative">
                    <div class="w-7 h-7 rounded-full bg-white bg-opacity-20 flex items-center justify-center backdrop-blur-sm cursor-pointer hover:bg-opacity-30 transition-all duration-200" onclick="location.reload()" id="refreshButton">
                        <i class="fas fa-sync-alt text-white text-sm"></i>
                    </div>

                </div>
                <a href="profile.php" class="transform transition-transform hover:scale-105">
                    <div class="h-8 w-8 rounded-full bg-white bg-opacity-20 flex items-center justify-center overflow-hidden border border-white border-opacity-30 backdrop-blur-sm" style="border: 2px solid white; height: 35px; width: 35px;">
                        <img src="<?php echo $profile_image; ?>" alt="Profile" class="h-full w-full object-cover">
                    </div>
                </a>
            </div>
        </div>
    </header>



    <!-- Main Content -->
    <main class="container mx-auto px-4 pt-20 pb-6 max-w-md">
        <?php echo $update_message; ?>

        <!-- Profile Header -->
        <div class="profile-header p-5 mb-4 mt-3 rounded-3xl relative text-white text-center shadow-md bg-gradient-to-tr from-indigo-600 to-purple-600">
            <form id="profile_image_form" action="" method="post" enctype="multipart/form-data" class="mb-4">
                <div class="flex justify-center">
                    <div class="profile-image-container relative">
                        <img src="<?php echo $profile_image; ?>" alt="Profile" class="h-24 w-24 rounded-full border-4 border-white shadow-lg object-cover">
                        <label for="profile_image" class="profile-image-edit absolute bottom-2 right-2 bg-white text-indigo-600 rounded-full p-2 cursor-pointer shadow-md hover:bg-indigo-100" style="left: 61px; bottom: -10px; right: -4px;">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" id="profile_image" name="profile_image" class="hidden" onchange="document.getElementById('profile_image_form').submit()">
                        <input type="hidden" name="update_profile" value="1">
                    </div>
                </div>
            </form>

            <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($user['full_name']); ?></h2>
            <p class="text-indigo-100"><?php echo htmlspecialchars($user['email'] ?? 'Email not set'); ?></p>
            <p class="text-indigo-100 text-sm"><?php echo htmlspecialchars($user['phone']); ?></p>

            <div class="mt-4 flex justify-center">
                <span class="<?php echo $kyc_badge_class; ?> text-xs px-3 py-1 rounded-full font-medium bg-white/20 backdrop-blur-sm">
                    KYC <?php echo $kyc_badge_text; ?>
                </span>
            </div>
        </div>


        <!-- Profile Information -->
        <div class="p-6 mb-5 mt-3 rounded-3xl relative bg-gradient-to-br from-white via-gray-50 to-gray-100 ">

            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold text-gray-800">Personal Information</h3>
                <button id="edit-profile-btn" class="flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-700 transition">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>

            <!-- View Mode -->
            <div id="profile-view" class="space-y-4">
                <div class="flex justify-between items-center border-b pb-2">
                    <p class="text-gray-500">Full Name</p>
                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($user['full_name']); ?></p>
                </div>

                <div class="flex justify-between items-center border-b pb-2">
                    <p class="text-gray-500">Joined Date</p>
                    <p class="font-medium text-gray-800"><?php echo $joined_date; ?></p>
                </div>

                <div class="flex justify-between items-center border-b pb-2">
                    <p class="text-gray-500">Occupation</p>
                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($user['occupation'] ?? 'Not set'); ?></p>
                </div>

                <div class="flex justify-between items-center">
                    <p class="text-gray-500">Emergency Contact</p>
                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($user['emergency_contact'] ?? 'Not set'); ?></p>
                </div>
            </div>

            <!-- Edit Mode -->
            <form id="profile-edit" action="<?php echo $form_action; ?>" method="post" enctype="multipart/form-data" class="space-y-4 hidden mt-4">

                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email (optional)</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label for="occupation" class="block text-sm font-medium text-gray-700 mb-1">Occupation (optional)</label>
                    <input type="text" id="occupation" name="occupation" value="<?php echo htmlspecialchars($user['occupation'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label for="emergency_contact" class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact</label>
                    <input type="text" id="emergency_contact" name="emergency_contact" value="<?php echo htmlspecialchars($user['emergency_contact'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                </div>

                <div class="flex space-x-3 pt-2">
                    <button type="submit" name="update_profile" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg font-medium transition">Save Changes</button>
                    <button type="button" id="cancel-edit" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 rounded-lg font-medium transition">Cancel</button>
                </div>
            </form>
        </div>


        <!-- KYC Section -->
        <div class="p-5 mb-4 mt-3 rounded-3xl  relative bg-gradient-to-br from-gray-50 via-white to-gray-100  border-gray-200">
            <h3 class="text-lg font-semibold mb-4">KYC Verification</h3>

            <div class="space-y-4">
                <p class="text-sm text-gray-600">Complete your KYC verification to unlock all features of the app. This helps in maintaining security and trust among roommates.</p>

                <div class="border border-gray-200 rounded-lg p-3">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <i class="fas fa-id-card text-blue-500 mr-3 text-xl"></i>
                            <div>
                                <p class="font-medium">ID Proof</p>
                                <p class="text-xs text-gray-500">Aadhar Card, PAN, or Passport</p>
                            </div>
                        </div>
                        <?php if (!empty($user['id_proof'])): ?>
                            <span class="kyc-verified text-xs px-3 py-1 rounded-full font-medium">Uploaded</span>
                        <?php else: ?>
                            <form action="" method="post" enctype="multipart/form-data" class="inline">
                                <input type="hidden" name="kyc_type" value="id_proof">
                                <label for="id_proof" class="bg-blue-600 text-white text-sm px-3 py-1 rounded-lg cursor-pointer">Upload</label>
                                <input type="file" id="id_proof" name="kyc_document" class="hidden" onchange="this.form.submit()">
                                <input type="hidden" name="upload_kyc" value="1">
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-3">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <i class="fas fa-home text-blue-500 mr-3 text-xl"></i>
                            <div>
                                <p class="font-medium">Address Proof</p>
                                <p class="text-xs text-gray-500">Utility Bill or Rental Agreement</p>
                            </div>
                        </div>
                        <?php if (!empty($user['address_proof'])): ?>
                            <span class="kyc-verified text-xs px-3 py-1 rounded-full font-medium">Uploaded</span>
                        <?php else: ?>
                            <form action="" method="post" enctype="multipart/form-data" class="inline">
                                <input type="hidden" name="kyc_type" value="address_proof">
                                <label for="address_proof" class="bg-blue-600 text-white text-sm px-3 py-1 rounded-lg cursor-pointer">Upload</label>
                                <input type="file" id="address_proof" name="kyc_document" class="hidden" onchange="this.form.submit()">
                                <input type="hidden" name="upload_kyc" value="1">
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-3">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <i class="fas fa-user-check text-blue-500 mr-3 text-xl"></i>
                            <div>
                                <p class="font-medium">Selfie Verification</p>
                                <p class="text-xs text-gray-500">Take a selfie for verification</p>
                            </div>
                        </div>
                        <?php if (!empty($user['selfie_verification'])): ?>
                            <span class="kyc-verified text-xs px-3 py-1 rounded-full font-medium">Completed</span>
                        <?php else: ?>
                            <form action="" method="post" enctype="multipart/form-data" class="inline">
                                <input type="hidden" name="kyc_type" value="selfie">
                                <label for="selfie" class="bg-blue-600 text-white text-sm px-3 py-1 rounded-lg cursor-pointer">Upload</label>
                                <input type="file" id="selfie" name="kyc_document" class="hidden" onchange="this.form.submit()">
                                <input type="hidden" name="upload_kyc" value="1">
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($kyc_status != 'verified'): ?>
                    <div class="mt-4">
                        <p class="text-sm text-gray-600 mb-2">Upload all documents to complete your KYC verification.</p>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <?php
                            $progress = 0;
                            if (!empty($user['id_proof'])) $progress += 33.33;
                            if (!empty($user['address_proof'])) $progress += 33.33;
                            if (!empty($user['selfie_verification'])) $progress += 33.34;
                            ?>
                            <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Logout Button -->
        <div class="p-5 mb-4 mt-3 rounded-3xl  relative bg-gradient-to-br from-gray-50 via-white to-gray-100  border-gray-200">
            <a href="process/logout_process.php" class="block w-full bg-red-600 text-white py-3 rounded-lg font-medium text-center">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </a>
        </div>
    </main>

    <script>
        // Toggle profile edit mode
        document.getElementById('edit-profile-btn').addEventListener('click', function() {
            document.getElementById('profile-view').classList.add('hidden');
            document.getElementById('profile-edit').classList.remove('hidden');
        });

        document.getElementById('cancel-edit').addEventListener('click', function() {
            document.getElementById('profile-edit').classList.add('hidden');
            document.getElementById('profile-view').classList.remove('hidden');
        });
    </script>
</body>

</html>