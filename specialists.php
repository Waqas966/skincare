<?php
// Include config file
require_once "config.php";

// Query to get specialists with their information
$sql = "SELECT u.id, u.first_name, u.last_name, s.experience, s.specialization 
        FROM users u 
        JOIN specialists s ON u.id = s.user_id 
        WHERE u.user_type = 'specialist' 
        AND u.approval_status = 'approved' 
        AND u.status = 1";

$result = $conn->query($sql);

// Array to store placeholder images - expanded with more options
$placeholderImages = [
    'male' => [
        "./images/male1.png", 
        "./images/male2.png",
        "./images/male3.png",
        "./images/male4.png",
        "./images/male5.png"
    ],
    'female' => [
        "./images/female1.png", 
        "./images/female2.png",
        "./images/female3.png",
        "./images/female4.png",
        "./images/female5.png"
    ]
];

// Helper function to get specialty title in proper format
function getSpecializationTitle($specialization)
{
    switch ($specialization) {
        case 'skin_care':
            return 'Skin Care Specialist';
        case 'laser':
            return 'Laser Treatment Specialist';
        case 'cosmetic':
            return 'Cosmetic Dermatologist';
        case 'hair':
            return 'Hair Restoration Specialist';
        default:
            return 'Dermatologist';
    }
}

// Improved placeholder image function
// This will better distribute the images and avoid repetition
function getPlaceholderImage($counter, $placeholderImages)
{
    // Determine gender based on counter
    $gender = ($counter % 2 == 0) ? 'male' : 'female';
    
    // Find available images for this gender
    $availableImages = $placeholderImages[$gender];
    $imageCount = count($availableImages);
    
    // Select image based on counter, ensures we cycle through all available images
    $imageIndex = floor($counter / 2) % $imageCount;
    
    return $availableImages[$imageIndex];
}

// Counter for image selection
$imageCounter = 0;
?>

<section class="specialists">
    <div class="container">
        <div class="section-header">
            <h2>Our Specialists</h2>
            <p>Meet our team of certified dermatologists and specialists</p>
        </div>
        <div class="specialists-slider">
            <?php
            if ($result->num_rows > 0) {
                // Output data of each row
                while ($row = $result->fetch_assoc()) {
                    $image = getPlaceholderImage($imageCounter, $placeholderImages);
                    $imageCounter++; // Increment counter after selecting image
                    $fullName = "Dr. " . $row["first_name"] . " " . $row["last_name"];
                    $title = getSpecializationTitle($row["specialization"]);
                    $experience = $row["experience"];
                    ?>
                    <div class="specialist-card">
                        <div class="specialist-img">
                            <img src="<?php echo $image; ?>" alt="<?php echo $fullName; ?>">
                        </div>
                        <h3><?php echo $fullName; ?></h3>
                        <p><?php echo $title; ?></p>
                        <div class="specialist-details">
                            <p>Experienced professional with <?php echo $experience; ?> years of expertise in
                                <?php echo strtolower(str_replace('_', ' ', $row["specialization"])); ?> treatments.</p>
                        </div>
                    </div>
                    <?php
                }
            } else {
                // If no specialists in database, show placeholders with different images
                ?>
                <div class="specialist-card">
                    <div class="specialist-img">
                        <img src="./images/female1.png" alt="Dr. Sarah Johnson">
                    </div>
                    <h3>Dr. Sarah Johnson</h3>
                    <p>Dermatologist</p>
                    <div class="specialist-details">
                        <p>Specializing in acne treatment and skin rejuvenation with over 10 years of experience.</p>
                    </div>
                </div>
                <div class="specialist-card">
                    <div class="specialist-img">
                        <img src="./images/male1.png" alt="Dr. Michael Chen">
                    </div>
                    <h3>Dr. Michael Chen</h3>
                    <p>Cosmetic Dermatologist</p>
                    <div class="specialist-details">
                        <p>Expert in cosmetic procedures including Botox and dermal fillers.</p>
                    </div>
                </div>
                <div class="specialist-card">
                    <div class="specialist-img">
                        <img src="./images/female2.png" alt="Dr. Emily Rodriguez">
                    </div>
                    <h3>Dr. Emily Rodriguez</h3>
                    <p>Hair Restoration Specialist</p>
                    <div class="specialist-details">
                        <p>Specializing in hair loss treatments and scalp conditions.</p>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</section>