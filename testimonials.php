<?php
// Include config file
require_once "config.php";



// Fetch testimonials from the reviews table
function getTestimonials($conn)
{
    // Join the reviews table with users to get patient names
    $sql = "SELECT r.id, r.review_text, r.rating, r.created_at, 
            CONCAT(u.first_name, ' ', SUBSTRING(u.last_name, 1, 1), '.') AS patient_name,
            a.description
            FROM reviews r
            JOIN users u ON r.patient_id = u.id
            JOIN appointments a ON r.appointment_id = a.id
            WHERE r.rating >= 4
            ORDER BY r.created_at DESC
            LIMIT 6";

    $result = mysqli_query($conn, $sql);

    $testimonials = [];
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $testimonials[] = $row;
        }
    }

    return $testimonials;
}

// Get star rating HTML
function getStarRating($rating)
{
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fas fa-star"></i>';
        } else {
            $stars .= '<i class="far fa-star"></i>';
        }
    }
    return $stars;
}
?>

<section class="testimonials">
    <div class="container">
        <div class="section-header">
            <h2>What Our Patients Say</h2>
            <p>Real experiences from our satisfied clients</p>
        </div>
        <div class="testimonials-slider">
            <?php
            // Get testimonials from database
            $testimonials = getTestimonials($conn);

            // If there are no testimonials in the database yet, show default ones
            if (empty($testimonials)) {
                // Default testimonials array (your current static testimonials)
                $default_testimonials = [
                    [
                        'review_text' => "After struggling with acne for years, DermaCare's treatments completely transformed my skin. I finally feel confident again!",
                        'patient_name' => "Jessica M.",
                        'treatment_type' => "Acne Treatment"
                    ],
                    [
                        'review_text' => "The hair restoration treatment exceeded my expectations. The staff was professional and the results are amazing.",
                        'patient_name' => "Robert L.",
                        'treatment_type' => "Hair Restoration"
                    ],
                    [
                        'review_text' => "I was nervous about getting laser treatment, but the team made me feel comfortable throughout the entire process.",
                        'patient_name' => "Michelle K.",
                        'treatment_type' => "Laser Treatment"
                    ]
                ];

                $testimonials = $default_testimonials;
            }

            // Display testimonials
            foreach ($testimonials as $testimonial) {
                ?>
                <div class="testimonial-card">
                    <div class="quote-icon">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p style="font-style: italic;"><?php echo htmlspecialchars($testimonial['review_text']); ?></p>
                    <?php if (isset($testimonial['rating'])) { ?>
                        <div class="rating">
                            <?php echo getStarRating($testimonial['rating']); ?>
                        </div>
                    <?php } ?>
                    <div class="testimonial-author">
                        <h4><?php echo htmlspecialchars($testimonial['patient_name']); ?></h4>
                        <p><?php echo htmlspecialchars($testimonial['description']); ?> Patient</p>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</section>