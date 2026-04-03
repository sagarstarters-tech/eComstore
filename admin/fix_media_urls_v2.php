<?php
/**
 * Media URL Portability Fix
 * Strips domain names from file_url in media_library to make them relative.
 */
include 'admin_header.php';

$res = $conn->query("SELECT id, file_url FROM media_library");
$count = 0;
while ($row = $res->fetch_assoc()) {
    $id = $row['id'];
    $url = $row['file_url'];
    
    // If it starts with http, we strip everything until /uploads/
    if (strpos($url, 'http') === 0) {
        $new_url = preg_replace('/^.*(uploads\/media\/.*)$/i', '$1', $url);
        
        if ($new_url !== $url) {
            $stmt = $conn->prepare("UPDATE media_library SET file_url = ? WHERE id = ?");
            $stmt->bind_param("si", $new_url, $id);
            if ($stmt->execute()) {
                echo "<p>Fixed ID $id: $new_url</p>";
                $count++;
            }
            $stmt->close();
        }
    }
}
echo "<h3>Cleaned $count records.</h3>";
include 'admin_footer.php';
?>
