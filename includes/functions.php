<?php
/**
 * Common functions for Video Portal
 */

// Sanitize input data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Create URL friendly slug
function create_slug($string) {
  $slug = preg_replace('~[^\pL\d]+~u', '-', $string);
  $slug = iconv('utf-8', 'us-ascii//TRANSLIT', $slug);
  $slug = preg_replace('~[^-\w]+~', '', $slug);
  $slug = trim($slug);
  $slug = preg_replace('~-+~', '-', $slug);
  $slug = strtolower($slug);
  if (empty($slug)) {
    return 'n-a';
  }
  return $slug;
}

// Check if user is logged in
function is_logged_in() {
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return true;
    }
    return false;
}

// Redirect to URL
function redirect($url) {
    header("Location: $url");
    exit();
}

// Flash messages
function set_message($message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

function display_message() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'];
        
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
        
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}

// Get video embed code based on source
function get_video_embed($source, $video_id, $width = '100%', $height = '315') {
    switch ($source) {
        case 'youtube':
            // YouTube embed with 16:9 aspect ratio
            return "<div class='video-responsive video-responsive-16by9'>
                    <iframe src='https://www.youtube.com/embed/{$video_id}' frameborder='0' 
                            allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture' 
                            allowfullscreen></iframe>
                    </div>";
        
        case 'tiktok':
            // TikTok embed with square aspect ratio (1:1)
            return "<div class='video-responsive video-responsive-1by1'>
                    <iframe src='https://www.tiktok.com/embed/v2/{$video_id}' 
                            frameborder='0' allow='autoplay' allowfullscreen>
                    </iframe>
                    </div>";
        
        case 'facebook':
            // Facebook embed with flexible aspect ratio
            return "<div class='video-responsive video-responsive-4by3'>
                    <iframe src='https://www.facebook.com/plugins/video.php?href=https://www.facebook.com/watch/?v={$video_id}&show_text=false&width=476&height=476&appId' 
                            frameborder='0' allowfullscreen='true' 
                            allow='autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share'>
                    </iframe>
                    </div>";
        
        default:
            return "<div class='alert alert-warning'>Unsupported video source</div>";
    }
}

// Extract video ID from various platform URLs
function extract_video_id($url, $source) {
    switch ($source) {
        case 'youtube':
            // Handle different YouTube URL formats
            $pattern = '/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
            break;
            
        case 'tiktok':
            // Handle TikTok URLs
            $pattern = '/tiktok\.com\/@[^\/]+\/video\/(\d+)/';
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
            break;
            
        case 'facebook':
            // Handle Facebook video URLs
            if (strpos($url, 'facebook.com') !== false) {
                $pattern = '/(?:facebook\.com\/(?:watch\/\?v=|[\w\.]+\/videos\/))(\d+)/';
                if (preg_match($pattern, $url, $matches)) {
                    return $matches[1];
                }
            }
            break;
    }
    
    return false;
}

// Get categories list
function get_categories() {
    global $conn;
    $categories = [];
    
    $query = "SELECT * FROM categories ORDER BY name ASC";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    
    return $categories;
}

// Get tags list
function get_tags() {
    global $conn;
    $tags = [];
    
    $query = "SELECT * FROM tags ORDER BY name ASC";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }
    }
    
    return $tags;
}

// Get video tags
function get_video_tags($video_id) {
    global $conn;
    $tags = [];
    
    $query = "SELECT t.id, t.name, t.slug FROM tags t 
              JOIN video_tags vt ON t.id = vt.tag_id 
              WHERE vt.video_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $video_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }
    }
    
    return $tags;
}

// Upload thumbnail image
function upload_thumbnail($file) {
    $target_dir = "../uploads/thumbnails/";
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Check if image file is a actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return false;
    }
    
    // Check file size (limit to 2MB)
    if ($file["size"] > 2000000) {
        return false;
    }
    
    // Allow certain file formats
    if ($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "gif") {
        return false;
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $new_filename;
    } else {
        return false;
    }
}
