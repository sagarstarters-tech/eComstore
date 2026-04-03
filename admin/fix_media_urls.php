<?php
/**
 * WhatsApp Media URL Cleanup — ADMIN ONLY
 * Fixes broken double-linked URLs in media_library.
 * DELETE AFTER RUNNING.
 */
include 'admin_header.php';
echo "<h2>Database Media URL Cleanup</h2>";

// 1. Identify broken URLs
$res = $conn->query("SELECT id, file_url FROM media_library");
$count = 0;
while ($row = $res->fetch_assoc()) {
    $id = $row['id'];
    $url = $row['file_url'];
    
    // Check for double concatenation or missing slash
    // Pattern: domain.comhttps://domain.com/
    if (preg_match('/\.comhttps:\/\//i', $url)) {
         $new_url = preg_replace('/^.*?(https?:\/\/)/i', '$1', $url);
         
         $stmt = $conn->prepare("UPDATE media_library SET file_url = ? WHERE id = ?");
         $stmt->bind_param("si", $new_url, $id);
         if ($stmt->execute()) {
             echo "<div class='text-success'>Fixed ID $id: From $url TO $new_url</div>";
             $count++;
         }
         $stmt->close();
    }
}

if ($count === 0) {
    echo "<div class='alert alert-info'>No broken URLs found.</div>";
} else {
    echo "<div class='alert alert-success'>Successfully fixed $count broken URLs.</div>";
}

include 'admin_footer.php';
?>
